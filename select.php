<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$link = polacz_lub_zakoncz();
$gat = gatunki($link);
mysqli_close($link);

$tytul = 'Dodaj ' . m('jedn');
require 'gora.php';
flash_wypisz();
?>
<form action="upload.php" method="post" enctype="multipart/form-data" class="mb-3" style="max-width:640px;">
	<?php echo csrf_pole(); ?>
	<div class="mb-3">
		<label for="title" class="form-label">Tytuł</label>
		<input class="form-control" type="text" id="title" name="title" maxlength="200" required>
	</div>
	<div class="mb-3">
		<label for="musician" class="form-label"><?php echo e(m('autor_l')); ?></label>
		<input class="form-control" type="text" id="musician" name="musician" maxlength="200" required>
	</div>
	<div class="mb-3">
		<label for="idmt" class="form-label">Gatunek</label>
		<select class="form-select" id="idmt" name="idmt" required>
			<option value="">&ndash; wybierz gatunek &ndash;</option>
<?php foreach ($gat as $g): ?>
			<option value="<?php echo (int)$g['id']; ?>"><?php echo e($g['nazwa']); ?></option>
<?php endforeach; ?>
		</select>
	</div>
	<div class="mb-3">
		<label for="lyrics" class="form-label"><?php echo e(m('tekst_l')); ?> (opcjonalnie)</label>
		<textarea class="form-control" id="lyrics" name="lyrics" rows="5"></textarea>
<?php if (m('napisy_vtt')): ?>
		<div class="form-text">Jeśli wkleisz napisy w formacie WebVTT (tekst zaczyna się od linii <code>WEBVTT</code>), będą wyświetlane na filmie jako napisy.</div>
<?php endif; ?>
	</div>
	<div class="mb-3">
		<label for="fileToUpload" class="form-label">Select file to upload (plik <?php echo e(m('roz')); ?>):</label>
		<input class="form-control" type="file" name="fileToUpload" id="fileToUpload" accept=".<?php echo e(m('roz')); ?>,<?php echo e(m('mime')); ?>" required>
		<div class="form-text">Limit serwera: <?php echo e((string)ini_get('upload_max_filesize')); ?> na plik.</div>
	</div>
	<button class="btn btn-primary" type="submit" name="submit" value="Upload"><i class="bi bi-cloud-upload"></i> Upload</button>
	<a class="btn btn-outline-secondary" href="moje.php">Anuluj</a>
</form>
<?php require 'dol.php'; ?>
