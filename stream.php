<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$id = (int)($_GET['id'] ?? 0);
$link = polacz_lub_zakoncz();
$p = $id > 0 ? pozycja($link, $id) : null;
if ($p === null) { mysqli_close($link); http_response_code(404); exit('Nie ma takiego pliku.'); }
if (!czy_moze_odtwarzac($link, $uid, $p)) { mysqli_close($link); http_response_code(403); exit('Ten plik nie jest Ci udostępniony.'); }
mysqli_close($link);

$katalog = katalog_uzytkownika((string)$p['wlasciciel']);
$plik = $katalog !== null ? realpath($katalog . '/' . $p['plik']) : false;
$kat_real = $katalog !== null ? realpath($katalog) : false;
if ($plik === false || $kat_real === false || strpos($plik, $kat_real . DIRECTORY_SEPARATOR) !== 0 || !is_file($plik)) {
	http_response_code(404);
	exit('Plik nie istnieje na serwerze.');
}

$rozmiar = (int)filesize($plik);
session_write_close();
while (ob_get_level() > 0) { ob_end_clean(); }
@ini_set('zlib.output_compression', '0');
@set_time_limit(0);

$start = 0; $koniec = $rozmiar - 1;
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $r) && ($r[1] !== '' || $r[2] !== '')) {
	if ($r[1] === '') { $start = max(0, $rozmiar - (int)$r[2]); }
	else { $start = (int)$r[1]; if ($r[2] !== '') { $koniec = min($koniec, (int)$r[2]); } }
	if ($start > $koniec || $start >= $rozmiar) {
		http_response_code(416);
		header('Content-Range: bytes */' . $rozmiar);
		exit();
	}
	http_response_code(206);
	header('Content-Range: bytes ' . $start . '-' . $koniec . '/' . $rozmiar);
}

$ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$p['plik']);
header('Content-Type: ' . typ_mime());
header('Content-Disposition: ' . (isset($_GET['pobierz']) ? 'attachment' : 'inline') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode((string)$p['plik']));
header('Content-Length: ' . ($koniec - $start + 1));
header('Accept-Ranges: bytes');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: sandbox');
header('Cache-Control: private, max-age=0, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') { exit(); }

$f = fopen($plik, 'rb');
if ($f === false) { exit(); }
fseek($f, $start);
$zostalo = $koniec - $start + 1;
while ($zostalo > 0 && !feof($f)) {
	$czesc = fread($f, (int)min(1048576, $zostalo));
	if ($czesc === false || $czesc === '') { break; }
	echo $czesc;
	$zostalo -= strlen($czesc);
	flush();
	if (connection_aborted()) { break; }
}
fclose($f);
