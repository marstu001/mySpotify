<?php declare(strict_types=1);
$tytul = 'Rekordy DNS domeny pbs.edu.pl (dns_get_record)';
require 'gora.php';

$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$domena = 'pbs.edu.pl';

if (function_exists('dns_get_record')) {
	$result = @dns_get_record($domena);
	if (!$result) {
		$result = [];
		foreach (['A' => DNS_A, 'AAAA' => DNS_AAAA, 'NS' => DNS_NS, 'MX' => DNS_MX, 'SOA' => DNS_SOA, 'TXT' => DNS_TXT, 'CNAME' => DNS_CNAME] as $typ => $stala) {
			$r = @dns_get_record($domena, $stala);
			if ($r) {
				$result = array_merge($result, $r);
			}
		}
	}
	if ($result) {
		echo '<pre>' . $e(print_r($result, true)) . '</pre>';
	} else {
		echo '<p class="alert alert-warning">dns_get_record() nie zwróciło rekordów dla ' . $e($domena) . ' &ndash; resolver DNS tego hostingu nie odpowiada na takie zapytania albo ogranicza je. '
		   . 'Adres IP domeny udało się jednak ustalić funkcją gethostbyname(): <strong>' . $e(gethostbyname($domena)) . '</strong> (patrz <a href="ip_hosty.php">ip_hosty.php</a>).</p>';
		if (function_exists('checkdnsrr')) {
			echo '<p class="small text-muted">checkdnsrr: A = ' . (checkdnsrr($domena, 'A') ? 'tak' : 'nie')
			   . ', MX = ' . (checkdnsrr($domena, 'MX') ? 'tak' : 'nie')
			   . ', NS = ' . (checkdnsrr($domena, 'NS') ? 'tak' : 'nie') . '.</p>';
		}
	}
} else {
	echo '<p class="alert alert-warning">Funkcja dns_get_record() jest niedostępna na tym hostingu.</p>';
}

require 'dol.php';
