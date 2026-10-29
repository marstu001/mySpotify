<?php declare(strict_types=1);
session_start();
if (isset($_SESSION['loggedin']))
{
	header('Location: index4.php');
	exit();
}
$blad  = isset($_GET['blad']) ? $_GET['blad'] : '';
$login = isset($_GET['login']) ? $_GET['login'] : '';
$formularz = true;
$publiczna  = true;
require 'gora.php';
?>
		<form method="post" action="add.php" enctype="multipart/form-data">
			<h1 class="h3 mb-3 fw-normal">Rejestracja</h1>

			<?php if ($blad !== ''): ?>
			<div class="alert alert-danger" role="alert"><?php echo htmlentities($blad, ENT_QUOTES, "UTF-8"); ?></div>
			<?php endif; ?>

			<div class="form-floating">
				<input type="text" class="form-control" id="floatingInput" name="user" placeholder="login" maxlength="20" pattern="[A-Za-z0-9_\-]{1,20}" title="Litery bez polskich znaków, cyfry, _ lub -" value="<?php echo htmlentities($login, ENT_QUOTES, 'UTF-8'); ?>" required>
				<label for="floatingInput">Login</label>
			</div>
			<div class="form-floating">
				<input type="password" class="form-control srodek" id="floatingPassword" name="pass" placeholder="Hasło" maxlength="20" required>
				<label for="floatingPassword">Hasło</label>
			</div>
			<div class="form-floating">
				<input type="password" class="form-control" id="floatingPassword2" name="pass2" placeholder="Powtórz hasło" maxlength="20" required>
				<label for="floatingPassword2">Powtórz hasło</label>
			</div>

			<div class="form-text text-start mb-3">Login (do 20 znaków: litery bez polskich znaków, cyfry, <code>_</code>, <code>-</code>) jest też nazwą Twojego katalogu z plikami.</div>

			<div class="mb-3 text-start">
				<label for="avatar" class="form-label">Avatar (opcjonalnie)</label>
				<input class="form-control" type="file" id="avatar" name="avatar" accept="image/png, image/jpeg">
				<div class="form-text">PNG lub JPEG do 2 MB. Jeśli nie wybierzesz pliku, zostanie ikona domyślna &ndash; avatar ustawisz też później po zalogowaniu.</div>
			</div>

			<button class="w-100 btn btn-lg btn-primary" type="submit">Zarejestruj się</button>
			<p class="mt-3 mb-3">Masz już konto? <a href="index3.php">Zaloguj się</a></p>
		</form>
<?php require 'dol.php'; ?>
