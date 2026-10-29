<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$link = polacz_lub_zakoncz();
$lista = moje_pozycje($link, $uid);
$playlisty = sql_wiersze($link, 'SELECT idpl, name FROM playlistname WHERE idu = ? ORDER BY name', 'i', [$uid]);
mysqli_close($link);

$tytul = m('moje');
require 'gora.php';
flash_wypisz();
?>
<p>
	<a class="btn btn-primary" href="select.php"><i class="bi bi-cloud-upload"></i> Dodaj <?php echo e(m('jedn')); ?></a>
	<a class="btn btn-outline-primary" href="playlisty.php"><i class="bi bi-collection-play"></i> Playlisty</a>
</p>
<?php if (!$lista): ?>
<p class="text-muted">Nie dodałeś jeszcze żadnych <?php echo e(m('dop_mn')); ?>.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($lista as $p): ?>
	<div class="col-12 col-lg-6">
		<div class="card h-100">
			<div class="card-body">
				<h2 class="h5 mb-0"><i class="bi <?php echo e(m('ikona')); ?>"></i> <?php echo e($p['tytul']); ?></h2>
				<div class="text-muted small mb-2"><?php echo e($p['autor']); ?> &middot; <span class="badge bg-secondary"><?php echo e((string)$p['gatunek']); ?></span> &middot; <?php echo e($p['data']); ?></div>
				<?php echo odtwarzacz($p, 'w-100'); ?>
<?php if ((string)$p['tekst'] !== '' && !ma_vtt($p['tekst'])): ?>
				<details class="mt-2"><summary><?php echo e(m('tekst_l')); ?></summary><pre class="mt-2 mb-0 tekst-pre"><?php echo e($p['tekst']); ?></pre></details>
<?php endif; ?>
			</div>
			<div class="card-footer d-flex flex-wrap gap-2 align-items-center">
				<a class="btn btn-sm btn-outline-secondary" href="odtwarzaj.php?id=<?php echo (int)$p['id']; ?>"><i class="bi bi-play-circle"></i> Odtwórz</a>
<?php if ($playlisty): ?>
				<form method="post" action="playlista_akcja.php" class="d-flex gap-1 m-0">
					<?php echo csrf_pole(); ?>
					<input type="hidden" name="akcja" value="dodaj">
					<input type="hidden" name="pozycja" value="<?php echo (int)$p['id']; ?>">
					<input type="hidden" name="wroc" value="moje.php">
					<select name="idpl" class="form-select form-select-sm" required>
<?php foreach ($playlisty as $pl): ?>
						<option value="<?php echo (int)$pl['idpl']; ?>"><?php echo e($pl['name']); ?></option>
<?php endforeach; ?>
					</select>
					<button class="btn btn-sm btn-outline-success text-nowrap" type="submit"><i class="bi bi-plus-circle"></i> Do playlisty</button>
				</form>
<?php endif; ?>
				<form method="post" action="kasuj.php" class="ms-auto m-0" onsubmit="return confirm(<?php echo e(json_encode('Usunąć „' . $p['tytul'] . '”?', JSON_UNESCAPED_UNICODE)); ?>);">
					<?php echo csrf_pole(); ?>
					<input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
					<button class="btn btn-sm btn-outline-danger" type="submit" title="Usuń"><i class="bi bi-trash"></i></button>
				</form>
			</div>
		</div>
	</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require 'dol.php'; ?>
