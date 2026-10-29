<?php declare(strict_types=1);
$tytul = 'Diagnoza geolokalizacji (połączenia wychodzące z serwera)';
require 'gora.php';
require_once 'geo.php';
$e  = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
?>
<table class="table table-bordered table-striped table-sm align-middle w-auto">
	<tr><th>cURL</th><td><?php echo function_exists('curl_init') ? 'dostępny' : 'brak'; ?></td></tr>
	<tr><th>allow_url_fopen</th><td><?php echo ini_get('allow_url_fopen') ? 'włączone' : 'wyłączone'; ?></td></tr>
	<tr><th>Twoje IP</th><td><?php echo $e($ip); ?></td></tr>
</table>
<h3 class="h5 mt-4">Odpowiedzi serwisów</h3>
<?php
foreach (['ipinfo.io' => 'https://ipinfo.io/' . rawurlencode($ip) . '/geo', 'ipwho.is' => 'https://ipwho.is/' . rawurlencode($ip), 'ip-api.com' => 'http://ip-api.com/json/' . rawurlencode($ip)] as $nazwa => $url) {
	[$tresc, $kod, $blad] = geo_http($url);
	echo '<p><strong>' . $e($nazwa) . '</strong> &ndash; HTTP ' . $kod . ($blad !== '' ? ', błąd: ' . $e($blad) : '') . '</p>';
	echo '<pre>' . $e($tresc === null ? '(brak odpowiedzi)' : mb_substr($tresc, 0, 500)) . '</pre>';
}
require 'dol.php';
