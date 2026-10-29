<?php declare(strict_types=1);
$tytul = 'Geolokalizacja z lokalnej bazy GeoIP (plik .mmdb)';
require 'gora.php';
require_once 'geo.php';
require_once 'maxmind_db_reader.php';

$e = fn(?string $s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

$pliki = glob(__DIR__ . '/*.mmdb') ?: [];
if (!$pliki) {
	echo '<p class="alert alert-warning">Brak bazy GeoIP. Wgraj do katalogu tego zadania plik <code>*.mmdb</code>, np.:</p><ul>'
	   . '<li><b>DB-IP City Lite</b> (darmowa, bez rejestracji, licencja CC-BY 4.0): <a href="https://db-ip.com/db/download/ip-to-city-lite" target="_blank" rel="noopener">db-ip.com/db/download/ip-to-city-lite</a> &ndash; pobierz wersję <i>mmdb</i>, rozpakuj .gz</li>'
	   . '<li><b>GeoLite2 City</b> (darmowa, wymaga konta MaxMind): <a href="https://dev.maxmind.com/geoip/geolite2-free-geolocation-data" target="_blank" rel="noopener">dev.maxmind.com</a></li></ul>';
	require 'dol.php';
	exit;
}
$plik = $pliki[0];

try {
	$reader = new MaxMind\Db\Reader($plik);
} catch (Throwable $ex) {
	echo '<p class="alert alert-warning">Nie udało się otworzyć bazy: ' . $e($ex->getMessage()) . '</p>';
	require 'dol.php';
	exit;
}
$meta = $reader->metadata();
$opis = is_array($meta->description ?? null) ? (string)(reset($meta->description) ?: '') : '';
$jestDbIp = stripos($meta->databaseType . ' ' . $opis . ' ' . basename($plik), 'dbip') !== false
         || stripos($meta->databaseType . ' ' . $opis . ' ' . basename($plik), 'db-ip') !== false;

function geoip_szukaj(MaxMind\Db\Reader $r, string $ip): array
{
	if (!filter_var($ip, FILTER_VALIDATE_IP)) {
		return ['blad' => 'To nie jest poprawny adres IP.'];
	}
	try {
		$rek = $r->get($ip);
	} catch (Throwable $ex) {
		return ['blad' => $ex->getMessage()];
	}
	if (!is_array($rek)) {
		return ['blad' => 'Adres nie występuje w bazie (np. prywatny lub zarezerwowany).'];
	}
	$nazwa = function ($v): string {
		return is_array($v) ? (string)($v['names']['en'] ?? '') : '';
	};
	$lat = $rek['location']['latitude'] ?? null;
	$lon = $rek['location']['longitude'] ?? null;
	return [
		'miasto'  => $nazwa($rek['city'] ?? null),
		'region'  => $nazwa($rek['subdivisions'][0] ?? null),
		'kraj'    => $nazwa($rek['country'] ?? null),
		'kod'     => (string)($rek['country']['iso_code'] ?? ''),
		'loc'     => ($lat !== null && $lon !== null) ? $lat . ',' . $lon : '',
	];
}

function geoip_wiersz(MaxMind\Db\Reader $r, string $ip, ?object $ipinfo = null, bool $porownaj = false): string
{
	$e = fn(?string $s): string => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	$w = geoip_szukaj($r, $ip);
	if (isset($w['blad'])) {
		return '<tr><td>' . $e($ip) . '</td><td colspan="' . ($porownaj ? 7 : 5) . '">' . $e($w['blad']) . '</td></tr>';
	}
	$mapa = preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $w['loc'])
		? '<a href="https://www.google.pl/maps/place/' . $e($w['loc']) . '" target="_blank" rel="noopener">LINK</a>' : '—';
	$s = '<tr><td>' . $e($ip) . '</td><td>' . $e($w['miasto'] ?: '—') . '</td><td>' . $e($w['region'] ?: '—')
	   . '</td><td>' . $e(trim($w['kraj'] . ' ' . ($w['kod'] ? '(' . $w['kod'] . ')' : '')) ?: '—')
	   . '</td><td>' . $e($w['loc'] ?: '—') . '</td><td>' . $mapa . '</td>';
	if ($porownaj) {
		$s .= '<td>' . ($ipinfo ? $e(trim(geo_pole($ipinfo, 'city') . ', ' . geo_pole($ipinfo, 'country'), ', ')) : 'brak (ipinfo.io niedostępne)') . '</td>';
	}
	return $s . '</tr>';
}

$naglowek = '<tr><th>IP</th><th>Miasto</th><th>Region</th><th>Kraj</th><th>Współrzędne</th><th>Mapa</th>';
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
?>
<h3 class="h5 mt-4">Baza</h3>
<table class="table table-bordered table-striped table-sm align-middle w-auto">
	<tr><th>Plik</th><td><?php echo $e(basename($plik)); ?></td></tr>
	<tr><th>Typ bazy</th><td><?php echo $e($meta->databaseType); ?></td></tr>
	<tr><th>Wersja IP</th><td>IPv<?php echo (int)$meta->ipVersion; ?></td></tr>
	<tr><th>Data budowy</th><td><?php echo $e(gmdate('Y-m-d', (int)$meta->buildEpoch)); ?></td></tr>
	<tr><th>Liczba węzłów</th><td><?php echo (int)$meta->nodeCount; ?></td></tr>
</table>

<h3 class="h5 mt-4">Twoje IP</h3>
<table class="table table-bordered table-striped table-sm align-middle w-auto">
	<?php echo $naglowek . '<th>ipinfo.io (porównanie)</th></tr>'; ?>
	<?php echo geoip_wiersz($reader, $ip, ip_details($ip), true); ?>
</table>
<p class="small text-muted">Dla adresów lokalnych (127.0.0.1, 192.168.x.x) żadna baza nie zna położenia. Porównanie z ipinfo.io pokazuje, że różne bazy podają nieco inne miasta.</p>

<h3 class="h5 mt-4">Sprawdź dowolne IP</h3>
<form class="row g-2 mb-3" method="get" action="geoip.php">
	<div class="col-12 col-md-6 col-lg-4"><input class="form-control" type="text" name="ip" maxlength="45" placeholder="np. 8.8.8.8 lub 2001:4860:4860::8888" value="<?php echo $e($_GET['ip'] ?? ''); ?>"></div>
	<div class="col-auto"><button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Szukaj</button></div>
</form>
<?php
$podane = trim((string)($_GET['ip'] ?? ''));
if ($podane !== '') {
	echo '<table class="table table-bordered table-striped table-sm align-middle w-auto">' . $naglowek . '</tr>' . geoip_wiersz($reader, $podane) . '</table>';
}
?>
<h3 class="h5 mt-4">Przykładowe adresy</h3>
<table class="table table-bordered table-striped table-sm align-middle w-auto">
	<?php echo $naglowek . '</tr>'; ?>
	<?php foreach (['8.8.8.8', '1.1.1.1', '208.67.222.222', '194.29.178.1', '2001:4860:4860::8888'] as $p) { echo geoip_wiersz($reader, $p); } ?>
</table>
<?php if ($jestDbIp): ?>
<p class="small text-muted">Dane geolokalizacyjne: <a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a> (licencja CC-BY 4.0).</p>
<?php endif; ?>
<?php $reader->close(); require 'dol.php'; ?>
