<?php declare(strict_types=1);
session_start();
if (isset($_SESSION['loggedin']))
{
	header('Location: index4.php');
	exit();
}
$blad = isset($_GET['blad']) ? $_GET['blad'] : '';
$info = isset($_GET['info']) ? $_GET['info'] : '';
$formularz = true;
$publiczna  = true;
require 'gora.php';
?>
		<form method="post" action="weryfikuj3.php">
			<h1 class="h3 mb-3 fw-normal">Zaloguj się</h1>

			<?php if ($info !== ''): ?>
			<div class="alert alert-success" role="alert"><?php echo htmlentities($info, ENT_QUOTES, "UTF-8"); ?></div>
			<?php endif; ?>
			<?php if ($blad !== ''): ?>
			<div class="alert alert-danger" role="alert"><?php echo htmlentities($blad, ENT_QUOTES, "UTF-8"); ?></div>
			<?php endif; ?>

			<div class="form-floating">
				<input type="text" class="form-control" id="floatingInput" name="user" placeholder="login" maxlength="20" required>
				<label for="floatingInput">Login</label>
			</div>
			<div class="form-floating">
				<input type="password" class="form-control" id="floatingPassword" name="pass" placeholder="Hasło" maxlength="20" required>
				<label for="floatingPassword">Hasło</label>
			</div>

			<button class="w-100 btn btn-lg btn-primary" type="submit">Zaloguj się</button>
			<p class="mt-3 mb-3">Nie masz konta? <a href="rejestruj.php">Zarejestruj się</a></p>
		</form>
<?php require 'dol.php'; ?>
