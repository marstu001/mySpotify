<?php declare(strict_types=1);
session_start();
if (!isset($_SESSION['loggedin']))
{
	header('Location: index3.php');
	exit();
}

$tytul = 'Rejestr wizyt (logowań) gości portalu';
require 'gora.php';
?>
<p><a class="btn btn-outline-secondary btn-sm" href="index4.php"><i class="bi bi-arrow-left"></i> Strona główna portalu</a></p>
<div id="tabela">Ładowanie tabeli&hellip;</div>
<script>
	fetch('goscie_tabela.php', { credentials: 'same-origin' })
		.then(function (r) { return r.text(); })
		.then(function (html) { document.getElementById('tabela').innerHTML = html; });
</script>
<?php require 'dol.php'; ?>
