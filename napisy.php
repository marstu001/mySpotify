<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$id = (int)($_GET['id'] ?? 0);
$link = polacz_lub_zakoncz();
$p = $id > 0 ? pozycja($link, $id) : null;
$ok = $p !== null && czy_moze_odtwarzac($link, $uid, $p) && ma_vtt($p['tekst']);
mysqli_close($link);
if (!$ok) { http_response_code(404); exit(); }

header('Content-Type: text/vtt; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo ltrim((string)$p['tekst'], "\xEF\xBB\xBF");
