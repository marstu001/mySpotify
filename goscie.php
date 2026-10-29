<?php declare(strict_types=1);
require_once 'db.php';
require_once 'geo.php';

$wizytaId = 0;
$blad     = '';
$link = db_polacz();
if ($link) {
	$wizytaId = zapisz_wizyte($link);
	mysqli_close($link);
	if ($wizytaId === 0) {
		$blad = 'Nie udało się zapisać wizyty &ndash; czy tabela goscieportalu została utworzona (utworz_tabele.php)?';
	}
} else {
	$blad = 'Brak połączenia z bazą danych &ndash; sprawdź ustawienia w pliku db.php.';
}

$tytul = 'Rejestr wizyt gości portalu';
require 'gora.php';
?>
<?php if ($blad !== ''): ?>
	<p class="alert alert-warning"><?php echo $blad; ?></p>
<?php endif; ?>

<div id="tabela">Ładowanie tabeli&hellip;</div>
<noscript><p class="alert alert-warning">Bez JavaScriptu nie da się odczytać parametrów ekranu. Tabelę wizyt widać tutaj: <a href="goscie_tabela.php">goscie_tabela.php</a>.</p></noscript>

<script src="klient.js"></script>
<script>
	function pokazTabele() {
		fetch('goscie_tabela.php', { credentials: 'same-origin' })
			.then(function (r) { return r.text(); })
			.then(function (html) { document.getElementById('tabela').innerHTML = html; });
	}
	<?php if ($wizytaId > 0): ?>
	wyslijDaneKlienta(<?php echo $wizytaId; ?>, <?php echo json_encode(wizyta_token($wizytaId)); ?>).then(pokazTabele, pokazTabele);
	<?php else: ?>
	pokazTabele();
	<?php endif; ?>
</script>
<?php require 'dol.php'; ?>
