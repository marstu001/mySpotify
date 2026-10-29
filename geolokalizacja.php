<?php declare(strict_types=1);
$tytul = 'Przybliżona geolokalizacja gościa na podstawie IP (ipinfo.io)';
require 'gora.php';
require_once 'geo.php';

$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$ipaddress = $_SERVER["REMOTE_ADDR"];
$details = ip_details($ipaddress);

if ($details === null) {
	echo '<p class="alert alert-warning">Nie udało się pobrać danych geolokalizacyjnych dla IP <strong>' . $e($ipaddress) . '</strong> '
	   . '(serwis ipinfo.io nie odpowiedział, albo hosting blokuje połączenia wychodzące, albo IP jest prywatne).</p>';
} else {
	echo 'Region: ' . $e(geo_pole($details, 'region')) . '<br />';
	echo 'Kraj: '   . $e(geo_pole($details, 'country')) . '<br />';
	echo 'Miasto: ' . $e(geo_pole($details, 'city')) . '<br />';
	echo 'Współrzędne (loc): ' . $e(geo_pole($details, 'loc')) . '<br />';
	echo 'IP: '     . $e(geo_pole($details, 'ip')) . '<br />';

	$loc = geo_pole($details, 'loc');
	if (preg_match('/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $loc)) {
		echo '<p><a href="https://www.google.pl/maps/place/' . $e($loc) . '" target="_blank" rel="noopener">Pokaż w Google Maps</a></p>';
	}
}

require 'dol.php';
