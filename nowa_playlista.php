<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$link = polacz_lub_zakoncz();
$kandydaci = pozycje_do_dodania($link, $uid);
$blad = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	csrf_wymagaj();
	$nazwa = trim((string)($_POST['name'] ?? ''));
	$publiczna = isset($_POST['public']) ? 1 : 0;
	$wybrane = array_map('intval', (array)($_POST['pozycje'] ?? []));
	if ($nazwa === '' || mb_strlen($nazwa) > 150) {
		$blad = 'Podaj nazwę playlisty (do 150 znaków).';
	} else {
		$w = sql_wykonaj($link, 'INSERT INTO playlistname (idu, name, public) VALUES (?, ?, ?)', 'isi', [$uid, $nazwa, $publiczna]);
		if (!$w['ok']) {
			$blad = 'Nie udało się zapisać playlisty.';
		} else {
			$idpl = $w['id'];
			$dozwolone = array_map(fn($p) => (int)$p['id'], $kandydaci);
			$dodano = 0;
			foreach (array_unique($wybrane) as $id) {
				if (in_array($id, $dozwolone, true)) {
					$dodano += sql_wykonaj($link, 'INSERT INTO playlistdatabase (idpl, ' . m('id') . ') VALUES (?, ?)', 'ii', [$idpl, $id])['ile'];
				}
			}
			mysqli_close($link);
			flash('success', 'Utworzono playlistę „' . $nazwa . '” (' . ($publiczna ? 'publiczna' : 'prywatna') . ', ' . $dodano . ' ' . liczba_pl($dodano, m('jedn'), m('mn'), m('dop_mn')) . ').');
			header('Location: playlista_edycja.php?idpl=' . $idpl);
			exit();
		}
	}
}
mysqli_close($link);

$tytul = 'Nowa playlista';
require 'gora.php';
if ($blad !== '') { echo '<div class="alert alert-danger">' . e($blad) . '</div>'; }
?>
<form method="post" action="nowa_playlista.php" style="max-width:760px;">
	<?php echo csrf_pole(); ?>
	<div class="mb-3">
		<label for="name" class="form-label">Nazwa playlisty</label>
		<input class="form-control" type="text" id="name" name="name" maxlength="150" value="<?php echo e((string)($_POST['name'] ?? '')); ?>" required autofocus>
	</div>
	<div class="form-check form-switch mb-3">
		<input class="form-check-input" type="checkbox" role="switch" id="public" name="public" value="1"<?php echo isset($_POST['public']) ? ' checked' : ''; ?>>
		<label class="form-check-label" for="public">Playlista publiczna (widoczna dla wszystkich użytkowników; domyślnie prywatna)</label>
	</div>
<?php if ($kandydaci): ?>
	<h2 class="h6">Dodaj do playlisty (opcjonalnie)</h2>
	<div class="list-group mb-3" style="max-height:340px; overflow:auto;">
<?php foreach ($kandydaci as $p): ?>
		<label class="list-group-item d-flex gap-2 align-items-center">
			<input class="form-check-input mt-0" type="checkbox" name="pozycje[]" value="<?php echo (int)$p['id']; ?>">
			<span><strong><?php echo e($p['tytul']); ?></strong> &ndash; <?php echo e($p['autor']); ?> <small class="text-muted">(<?php echo e($p['wlasciciel']); ?>)</small></span>
		</label>
<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-muted">Nie masz jeszcze <?php echo e(m('dop_mn')); ?> do dodania. Możesz je dodać później.</p>
<?php endif; ?>
	<button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Utwórz playlistę</button>
	<a class="btn btn-outline-secondary" href="playlisty.php">Anuluj</a>
</form>
<?php require 'dol.php'; ?>
