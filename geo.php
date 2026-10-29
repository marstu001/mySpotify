<?php declare(strict_types=1);

function geo_http(string $url): array
{
	if (function_exists('curl_init')) {
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT        => 5,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (zadanie-3 geolokalizacja)',
			CURLOPT_HTTPHEADER     => ['Accept: application/json'],
		]);
		$tresc = curl_exec($ch);
		$kod   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$blad  = curl_error($ch);
		curl_close($ch);
		return [is_string($tresc) ? $tresc : null, $kod, $blad];
	}
	if (ini_get('allow_url_fopen')) {
		$ctx   = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true, 'header' => "Accept: application/json\r\nUser-Agent: zadanie-3\r\n"]]);
		$tresc = @file_get_contents($url, false, $ctx);
		$kod   = 0;
		if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
			$kod = (int)$m[1];
		}
		return [is_string($tresc) ? $tresc : null, $kod, $tresc === false ? 'file_get_contents nie powiodło się' : ''];
	}
	return [null, 0, 'brak cURL i allow_url_fopen=0'];
}

function geo_normalizuj(string $dostawca, ?object $d): ?object
{
	if ($d === null) {
		return null;
	}
	$w = new stdClass();
	if ($dostawca === 'ipinfo.io') {
		if (isset($d->bogon) || !isset($d->country)) {
			return null;
		}
		foreach (['ip', 'city', 'region', 'country', 'loc'] as $p) {
			$w->$p = isset($d->$p) && is_string($d->$p) ? $d->$p : '';
		}
	} elseif ($dostawca === 'ipwho.is') {
		if (empty($d->success)) {
			return null;
		}
		$w->ip      = (string)($d->ip ?? '');
		$w->city    = (string)($d->city ?? '');
		$w->region  = (string)($d->region ?? '');
		$w->country = (string)($d->country_code ?? '');
		$w->loc     = isset($d->latitude, $d->longitude) ? $d->latitude . ',' . $d->longitude : '';
	} else {
		if (($d->status ?? '') !== 'success') {
			return null;
		}
		$w->ip      = (string)($d->query ?? '');
		$w->city    = (string)($d->city ?? '');
		$w->region  = (string)($d->regionName ?? '');
		$w->country = (string)($d->countryCode ?? '');
		$w->loc     = isset($d->lat, $d->lon) ? $d->lat . ',' . $d->lon : '';
	}
	$w->zrodlo = $dostawca;
	return $w;
}

function ip_details(string $ip): ?object
{
	static $cache = [];
	if (array_key_exists($ip, $cache)) {
		return $cache[$ip];
	}
	$GLOBALS['geo_log'] = $GLOBALS['geo_log'] ?? [];
	$wynik = null;

	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
		$plik = rtrim(sys_get_temp_dir(), '/\\') . '/z3_geo_' . md5($ip) . '.json';
		if (is_file($plik)) {
			$wiek = time() - (int)@filemtime($plik);
			$dane = json_decode((string)@file_get_contents($plik));
			if (is_object($dane) && !empty($dane->country) && $wiek < 86400) {
				return $cache[$ip] = $dane;
			}
			if (is_object($dane) && isset($dane->brak) && $wiek < 600) {
				$GLOBALS['geo_log'][] = 'wcześniejsza próba nie powiodła się (pamięć podręczna 10 min)';
				return $cache[$ip] = null;
			}
		}

		$zrodla = [
			'ipinfo.io' => 'https://ipinfo.io/' . rawurlencode($ip) . '/geo',
			'ipwho.is'  => 'https://ipwho.is/' . rawurlencode($ip),
			'ip-api.com' => 'http://ip-api.com/json/' . rawurlencode($ip),
		];
		foreach ($zrodla as $nazwa => $url) {
			[$tresc, $kod, $blad] = geo_http($url);
			$GLOBALS['geo_log'][] = $nazwa . ': HTTP ' . $kod . ($blad !== '' ? ', błąd: ' . $blad : '') . ($tresc !== null ? ', odpowiedź ' . strlen($tresc) . ' B' : ', brak odpowiedzi');
			if ($tresc !== null && $tresc !== '') {
				$w = geo_normalizuj($nazwa, json_decode($tresc) instanceof stdClass ? json_decode($tresc) : null);
				if ($w !== null) {
					$wynik = $w;
					break;
				}
			}
		}
		@file_put_contents($plik, json_encode($wynik ?? ['brak' => 1]));
	}

	return $cache[$ip] = $wynik;
}

