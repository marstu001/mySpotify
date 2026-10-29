<?php declare(strict_types=1);
require_once 'db.php';
require_once 'geo.php';

header('Content-Type: text/plain; charset=utf-8');

$id    = isset($_POST['id']) && ctype_digit((string)$_POST['id']) ? (int)$_POST['id'] : 0;
$token = (string)($_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0 || !hash_equals(wizyta_token($id), $token)) {
	http_response_code(400);
	exit('niepoprawne żądanie');
}

$ekran = (string)($_POST['ekran'] ?? '');
$ekran = preg_match('/^\d{1,5}x\d{1,5}$/', $ekran) ? $ekran : null;

$okno = (string)($_POST['okno'] ?? '');
$okno = preg_match('/^\d{1,5}x\d{1,5}$/', $okno) ? $okno : null;

$kolory = (string)($_POST['kolory'] ?? '');
$kolory = (ctype_digit($kolory) && (int)$kolory <= 64) ? (int)$kolory : null;

$ciasteczka = (($_POST['ciasteczka'] ?? '') === '1') ? 1 : 0;
$java       = (($_POST['java'] ?? '') === '1') ? 1 : 0;

$jezyk = (string)($_POST['jezyk'] ?? '');
$jezyk = preg_match('/^[A-Za-z0-9_-]{1,20}$/', $jezyk) ? $jezyk : null;

$link = db_polacz();
if (!$link) {
	http_response_code(500);
	exit('brak połączenia z bazą');
}

$stmt = mysqli_prepare($link, 'UPDATE goscieportalu SET ekran = ?, okno = ?, kolory = ?, ciasteczka = ?, java = ?, jezyk = ? WHERE id = ?');
if (!$stmt) {
	http_response_code(500);
	exit('błąd bazy');
}
mysqli_stmt_bind_param($stmt, 'ssiiisi', $ekran, $okno, $kolory, $ciasteczka, $java, $jezyk, $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($link);

echo 'OK';
