<?php declare(strict_types=1);
session_start();
if (!isset($_SESSION['loggedin']))
{
	header('Location: index3.php');
	exit();
}
require_once 'db.php';
require_once 'geo.php';
require_once 'media_cfg.php';

$wyslij = !empty($_SESSION['wizyta_id']) && empty($_SESSION['klient_wyslany']);
if ($wyslij) { $_SESSION['klient_wyslany'] = true; }

$tytul = MEDIA['nazwa'] . ' – strona główna';
require 'gora.php';
?>
<p>Jestem zalogowany jako <strong><?php echo htmlentities((string)($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
<p class="d-flex flex-wrap gap-2">
	<a class="btn btn-primary" href="select.php"><i class="bi bi-cloud-upload"></i> Dodaj <?php echo htmlspecialchars(MEDIA['jedn'], ENT_QUOTES, 'UTF-8'); ?></a>
	<a class="btn btn-primary" href="moje.php"><i class="bi <?php echo MEDIA['ikona']; ?>"></i> <?php echo htmlspecialchars(MEDIA['moje'], ENT_QUOTES, 'UTF-8'); ?></a>
	<a class="btn btn-primary" href="playlisty.php"><i class="bi bi-collection-play"></i> Playlisty</a>
	<a class="btn btn-outline-primary" href="nowa_playlista.php"><i class="bi bi-plus-circle"></i> Nowa playlista</a>
	<a class="btn btn-outline-primary" href="goscie_podglad.php"><i class="bi bi-journal-check"></i> Rejestr wizyt</a>
	<a class="btn btn-outline-primary" href="zmien_avatar.php"><i class="bi bi-image"></i> Zmień avatar</a>
	<a class="btn btn-outline-secondary" href="logout.php"><i class="bi bi-box-arrow-right"></i> Wyloguj</a>
</p>
<?php if ($wyslij): ?>
<script src="klient.js"></script>
<script>wyslijDaneKlienta(<?php echo (int)$_SESSION['wizyta_id']; ?>, <?php echo json_encode(wizyta_token((int)$_SESSION['wizyta_id'])); ?>);</script>
<?php endif; ?>
<?php require 'dol.php'; ?>
