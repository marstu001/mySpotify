<?php declare(strict_types=1);
session_start();
require_once 'db.php';
require_once 'avatar.php';
require_once 'geo.php';

list($dostep, $kraj, $powod) = sprawdz_dostep_ip($_SERVER['REMOTE_ADDR'] ?? '');
if (!$dostep)
{
	header('Location: index3.php?blad=' . urlencode('Logowanie zablokowane: Twoje IP wskazuje na kraj spoza terytorium Polski' . ($kraj !== '' ? " ($kraj)" : '') . '.'));
	exit();
}

$user = htmlentities($_POST['user'] ?? '', ENT_QUOTES, "UTF-8");
$pass = htmlentities($_POST['pass'] ?? '', ENT_QUOTES, "UTF-8");

$link = db_polacz();
if (!$link)
{
	header('Location: index3.php?blad=' . urlencode('Błąd połączenia z bazą danych.'));
	exit();
}

avatar_upewnij_kolumne($link);
$result = mysqli_query($link, "SELECT * FROM users WHERE username='$user'");
$rekord = $result ? mysqli_fetch_array($result) : null;

if (!$rekord)
{
	mysqli_close($link);
	header('Location: index3.php?blad=' . urlencode('Brak użytkownika o takim loginie.'));
	exit();
}

if ($rekord['password'] !== $pass)
{
	mysqli_close($link);
	header('Location: index3.php?blad=' . urlencode('Błędne hasło.'));
	exit();
}

session_regenerate_id(true);
$_SESSION['loggedin']       = true;
$_SESSION['user_id']        = (int)$rekord['id'];
$_SESSION['username']       = $rekord['username'];
$_SESSION['avatar']         = (string)($rekord['avatar'] ?? '');
$_SESSION['wizyta_id']      = zapisz_wizyte($link);
$_SESSION['klient_wyslany'] = false;
mysqli_close($link);
header('Location: index4.php');
exit();
