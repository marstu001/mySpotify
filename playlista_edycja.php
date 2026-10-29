<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$idpl = (int)($_GET['idpl'] ?? 0);
$link = polacz_lub_zakoncz();
$pl = $idpl > 0 ? playlista($link, $idpl) : null;
if ($pl === null || (int)$pl['idu'] !== $uid) {
	mysqli_close($link);
	flash('danger', 'Nie ma takiej playlisty albo nie należy do Ciebie.');
	header('Location: playlisty.php');
	exit();
}
$elementy = elementy_playlisty($link, $idpl);
$kandydaci = pozycje_do_dodania($link, $uid, $idpl);
mysqli_close($link);

$tytul = 'Edycja playlisty';
require 'gora.php';
flash_wypisz();
?>
<div class="row g-4">
	<div class="col-12 col-lg-5">
		<form method="post" action="playlista_akcja.php" class="mb-3">
			<?php echo csrf_pole(); ?>
			<input type="hidden" name="akcja" value="zmien">
			<input type="hidden" name="idpl" value="<?php echo $idpl; ?>">
			<label for="name" class="form-label">Nazwa playlisty</label>
			<input class="form-control mb-3" type="text" id="name" name="name" maxlength="150" value="<?php echo e($pl['name']); ?>" required>
			<div class="form-check form-switch mb-3">
				<input class="form-check-input" type="checkbox" role="switch" id="public" name="public" value="1"<?php echo (int)$pl['public'] === 1 ? ' checked' : ''; ?>>
				<label class="form-check-label" for="public">Playlista publiczna</label>
			</div>
			<button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Zapisz</button>
			<a class="btn btn-success" href="playlista.php?idpl=<?php echo $idpl; ?>"><i class="bi bi-play-fill"></i> Odtwórz</a>
		</form>
		<form method="post" action="playlista_akcja.php" onsubmit="return confirm('Usunąć całą playlistę?');">
			<?php echo csrf_pole(); ?>
			<input type="hidden" name="akcja" value="kasuj">
			<input type="hidden" name="idpl" value="<?php echo $idpl; ?>">
			<button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Usuń playlistę</button>
		</form>
	</div>
	<div class="col-12 col-lg-7">
		<h2 class="h5">Na playliście (<?php echo count($elementy); ?>)</h2>
<?php if (!$elementy): ?>
		<p class="text-muted">Playlista jest pusta.</p>
<?php else: ?>
		<ul class="list-group mb-4">
<?php foreach ($elementy as $i => $p): ?>
			<li class="list-group-item d-flex justify-content-between align-items-center">
				<span><span class="text-muted"><?php echo $i + 1; ?>.</span> <strong><?php echo e($p['tytul']); ?></strong> &ndash; <?php echo e($p['autor']); ?> <small class="text-muted">(<?php echo e($p['wlasciciel']); ?>)</small></span>
				<form method="post" action="playlista_akcja.php" class="m-0">
					<?php echo csrf_pole(); ?>
					<input type="hidden" name="akcja" value="usun">
					<input type="hidden" name="idpl" value="<?php echo $idpl; ?>">
					<input type="hidden" name="idpldb" value="<?php echo (int)$p['idpldb']; ?>">
					<button class="btn btn-sm btn-outline-danger" type="submit" title="Usuń z playlisty"><i class="bi bi-x-lg"></i></button>
				</form>
			</li>
<?php endforeach; ?>
		</ul>
<?php endif; ?>

		<h2 class="h5">Dodaj do playlisty</h2>
<?php if (!$kandydaci): ?>
		<p class="text-muted">Brak <?php echo e(m('dop_mn')); ?> do dodania: wszystkie Twoje i z publicznych playlist są już na tej liście.</p>
<?php else: ?>
		<form method="post" action="playlista_akcja.php">
			<?php echo csrf_pole(); ?>
			<input type="hidden" name="akcja" value="dodaj">
			<input type="hidden" name="idpl" value="<?php echo $idpl; ?>">
			<div class="list-group mb-3" style="max-height:320px; overflow:auto;">
<?php foreach ($kandydaci as $p): ?>
				<label class="list-group-item d-flex gap-2 align-items-center">
					<input class="form-check-input mt-0" type="checkbox" name="pozycje[]" value="<?php echo (int)$p['id']; ?>">
					<span><strong><?php echo e($p['tytul']); ?></strong> &ndash; <?php echo e($p['autor']); ?> <small class="text-muted">(<?php echo e($p['wlasciciel']); ?><?php echo (int)$p['idu'] === $uid ? ', Twój' : ', z publicznej playlisty'; ?>)</small></span>
				</label>
<?php endforeach; ?>
			</div>
			<button class="btn btn-success" type="submit"><i class="bi bi-plus-circle"></i> Dodaj zaznaczone</button>
		</form>
<?php endif; ?>
	</div>
</div>
<p class="mt-4"><a class="btn btn-outline-secondary" href="playlisty.php"><i class="bi bi-arrow-left"></i> Playlisty</a></p>
<?php require 'dol.php'; ?>
