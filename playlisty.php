<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$link = polacz_lub_zakoncz();
$sql = 'SELECT n.idpl, n.name, n.public, n.`datetime` AS data, n.idu, u.username AS wlasciciel, (SELECT COUNT(*) FROM playlistdatabase d WHERE d.idpl = n.idpl) AS ile FROM playlistname n JOIN users u ON u.id = n.idu';
$moje     = sql_wiersze($link, $sql . ' WHERE n.idu = ? ORDER BY n.name', 'i', [$uid]);
$publiczne = sql_wiersze($link, $sql . ' WHERE n.public = 1 AND n.idu <> ? ORDER BY u.username, n.name', 'i', [$uid]);
mysqli_close($link);

$tytul = 'Playlisty';
require 'gora.php';
flash_wypisz();
?>
<p><a class="btn btn-primary" href="nowa_playlista.php"><i class="bi bi-plus-circle"></i> Nowa playlista</a></p>

<h2 class="h5">Moje playlisty</h2>
<?php if (!$moje): ?>
<p class="text-muted">Nie masz jeszcze żadnej playlisty.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover align-middle">
	<thead><tr><th>Nazwa</th><th>Widoczność</th><th>Liczba</th><th>Utworzona</th><th class="text-end">Akcje</th></tr></thead>
	<tbody>
<?php foreach ($moje as $pl): ?>
		<tr>
			<td><i class="bi bi-collection-play"></i> <a href="playlista.php?idpl=<?php echo (int)$pl['idpl']; ?>"><?php echo e($pl['name']); ?></a></td>
			<td><?php echo (int)$pl['public'] === 1 ? '<span class="badge bg-success">publiczna</span>' : '<span class="badge bg-secondary">prywatna</span>'; ?></td>
			<td><?php echo (int)$pl['ile']; ?></td>
			<td><?php echo e($pl['data']); ?></td>
			<td class="text-end text-nowrap">
				<a class="btn btn-sm btn-outline-success" href="playlista.php?idpl=<?php echo (int)$pl['idpl']; ?>"><i class="bi bi-play-fill"></i> Odtwórz</a>
				<a class="btn btn-sm btn-outline-primary" href="playlista_edycja.php?idpl=<?php echo (int)$pl['idpl']; ?>"><i class="bi bi-pencil"></i> Edytuj</a>
			</td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<?php endif; ?>

<h2 class="h5 mt-4">Publiczne playlisty innych użytkowników</h2>
<?php if (!$publiczne): ?>
<p class="text-muted">Nikt jeszcze nie udostępnił publicznej playlisty.</p>
<?php else: ?>
<div class="table-responsive"><table class="table table-hover align-middle">
	<thead><tr><th>Nazwa</th><th>Właściciel</th><th>Liczba</th><th>Utworzona</th><th class="text-end">Akcje</th></tr></thead>
	<tbody>
<?php foreach ($publiczne as $pl): ?>
		<tr>
			<td><i class="bi bi-collection-play"></i> <a href="playlista.php?idpl=<?php echo (int)$pl['idpl']; ?>"><?php echo e($pl['name']); ?></a></td>
			<td><i class="bi bi-person"></i> <?php echo e($pl['wlasciciel']); ?></td>
			<td><?php echo (int)$pl['ile']; ?></td>
			<td><?php echo e($pl['data']); ?></td>
			<td class="text-end"><a class="btn btn-sm btn-outline-success" href="playlista.php?idpl=<?php echo (int)$pl['idpl']; ?>"><i class="bi bi-play-fill"></i> Odtwórz</a></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table></div>
<?php endif; ?>
<?php require 'dol.php'; ?>
