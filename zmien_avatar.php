<?php declare(strict_types=1);
session_start();
if (!isset($_SESSION['loggedin']))
{
	header('Location: index3.php');
	exit();
}
require_once 'db.php';
require_once 'avatar.php';

$login = (string)($_SESSION['username'] ?? '');
$link  = db_polacz();
if (!$link)
{
	exit('Błąd połączenia z bazą danych.');
}
avatar_upewnij_kolumne($link);

$pobierz = mysqli_prepare($link, 'SELECT avatar FROM users WHERE username = ?');
mysqli_stmt_bind_param($pobierz, 's', $login);
mysqli_stmt_execute($pobierz);
mysqli_stmt_bind_result($pobierz, $aktualny);
mysqli_stmt_fetch($pobierz);
mysqli_stmt_close($pobierz);

$zapisz = function (?string $sciezka) use ($link, $login): bool {
	$u = mysqli_prepare($link, 'UPDATE users SET avatar = ? WHERE username = ?');
	mysqli_stmt_bind_param($u, 'ss', $sciezka, $login);
	$ok = mysqli_stmt_execute($u);
	mysqli_stmt_close($u);
	return $ok;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$komunikat = '';
	$typ       = 'info';
	if (($_POST['akcja'] ?? '') === 'usun')
	{
		if ($zapisz(null)) {
			avatar_usun_plik($aktualny);
			$_SESSION['avatar'] = '';
			$komunikat = 'Avatar usunięty -- wróciła ikona domyślna.';
		} else {
			$typ = 'blad'; $komunikat = 'Nie udało się zmienić avatara.';
		}
	}
	else
	{
		[$nowy, $blad] = avatar_zapisz($_FILES['avatar'] ?? [], $login);
		if ($blad !== '') {
			$typ = 'blad'; $komunikat = $blad;
		} elseif ($nowy === null) {
			$typ = 'blad'; $komunikat = 'Nie wybrano pliku.';
		} elseif ($zapisz($nowy)) {
			avatar_usun_plik($aktualny);
			$_SESSION['avatar'] = $nowy;
			$komunikat = 'Avatar został zmieniony.';
		} else {
			avatar_usun_plik($nowy);
			$typ = 'blad'; $komunikat = 'Nie udało się zapisać avatara w bazie.';
		}
	}
	mysqli_close($link);
	header('Location: zmien_avatar.php?' . $typ . '=' . urlencode($komunikat));
	exit();
}
mysqli_close($link);

$info = $_GET['info'] ?? '';
$blad = $_GET['blad'] ?? '';
$tytul = 'Zmiana avatara';
require 'gora.php';
?>
<div class="row">
	<div class="col-12 col-md-8 col-lg-5">
		<?php if ($info !== ''): ?><div class="alert alert-success"><?php echo htmlentities($info, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
		<?php if ($blad !== ''): ?><div class="alert alert-danger"><?php echo htmlentities($blad, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

		<div class="card shadow-sm">
			<div class="card-body">
				<div class="d-flex align-items-center mb-3">
					<?php echo avatar_html($aktualny, 96); ?>
					<div class="ms-3">
						<div class="fw-semibold"><?php echo htmlentities($login, ENT_QUOTES, 'UTF-8'); ?></div>
						<div class="small text-muted"><?php echo $aktualny ? 'Własny avatar' : 'Ikona domyślna'; ?></div>
					</div>
				</div>

				<form method="post" action="zmien_avatar.php" enctype="multipart/form-data">
					<div class="mb-3">
						<label for="avatar" class="form-label">Nowy avatar</label>
						<input class="form-control" type="file" id="avatar" name="avatar" accept="image/png, image/jpeg" required>
						<div class="form-text">PNG lub JPEG do 2 MB.</div>
					</div>
					<button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> Zapisz avatar</button>
				</form>

				<?php if ($aktualny): ?>
				<form method="post" action="zmien_avatar.php" class="mt-3">
					<input type="hidden" name="akcja" value="usun">
					<button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-trash"></i> Usuń avatar (ikona domyślna)</button>
				</form>
				<?php endif; ?>
			</div>
		</div>
		<p class="mt-3"><a class="btn btn-outline-secondary btn-sm" href="index4.php"><i class="bi bi-arrow-left"></i> Strona główna portalu</a></p>
	</div>
</div>
<?php require 'dol.php'; ?>
