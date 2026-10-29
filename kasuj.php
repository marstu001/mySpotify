<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();
csrf_wymagaj();

$id = (int)($_POST['id'] ?? 0);
$link = polacz_lub_zakoncz();
$p = $id > 0 ? pozycja($link, $id) : null;
if ($p === null || (int)$p['idu'] !== $uid) {
	mysqli_close($link);
	flash('danger', 'Nie ma takiego elementu albo nie należy do Ciebie.');
	header('Location: moje.php');
	exit();
}
$w = sql_wykonaj($link, 'DELETE FROM ' . m('tabela') . ' WHERE ' . m('id') . ' = ? AND idu = ?', 'ii', [$id, $uid]);
mysqli_close($link);
if ($w['ok'] && $w['ile'] > 0) {
	$katalog = katalog_uzytkownika($login);
	if ($katalog !== null) {
		$plik = $katalog . '/' . basename((string)$p['plik']);
		if (is_file($plik)) { @unlink($plik); }
	}
	flash('success', 'Usunięto „' . $p['tytul'] . '”.');
} else {
	flash('danger', 'Nie udało się usunąć.');
}
header('Location: moje.php');
exit();
