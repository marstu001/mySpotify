<?php declare(strict_types=1);
$tytul = 'Obciążenie serwera i lista procesów (top)';
require 'gora.php';

$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$top = [];
$blad = 1;
if (function_exists('exec')) {
	exec('TERM=xterm top -b -n 1 2>&1', $top, $blad);
}

if ($blad === 0 && count($top) > 0) {
	echo '<pre>' . $e(implode("\n", $top)) . '</pre>';
} else {
	echo '<p class="alert alert-warning">Polecenie <code>top</code> jest zablokowane na tym hostingu'
	   . ($top ? ' (odpowiedź serwera: <code>' . $e(trim(implode(' ', $top))) . '</code>)' : '')
	   . '. Poniżej te same dane odczytane z plików systemowych <code>/proc</code>, z których korzysta top.</p>';

	$load = @file_get_contents('/proc/loadavg');
	if ($load !== false) {
		$p = explode(' ', trim($load));
		echo '<h3 class="h5 mt-4">Obciążenie (load average)</h3><p>1 min: <strong>' . $e($p[0]) . '</strong>, 5 min: <strong>' . $e($p[1]) . '</strong>, 15 min: <strong>' . $e($p[2]) . '</strong>, procesy uruchomione/wszystkie: ' . $e($p[3]) . '</p>';
	}
	$up = @file_get_contents('/proc/uptime');
	if ($up !== false) {
		$sek = (int)explode(' ', $up)[0];
		echo '<p>Czas pracy serwera: <strong>' . intdiv($sek, 86400) . ' dni, ' . intdiv($sek % 86400, 3600) . ' godz., ' . intdiv($sek % 3600, 60) . ' min</strong></p>';
	}
	$mem = @file_get_contents('/proc/meminfo');
	if ($mem !== false && preg_match_all('/^(MemTotal|MemFree|MemAvailable|SwapTotal|SwapFree):\s+(\d+) kB/m', $mem, $m, PREG_SET_ORDER)) {
		echo '<h3 class="h5 mt-4">Pamięć</h3><table class="table table-bordered table-striped table-sm align-middle w-auto">';
		foreach ($m as $w) {
			echo '<tr><th>' . $e($w[1]) . '</th><td>' . number_format((int)$w[2] / 1024, 0, ',', ' ') . ' MB</td></tr>';
		}
		echo '</table>';
	}

	$lista = [];
	foreach (@scandir('/proc') ?: [] as $pid) {
		if (!ctype_digit($pid)) { continue; }
		$stat = @file_get_contents("/proc/$pid/stat");
		if ($stat === false || !preg_match('/^\d+ \((.*)\) (\S) /s', $stat, $mm)) { continue; }
		$pola = explode(' ', substr($stat, strrpos($stat, ')') + 2));
		$lista[] = ['pid' => (int)$pid, 'nazwa' => $mm[1], 'stan' => $mm[2], 'rss' => (int)($pola[21] ?? 0) * 4];
	}
	usort($lista, fn($a, $b) => $b['rss'] <=> $a['rss']);
	echo '<h3 class="h5 mt-4">Procesy widoczne dla tego konta (' . count($lista) . '), od największego zużycia pamięci</h3><table class="table table-bordered table-striped table-sm align-middle w-auto"><tr><th>PID</th><th>Nazwa</th><th>Stan</th><th>Pamięć (MB)</th></tr>';
	foreach (array_slice($lista, 0, 30) as $w) {
		echo '<tr><td>' . $w['pid'] . '</td><td>' . $e($w['nazwa']) . '</td><td>' . $e($w['stan']) . '</td><td>' . number_format($w['rss'] / 1024, 1, ',', ' ') . '</td></tr>';
	}
	echo '</table>';
}

require 'dol.php';
