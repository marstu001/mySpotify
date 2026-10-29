<?php declare(strict_types=1);
$tytul = 'Lista plików z prawami w katalogu zadania (ls -al)';
require 'gora.php';

if (function_exists('shell_exec')) {
	$wynik = shell_exec('ls -al ' . escapeshellarg(__DIR__) . ' 2>&1');
	if ($wynik !== null && $wynik !== '') {
		echo '<pre>' . htmlspecialchars($wynik, ENT_QUOTES, 'UTF-8') . '</pre>';
	} else {
		echo '<p class="alert alert-warning">Polecenie ls nie zwróciło wyniku (może być zablokowane na tym hostingu).</p>';
	}
} else {
	echo '<p class="alert alert-warning">Funkcja shell_exec() jest wyłączona na tym hostingu (disable_functions) &ndash; to celowe zabezpieczenie, skrypt nie może zostać wykonany.</p>';
}

require 'dol.php';
