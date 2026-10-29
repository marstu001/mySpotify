<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$id = (int)($_GET['id'] ?? 0);
$link = polacz_lub_zakoncz();
$p = $id > 0 ? pozycja($link, $id) : null;
$dostep = $p !== null && czy_moze_odtwarzac($link, $uid, $p);
mysqli_close($link);
if (!$dostep) {
	flash('danger', 'Nie ma takiego elementu albo nie masz do niego dostępu.');
	header('Location: moje.php');
	exit();
}

$tytul = $p['tytul'];
require 'gora.php';
?>
<p class="text-muted"><?php echo e(m('autor_l')); ?>: <strong><?php echo e($p['autor']); ?></strong> &middot; gatunek: <strong><?php echo e((string)$p['gatunek']); ?></strong> &middot; dodał(a): <strong><?php echo e($p['wlasciciel']); ?></strong> &middot; <?php echo e($p['data']); ?></p>
<div class="mb-3" style="max-width:900px;"><?php echo odtwarzacz($p, 'w-100'); ?></div>
<?php if ((string)$p['tekst'] !== '' && !ma_vtt($p['tekst'])): ?>
<h2 class="h5"><?php echo e(m('tekst_l')); ?></h2>
<pre class="tekst-pre"><?php echo e($p['tekst']); ?></pre>
<?php endif; ?>
<p><a class="btn btn-outline-secondary" href="moje.php"><i class="bi bi-arrow-left"></i> Wróć</a></p>
<?php require 'dol.php'; ?>