function geo_pole(?object $d, string $pole): string
{
	return ($d !== null && isset($d->$pole) && is_string($d->$pole)) ? $d->$pole : '';
}

function sprawdz_dostep_ip(string $ip): array
{
	if (in_array($ip, ZABLOKOWANE_IP, true)) {
		return [false, '', 'IP znajduje się na czarnej liście'];
	}
	$kraj = strtoupper(geo_pole(ip_details($ip), 'country'));
	if ($kraj === '') {
		return [true, '', 'nie udało się ustalić kraju (adres prywatny albo brak odpowiedzi usługi geolokalizacji) &ndash; dostęp dozwolony'];
	}
	if (!in_array($kraj, DOZWOLONE_KRAJE, true)) {
		return [false, $kraj, 'IP wskazuje na kraj spoza białej listy'];
	}
	return [true, $kraj, 'IP wskazuje na kraj z białej listy'];
}

function wizyta_token(int $id): string
{
	return hash_hmac('sha256', (string)$id, TAJNY_KLUCZ);
}

function zapisz_wizyte(mysqli $link): int
{
	$ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
	$ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);

	$stmt = mysqli_prepare($link, 'INSERT INTO goscieportalu (ipaddress, przegladarka) VALUES (?, ?)');
	if (!$stmt) {
		return 0;
	}
	mysqli_stmt_bind_param($stmt, 'ss', $ip, $ua);
	$ok = mysqli_stmt_execute($stmt);
	$id = $ok ? mysqli_insert_id($link) : 0;
	mysqli_stmt_close($stmt);
	return (int)$id;
}

function przegladarka_czytelnie(?string $ua): string
{
	if ($ua === null || trim($ua) === '') {
		return '—';
	}

	if (ini_get('browscap') && function_exists('get_browser')) {
		$b = @get_browser($ua, true);
		if (is_array($b) && !empty($b['browser']) && $b['browser'] !== 'Default Browser') {
			$tekst = $b['browser'] . (!empty($b['version']) && $b['version'] !== '0.0' ? ' ' . $b['version'] : '');
			if (!empty($b['platform']) && $b['platform'] !== 'unknown') {
				$tekst .= ' (' . $b['platform'] . ')';
			}
			return $tekst;
		}
	}

	if (preg_match('/(Googlebot|bingbot|DuckDuckBot|Baiduspider|YandexBot|AhrefsBot|SemrushBot|facebookexternalhit|[A-Za-z]*bot|crawler|spider)/i', $ua, $m)) {
		return 'Bot: ' . $m[1];
	}

	$wzorce = [
		'Edge'                    => '/Edg(?:e|A|iOS)?\/(\d+)/',
		'Opera'                   => '/(?:OPR|Opera)\/(\d+)/',
		'Vivaldi'                 => '/Vivaldi\/(\d+)/',
		'Samsung Internet'        => '/SamsungBrowser\/(\d+)/',
		'Firefox'                 => '/(?:Firefox|FxiOS)\/(\d+)/',
		'Chrome'                  => '/(?:Chrome|CriOS)\/(\d+)/',
		'Safari'                  => '/Version\/(\d+).*Safari/',
		'Internet Explorer'       => '/(?:MSIE |rv:)(\d+).*?(?:Trident|\))/',
	];
	$przegladarka = 'nieznana przeglądarka';
	foreach ($wzorce as $nazwa => $regex) {
		if (preg_match($regex, $ua, $m)) {
			$przegladarka = $nazwa . ' ' . $m[1];
			break;
		}
	}

	$system = '';
	if (preg_match('/Windows NT (\d+\.\d+)/', $ua, $m)) {
		$mapa = ['10.0' => 'Windows 10/11', '6.3' => 'Windows 8.1', '6.2' => 'Windows 8', '6.1' => 'Windows 7'];
		$system = $mapa[$m[1]] ?? 'Windows NT ' . $m[1];
	} elseif (preg_match('/Android (\d+(?:\.\d+)?)/', $ua, $m)) {
		$system = 'Android ' . $m[1];
	} elseif (preg_match('/(iPhone|iPad|iPod)/', $ua, $m)) {
		$system = 'iOS (' . $m[1] . ')';
	} elseif (strpos($ua, 'Mac OS X') !== false) {
		$system = 'macOS';
	} elseif (strpos($ua, 'CrOS') !== false) {
		$system = 'ChromeOS';
	} elseif (strpos($ua, 'Linux') !== false) {
		$system = 'Linux';
	}

	return $przegladarka . ($system !== '' ? ' (' . $system . ')' : '');
}
