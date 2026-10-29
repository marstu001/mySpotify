<?php declare(strict_types=1);
$tytul = 'Nazwa użytkownika serwera (whoami)';
require 'gora.php';

if (function_exists('exec')) {
	$wynik = exec('whoami');
	if ($wynik !== '' && $wynik !== false) {
		echo '<p>Serwer działa jako użytkownik: <strong>' . htmlspecialchars($wynik, ENT_QUOTES, 'UTF-8') . '</strong></p>';
	} else {
		echo '<p class="alert alert-warning">Polecenie whoami nie zwróciło wyniku (może być zablokowane na tym hostingu).</p>';
	}
} else {
	echo '<p class="alert alert-warning">Funkcja exec() jest wyłączona na tym hostingu (disable_functions) &ndash; to celowe zabezpieczenie, skrypt nie może zostać wykonany.</p>';
}

require 'dol.php';
