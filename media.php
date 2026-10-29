<?php declare(strict_types=1);
require_once 'db.php';
require_once 'media_cfg.php';

if (!function_exists('e')) {
	function e(?string $s): string
	{
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}

function m(string $klucz)
{
	return MEDIA[$klucz];
}

function sesja_start(): void
{
	if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
}

function csrf_token(): string
{
	sesja_start();
	if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
	return $_SESSION['csrf'];
}
function csrf_pole(): string
{
	return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_wymagaj(): void
{
	sesja_start();
	$token = (string)($_POST['csrf'] ?? '');
	if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
		http_response_code(400);
		exit('Nieprawidłowe żądanie (brak lub zły token formularza). Wróć do poprzedniej strony i spróbuj ponownie.');
	}
}

function flash(string $typ, string $tekst): void
{
	sesja_start();
	$_SESSION['flash'][] = [$typ, $tekst];
}
function flash_wypisz(): void
{
	sesja_start();
	foreach ($_SESSION['flash'] ?? [] as [$typ, $tekst]) {
		echo '<div class="alert alert-' . e($typ) . '" role="alert">' . e($tekst) . '</div>' . "\n";
	}
	unset($_SESSION['flash']);
}

function wymagaj_logowania(): array
{
	sesja_start();
	if (empty($_SESSION['loggedin']) || empty($_SESSION['user_id'])) {
		header('Location: index3.php');
		exit();
	}
	return [(int)$_SESSION['user_id'], (string)$_SESSION['username']];
}

function polacz_lub_zakoncz(): mysqli
{
	$link = db_polacz();
	if (!$link) { exit('Błąd połączenia z bazą danych.'); }
	return $link;
}

function sql_wiersze(mysqli $link, string $sql, string $typy = '', array $par = []): array
{
	$st = mysqli_prepare($link, $sql);
	if (!$st) { return []; }
	if ($par) { mysqli_stmt_bind_param($st, $typy, ...$par); }
	if (!mysqli_stmt_execute($st)) { mysqli_stmt_close($st); return []; }
	$meta = mysqli_stmt_result_metadata($st);
	if (!$meta) { mysqli_stmt_close($st); return []; }
	$wiersz = []; $ref = [];
	foreach (mysqli_fetch_fields($meta) as $p) { $ref[] = &$wiersz[$p->name]; }
	mysqli_stmt_bind_result($st, ...$ref);
	$wynik = [];
	while (mysqli_stmt_fetch($st)) {
		$kopia = [];
		foreach ($wiersz as $k => $v) { $kopia[$k] = $v; }
		$wynik[] = $kopia;
	}
	mysqli_stmt_close($st);
	return $wynik;
}
function sql_jeden(mysqli $link, string $sql, string $typy = '', array $par = []): ?array
{
	$w = sql_wiersze($link, $sql, $typy, $par);
	return $w ? $w[0] : null;
}
function sql_wykonaj(mysqli $link, string $sql, string $typy = '', array $par = []): array
{
	$st = mysqli_prepare($link, $sql);
	if (!$st) { return ['ok' => false, 'id' => 0, 'ile' => 0]; }
	if ($par) { mysqli_stmt_bind_param($st, $typy, ...$par); }
	$ok  = mysqli_stmt_execute($st);
	$wyn = ['ok' => $ok, 'id' => (int)mysqli_stmt_insert_id($st), 'ile' => max(0, (int)mysqli_stmt_affected_rows($st))];
	mysqli_stmt_close($st);
	return $wyn;
}

function login_poprawny(string $login): bool
{
	return (bool)preg_match('/^[A-Za-z0-9_-]{1,20}$/', $login);
}

function katalog_mediow(): ?string
{
	$k = __DIR__ . '/' . m('katalog');
	if (!is_dir($k) && !@mkdir($k, 0755, true)) { return null; }
	$ht = $k . '/.htaccess';
	if (!is_file($ht)) {
		@file_put_contents($ht, "Options -Indexes\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n");
	}
	if (!is_file($k . '/index.html')) { @file_put_contents($k . '/index.html', ''); }
	return $k;
}
function katalog_uzytkownika(string $login): ?string
{
	if (!login_poprawny($login)) { return null; }
	$k = katalog_mediow();
	if ($k === null) { return null; }
	$u = $k . '/' . $login;
	if (!is_dir($u) && !@mkdir($u, 0755)) { return null; }
	return $u;
}

function nazwa_pliku_czysta(string $n): string
{
	$n = basename(str_replace('\\', '/', $n));
	$n = preg_replace('/[\x00-\x1F\x7F"*:<>?|\/\\\\]/u', '_', $n) ?? '';
	$n = ltrim($n, ". \t");
	$n = rtrim($n, ". ");
	if ($n === '') { return ''; }
	if (strlen($n) > 150) {
		$roz = pathinfo($n, PATHINFO_EXTENSION);
		$n   = mb_strcut(pathinfo($n, PATHINFO_FILENAME), 0, 140 - strlen($roz), 'UTF-8') . ($roz !== '' ? '.' . $roz : '');
	}
	return $n;
}
function wolna_nazwa(string $katalog, string $nazwa): string
{
	if (!file_exists($katalog . '/' . $nazwa)) { return $nazwa; }
	$roz = pathinfo($nazwa, PATHINFO_EXTENSION);
	$baz = pathinfo($nazwa, PATHINFO_FILENAME);
	for ($i = 1; $i < 1000; $i++) {
		$n = $baz . " ($i)" . ($roz !== '' ? '.' . $roz : '');
		if (!file_exists($katalog . '/' . $n)) { return $n; }
	}
	return bin2hex(random_bytes(4)) . '_' . $nazwa;
}

function zawartosc_poprawna(string $tmp): bool
{
	$f = @fopen($tmp, 'rb');
	if ($f === false) { return false; }
	$g = (string)fread($f, 16);
	fclose($f);
	if (m('roz') === 'mp3') {
		if (substr($g, 0, 3) === 'ID3') { return true; }
		return strlen($g) >= 2 && ord($g[0]) === 0xFF && (ord($g[1]) & 0xE0) === 0xE0;
	}
	return strlen($g) >= 12 && substr($g, 4, 4) === 'ftyp';
}

function gatunki(mysqli $link): array
{
	return sql_wiersze($link, 'SELECT ' . m('gat') . ' AS id, ' . m('gat_nazwa') . ' AS nazwa FROM ' . m('gat_tabela') . ' ORDER BY ' . m('gat'));
}

function sql_pozycja_pola(): string
{
	return 's.' . m('id') . ' AS id, s.' . m('tytul') . ' AS tytul, s.' . m('autor') . ' AS autor, s.`datetime` AS data, s.idu, s.' . m('plik') . ' AS plik, s.' . m('tekst') . ' AS tekst, s.' . m('gat') . ' AS gat, g.' . m('gat_nazwa') . ' AS gatunek, u.username AS wlasciciel';
}
function sql_pozycja_zrodlo(): string
{
	return m('tabela') . ' s LEFT JOIN ' . m('gat_tabela') . ' g ON g.' . m('gat') . ' = s.' . m('gat') . ' JOIN users u ON u.id = s.idu';
}
function pozycja(mysqli $link, int $id): ?array
{
	return sql_jeden($link, 'SELECT ' . sql_pozycja_pola() . ' FROM ' . sql_pozycja_zrodlo() . ' WHERE s.' . m('id') . ' = ?', 'i', [$id]);
}
function moje_pozycje(mysqli $link, int $uid): array
{
	return sql_wiersze($link, 'SELECT ' . sql_pozycja_pola() . ' FROM ' . sql_pozycja_zrodlo() . ' WHERE s.idu = ? ORDER BY s.`datetime` DESC, s.' . m('id') . ' DESC', 'i', [$uid]);
}

function czy_moze_odtwarzac(mysqli $link, int $uid, array $p): bool
{
	if ((int)$p['idu'] === $uid) { return true; }
	$id = (int)$p['id'];
	$r = sql_jeden($link, 'SELECT 1 AS x FROM playlistdatabase d JOIN playlistname n ON n.idpl = d.idpl WHERE d.' . m('id') . ' = ? AND (n.public = 1 OR n.idu = ?) LIMIT 1', 'ii', [$id, $uid]);
	return $r !== null;
}

function pozycje_do_dodania(mysqli $link, int $uid, int $idpl = 0): array
{
	$sql = 'SELECT DISTINCT ' . sql_pozycja_pola() . ' FROM ' . sql_pozycja_zrodlo()
		. ' WHERE (s.idu = ? OR s.' . m('id') . ' IN (SELECT d.' . m('id') . ' FROM playlistdatabase d JOIN playlistname n ON n.idpl = d.idpl WHERE n.public = 1))';
	$typy = 'i'; $par = [$uid];
	if ($idpl > 0) {
		$sql .= ' AND s.' . m('id') . ' NOT IN (SELECT ' . m('id') . ' FROM playlistdatabase WHERE idpl = ?)';
		$typy .= 'i'; $par[] = $idpl;
	}
	$sql .= ' ORDER BY u.username, s.' . m('tytul');
	return sql_wiersze($link, $sql, $typy, $par);
}

function playlista(mysqli $link, int $idpl): ?array
{
	return sql_jeden($link, 'SELECT n.idpl, n.idu, n.name, n.public, n.`datetime` AS data, u.username AS wlasciciel FROM playlistname n JOIN users u ON u.id = n.idu WHERE n.idpl = ?', 'i', [$idpl]);
}
function czy_dostep_playlisty(array $pl, int $uid): bool
{
	return (int)$pl['idu'] === $uid || (int)$pl['public'] === 1;
}
function elementy_playlisty(mysqli $link, int $idpl): array
{
	return sql_wiersze($link, 'SELECT d.idpldb, ' . sql_pozycja_pola() . ' FROM playlistdatabase d JOIN ' . m('tabela') . ' s ON s.' . m('id') . ' = d.' . m('id') . ' LEFT JOIN ' . m('gat_tabela') . ' g ON g.' . m('gat') . ' = s.' . m('gat') . ' JOIN users u ON u.id = s.idu WHERE d.idpl = ? ORDER BY d.idpldb', 'i', [$idpl]);
}

function url_strumien(int $id): string
{
	return 'stream.php?id=' . $id;
}
function typ_mime(): string
{
	return m('mime');
}
function ma_vtt(?string $tekst): bool
{
	return m('napisy_vtt') && is_string($tekst) && strncmp(ltrim($tekst, "\xEF\xBB\xBF \t\r\n"), 'WEBVTT', 6) === 0;
}

function liczba_pl(int $n, string $jedn, string $dwa, string $piec): string
{
	$d = $n % 10; $s = $n % 100;
	if ($n === 1) { return $jedn; }
	return ($d >= 2 && $d <= 4 && ($s < 12 || $s > 14)) ? $dwa : $piec;
}

function odtwarzacz(array $p, string $klasa = ''): string
{
	$src = e(url_strumien((int)$p['id']));
	$typ = e(typ_mime());
	if (m('tag') === 'video') {
		$track = ma_vtt($p['tekst']) ? '<track kind="subtitles" src="' . e('napisy.php?id=' . (int)$p['id']) . '" srclang="pl" label="Polski">' : '';
		return '<video controls preload="none" class="' . e($klasa) . '"><source src="' . $src . '" type="' . $typ . '">' . $track . 'Przeglądarka nie obsługuje odtwarzacza wideo.</video>';
	}
	return '<audio controls preload="none" class="' . e($klasa) . '"><source src="' . $src . '" type="' . $typ . '">Przeglądarka nie obsługuje odtwarzacza audio.</audio>';
}
