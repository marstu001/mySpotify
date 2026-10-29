<?php declare(strict_types=1);
$tytul = 'Goście portalu z różnych IP, od najczęściej odwiedzających (wariant c)';
require 'gora.php';
?>
<div id="tabela">Ładowanie tabeli&hellip;</div>
<noscript><p class="uwaga">Tabela ładuje się przez JavaScript: <a href="goscie_tabela.php?tryb=ranking">goscie_tabela.php?tryb=ranking</a>.</p></noscript>
<script>
	fetch('goscie_tabela.php?tryb=ranking', { credentials: 'same-origin' })
		.then(function (r) { return r.text(); })
		.then(function (html) { document.getElementById('tabela').innerHTML = html; });
</script>
<?php require 'dol.php'; ?>
