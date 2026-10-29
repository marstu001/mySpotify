<?php declare(strict_types=1);
require_once 'db.php';
require_once 'geo.php';

header('Content-Type: text/html; charset=utf-8');

$e = fn(?string $s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$tak_nie = fn($v): string => $v === null ? '—' : ((int)$v === 1 ? 'tak' : 'nie');

$tryb = $_GET['tryb'] ?? 'wszystkie';
if (!in_array($tryb, ['wszystkie', 'unikalne', 'ranking'], true)) {
	$tryb = 'wszystkie';
}

$link = db_polacz();
if (!$link) {
	exit('<p class="alert alert-warning">Brak połączenia z bazą danych &ndash; sprawdź ustawienia w pliku db.php.</p>');
}

$kolumny = 'g.id, g.ipaddress, g.`datetime`, g.przegladarka, g.ekran, g.okno, g.kolory, g.ciasteczka, g.java, g.jezyk';
if ($tryb === 'wszystkie') {
	$sql = "SELECT $kolumny, 1 AS wejscia FROM goscieportalu g ORDER BY g.id DESC LIMIT 100";
} else {
	$kolejnosc = $tryb === 'ranking' ? 's.wejscia DESC, g.id DESC' : 'g.id DESC';
	$sql = "SELECT $kolumny, s.wejscia FROM goscieportalu g "
	     . "JOIN (SELECT MAX(id) AS ostatnie_id, COUNT(*) AS wejscia FROM goscieportalu GROUP BY ipaddress) s ON g.id = s.ostatnie_id "
	     . "ORDER BY $kolejnosc LIMIT 100";
}

$wynik = mysqli_query($link, $sql);
if (!$wynik) {
	exit('<p class="alert alert-warning">Błąd zapytania: ' . $e(mysqli_error($link)) . ' &ndash; czy tabela goscieportalu została utworzona (utworz_tabele.php)?</p>');
}

$opis = [
	'wszystkie' => 'Ostatnie 100 wizyt, od najnowszej.',
	'unikalne'  => 'Każde IP występuje jeden raz (dane z jego ostatniej wizyty), kolumna „Wejść” pokazuje, ile razy dany gość odwiedził portal.',
	'ranking'   => 'Każde IP występuje jeden raz (dane z jego ostatniej wizyty), lista posortowana malejąco wg liczby wejść na portal.',
];
echo '<p class="small text-muted">' . $opis[$tryb] . ' Dane geolokalizacyjne bywają przybliżone lub celowo zafałszowane (VPN, sieć komórkowa).</p>';
echo '<div class="table-responsive"><table class="table table-bordered table-striped table-sm table-hover align-middle">';
echo '<thead class="table-dark"><tr><th>Lp.</th><th>IP</th>' . ($tryb === 'wszystkie' ? '' : '<th>Wejść</th>') . '<th>' . ($tryb === 'wszystkie' ? 'Data i godzina' : 'Ostatnia wizyta') . '</th>'
   . '<th>Miasto</th><th>Region</th><th>Kraj</th><th>Współrzędne</th><th>Mapa</th>'
   . '<th>Przeglądarka</th><th>Ekran</th><th>Okno</th><th>Kolory (bit)</th><th>Ciasteczka</th><th>Java</th><th>Język</th></tr></thead><tbody>';

$lp = 0;
while ($w = mysqli_fetch_assoc($wynik)) {
	$lp++;
	$d    = ip_details($w['ipaddress']);
	$loc  = geo_pole($d, 'loc');
	$mapa = preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $loc)
		? '<a href="https://www.google.pl/maps/place/' . $e($loc) . '" target="_blank" rel="noopener">LINK</a>'
		: '—';

	echo '<tr>';
	echo '<td>' . $lp . '</td>';
	echo '<td>' . $e($w['ipaddress']) . '</td>';
	if ($tryb !== 'wszystkie') {
		echo '<td><strong>' . (int)$w['wejscia'] . '</strong></td>';
	}
	echo '<td>' . $e($w['datetime']) . '</td>';
	echo '<td>' . $e(geo_pole($d, 'city') ?: '—') . '</td>';
	echo '<td>' . $e(geo_pole($d, 'region') ?: '—') . '</td>';
	echo '<td>' . $e(geo_pole($d, 'country') ?: '—') . '</td>';
	echo '<td>' . $e($loc ?: '—') . '</td>';
	echo '<td>' . $mapa . '</td>';
	echo '<td title="' . $e($w['przegladarka']) . '">' . $e(przegladarka_czytelnie($w['przegladarka'])) . '</td>';
	echo '<td>' . $e($w['ekran'] ?? '—') . '</td>';
	echo '<td>' . $e($w['okno'] ?? '—') . '</td>';
	echo '<td>' . $e($w['kolory'] === null ? '—' : (string)$w['kolory']) . '</td>';
	echo '<td>' . $tak_nie($w['ciasteczka']) . '</td>';
	echo '<td>' . $tak_nie($w['java']) . '</td>';
	echo '<td>' . $e($w['jezyk'] ?? '—') . '</td>';
	echo '</tr>';
}
echo '</tbody></table></div>';

mysqli_close($link);
