<?php declare(strict_types=1);
require_once 'db.php';
require_once 'avatar.php';

$pass_raw  = (string)($_POST['pass'] ?? '');
$pass2_raw = (string)($_POST['pass2'] ?? '');
$user      = htmlentities($_POST['user'] ?? '', ENT_QUOTES, "UTF-8");
$pass      = htmlentities($pass_raw, ENT_QUOTES, "UTF-8");

$wroc = function (string $komunikat) use ($user): void {
	header('Location: rejestruj.php?blad=' . urlencode($komunikat) . '&login=' . urlencode(html_entity_decode($user, ENT_QUOTES, 'UTF-8')));
	exit();
};

if ($user === '' || $pass_raw === '')
{
	$wroc('Podaj login i hasło.');
}
if (!preg_match('/^[A-Za-z0-9_-]{1,20}$/', $user))
{
	$wroc('Login może mieć do 20 znaków: litery bez polskich znaków, cyfry, _ oraz - (to będzie nazwa Twojego katalogu z plikami).');
}
if ($pass_raw !== $pass2_raw)
{
	$wroc('Hasła nie są takie same. Wpisz je jeszcze raz.');
}

$link = db_polacz();
if (!$link)
{
	$wroc('Błąd połączenia z bazą danych.');
}
avatar_upewnij_kolumne($link);

$sprawdz  = mysqli_query($link, "SELECT * FROM users WHERE username='$user'");
$istnieje = $sprawdz ? mysqli_fetch_array($sprawdz) : null;
if ($istnieje)
{
	mysqli_close($link);
	$wroc('Użytkownik o takim loginie już istnieje. Wybierz inny login.');
}

[$avatar, $blad_avatara] = avatar_zapisz($_FILES['avatar'] ?? [], $user);
if ($blad_avatara !== '')
{
	mysqli_close($link);
	$wroc($blad_avatara);
}
$avatar_sql = $avatar === null ? 'NULL' : "'" . $avatar . "'";

$result = mysqli_query($link, "INSERT INTO users (username, password, avatar) VALUES ('$user', '$pass', $avatar_sql)");
if ($result)
{
	mysqli_close($link);
	header('Location: index3.php?info=' . urlencode('Rejestracja zakończona sukcesem! Możesz się teraz zalogować.'));
	exit();
}
$blad = mysqli_error($link);
mysqli_close($link);
avatar_usun_plik($avatar);
$wroc('Błąd podczas rejestracji: ' . $blad);
