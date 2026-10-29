<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();
csrf_wymagaj();

$akcja = (string)($_POST['akcja'] ?? '');
$idpl  = (int)($_POST['idpl'] ?? 0);
$wroc  = (string)($_POST['wroc'] ?? '');
$wroc  = $wroc === 'moje.php' ? 'moje.php' : 'playlista_edycja.php?idpl=' . $idpl;

$link = polacz_lub_zakoncz();
$pl = $idpl > 0 ? playlista($link, $idpl) : null;
if ($pl === null || (int)$pl['idu'] !== $uid) {
	mysqli_close($link);
	flash('danger', 'Nie ma takiej playlisty albo nie należy do Ciebie.');
	header('Location: playlisty.php');
	exit();
}

if ($akcja === 'dodaj') {
	$wybrane = (array)($_POST['pozycje'] ?? []);
	if (isset($_POST['pozycja'])) { $wybrane[] = $_POST['pozycja']; }
	$dozwolone = array_map(fn($p) => (int)$p['id'], pozycje_do_dodania($link, $uid, $idpl));
	$dodano = 0;
	foreach (array_unique(array_map('intval', $wybrane)) as $id) {
		if (in_array($id, $dozwolone, true)) {
			$dodano += sql_wykonaj($link, 'INSERT INTO playlistdatabase (idpl, ' . m('id') . ') VALUES (?, ?)', 'ii', [$idpl, $id])['ile'];
		}
	}
	flash($dodano ? 'success' : 'warning', $dodano ? 'Dodano do playlisty „' . $pl['name'] . '”: ' . $dodano . '.' : 'Nic nie dodano (element jest już na playliście albo nie masz do niego dostępu).');
} elseif ($akcja === 'usun') {
	$idpldb = (int)($_POST['idpldb'] ?? 0);
	sql_wykonaj($link, 'DELETE FROM playlistdatabase WHERE idpldb = ? AND idpl = ?', 'ii', [$idpldb, $idpl]);
	flash('success', 'Usunięto z playlisty.');
} elseif ($akcja === 'zmien') {
	$nazwa = trim((string)($_POST['name'] ?? ''));
	$publiczna = isset($_POST['public']) ? 1 : 0;
	if ($nazwa === '' || mb_strlen($nazwa) > 150) {
		flash('danger', 'Podaj nazwę playlisty (do 150 znaków).');
	} else {
		sql_wykonaj($link, 'UPDATE playlistname SET name = ?, public = ? WHERE idpl = ? AND idu = ?', 'siii', [$nazwa, $publiczna, $idpl, $uid]);
		flash('success', 'Zapisano zmiany playlisty.');
	}
} elseif ($akcja === 'kasuj') {
	sql_wykonaj($link, 'DELETE FROM playlistname WHERE idpl = ? AND idu = ?', 'ii', [$idpl, $uid]);
	mysqli_close($link);
	flash('success', 'Usunięto playlistę „' . $pl['name'] . '”.');
	header('Location: playlisty.php');
	exit();
}
mysqli_close($link);
header('Location: ' . $wroc);
exit();
