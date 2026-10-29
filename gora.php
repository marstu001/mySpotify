<?php declare(strict_types=1);
$publiczna = $publiczna ?? false;
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!$publiczna && !isset($_SESSION['loggedin']))
{
	header('Location: index3.php');
	exit();
}
$tytul     = $tytul ?? '';
$formularz = $formularz ?? false;
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Zadanie 6a - mySpotify">
	<meta name="author" content="Stube">
	<meta name="keywords" content="geolokalizacja, IP, PHP, Bootstrap">
	<title>Stube</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
	<link rel="stylesheet" type="text/css" href="twoj_css.css">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" src="twoj_js.js"></script>
</head>

<body onload="myLoadHeader()">
	<div id='myHeader'> </div>
<?php if ($formularz): ?>
	<main class="form-signin">
<?php else: ?>
	<main>
		<section class="sekcja1">
			<div class="container-fluid">
				<h1 class="h3 mb-3"><?php echo htmlspecialchars($tytul, ENT_QUOTES, 'UTF-8'); ?></h1>
<?php endif; ?>
