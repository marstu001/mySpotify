<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
	flash('danger', 'Przesyłany plik jest większy niż limit serwera (' . ini_get('post_max_size') . ').');
	header('Location: select.php');
	exit();
}
csrf_wymagaj();

$blad = function (string $tekst): void {
	flash('danger', $tekst);
	header('Location: select.php');
	exit();
};

$tytul_p = trim((string)($_POST['title'] ?? ''));
$autor   = trim((string)($_POST['musician'] ?? ''));
$tekst   = trim((string)($_POST['lyrics'] ?? ''));
$idmt    = (int)($_POST['idmt'] ?? 0);

if ($tytul_p === '' || $autor === '') { $blad('Podaj tytuł i ' . mb_strtolower(m('autor_l')) . '.'); }
if (mb_strlen($tytul_p) > 200 || mb_strlen($autor) > 200) { $blad('Tytuł i ' . mb_strtolower(m('autor_l')) . ' mogą mieć do 200 znaków.'); }

$link = polacz_lub_zakoncz();
if (sql_jeden($link, 'SELECT 1 AS x FROM ' . m('gat_tabela') . ' WHERE ' . m('gat') . ' = ?', 'i', [$idmt]) === null) {
	mysqli_close($link);
	$blad('Wybierz gatunek z listy.');
}

$plik = $_FILES['fileToUpload'] ?? null;
$kody = [
	UPLOAD_ERR_INI_SIZE   => 'plik jest większy niż limit serwera (' . ini_get('upload_max_filesize') . ')',
	UPLOAD_ERR_FORM_SIZE  => 'plik jest za duży',
	UPLOAD_ERR_PARTIAL    => 'plik został przesłany tylko częściowo',
	UPLOAD_ERR_NO_FILE    => 'nie wybrano pliku',
	UPLOAD_ERR_NO_TMP_DIR => 'brak katalogu tymczasowego na serwerze',
	UPLOAD_ERR_CANT_WRITE => 'serwer nie może zapisać pliku na dysku',
	UPLOAD_ERR_EXTENSION  => 'przesyłanie zablokowane przez rozszerzenie PHP',
];
if (!$plik || (int)$plik['error'] !== UPLOAD_ERR_OK) {
	mysqli_close($link);
	$blad('Error uploading file: ' . ($kody[(int)($plik['error'] ?? UPLOAD_ERR_NO_FILE)] ?? 'nieznany błąd') . '.');
}

$nazwa = nazwa_pliku_czysta((string)$plik['name']);
if ($nazwa === '' || strtolower(pathinfo($nazwa, PATHINFO_EXTENSION)) !== m('roz')) {
	mysqli_close($link);
	$blad('Dozwolone są tylko pliki ' . m('roz') . '.');
}
if (!zawartosc_poprawna((string)$plik['tmp_name'])) {
	mysqli_close($link);
	$blad('Zawartość pliku nie wygląda na prawidłowy plik ' . m('roz') . '.');
}

$target_dir = katalog_uzytkownika($login);
if ($target_dir === null) {
	mysqli_close($link);
	$blad('Nie udało się utworzyć katalogu użytkownika na serwerze.');
}
$nazwa = wolna_nazwa($target_dir, $nazwa);
$target_file = $target_dir . '/' . $nazwa;
if (!move_uploaded_file((string)$plik['tmp_name'], $target_file)) {
	mysqli_close($link);
	$blad('Error uploading file.');
}
@chmod($target_file, 0644);

$w = sql_wykonaj($link,
	'INSERT INTO ' . m('tabela') . ' (' . m('tytul') . ', ' . m('autor') . ', idu, ' . m('plik') . ', ' . m('tekst') . ', ' . m('gat') . ') VALUES (?, ?, ?, ?, ?, ?)',
	'ssissi', [$tytul_p, $autor, $uid, $nazwa, $tekst === '' ? null : $tekst, $idmt]);
mysqli_close($link);
if (!$w['ok']) {
	@unlink($target_file);
	$blad('Nie udało się zapisać danych w bazie.');
}

flash('success', $nazwa . ' uploaded.');
header('Location: moje.php');
exit();
