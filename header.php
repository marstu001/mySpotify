<?php declare(strict_types=1);
session_start();
require_once 'media_cfg.php';
require_once 'avatar.php';
if (!isset($_SESSION['loggedin'])) { return; }
?>
<header>
<div class="container">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top mt-0 mb-0 ms-0 me-0 pt-0 pb-0 ps-0 pe-0">
	<div class="container-fluid">

		<div class="collapse navbar-collapse" id="main_nav">
			<ul class="navbar-nav">
				<li class="nav-item"><a class="nav-link" href="index4.php"><i class="bi bi-house-door"></i> Zadanie 6a</a></li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi <?php echo MEDIA['ikona']; ?>"></i> <?php echo MEDIA['nazwa']; ?></a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="select.php"><i class="bi bi-cloud-upload"></i> Dodaj <?php echo MEDIA['jedn']; ?></a></li>
						<li><a class="dropdown-item" href="moje.php"><i class="bi <?php echo MEDIA['ikona']; ?>"></i> <?php echo MEDIA['moje']; ?></a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="playlisty.php"><i class="bi bi-collection-play"></i> Playlisty</a></li>
						<li><a class="dropdown-item" href="nowa_playlista.php"><i class="bi bi-plus-circle"></i> Nowa playlista</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-hdd-network"></i> Serwer</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="netstat.php"><i class="bi bi-ethernet"></i> Status usług &ndash; netstat</a></li>
						<li><a class="dropdown-item" href="phpinfo_test.php"><i class="bi bi-info-circle"></i> Informacje o PHP &ndash; phpinfo</a></li>
						<li><a class="dropdown-item" href="whoami.php"><i class="bi bi-person-badge"></i> Użytkownik serwera &ndash; whoami</a></li>
						<li><a class="dropdown-item" href="top.php"><i class="bi bi-cpu"></i> Obciążenie serwera &ndash; top</a></li>
						<li><a class="dropdown-item" href="ls.php"><i class="bi bi-folder2-open"></i> Lista plików &ndash; ls -al</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-diagram-3"></i> DNS i IP</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="dns_rekord.php"><i class="bi bi-journal-text"></i> Rekordy DNS domeny</a></li>
						<li><a class="dropdown-item" href="ip_hosty.php"><i class="bi bi-hdd-rack"></i> IP serwera, IP gościa, nazwy domenowe</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-geo-alt"></i> Geolokalizacja</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="geolokalizacja.php"><i class="bi bi-pin-map"></i> Geolokalizacja gościa (ipinfo.io)</a></li>
						<li><a class="dropdown-item" href="geo_diagnoza.php"><i class="bi bi-wrench-adjustable"></i> Diagnoza serwisów geolokalizacji</a></li>
						<li><a class="dropdown-item" href="geoip.php"><i class="bi bi-database"></i> Lokalna baza GeoIP (.mmdb)</a></li>
					</ul>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown"><i class="bi bi-people"></i> Goście portalu</a>
					<ul class="dropdown-menu">
						<li><a class="dropdown-item" href="goscie.php"><i class="bi bi-journal-check"></i> Rejestr wizyt gości</a></li>
						<li><a class="dropdown-item" href="blokada_test.php"><i class="bi bi-shield-lock"></i> Wariant a &ndash; blokada spoza Polski</a></li>
						<li><a class="dropdown-item" href="goscie_unikalne.php"><i class="bi bi-fingerprint"></i> Wariant b &ndash; różne IP i liczba wejść</a></li>
						<li><a class="dropdown-item" href="goscie_ranking.php"><i class="bi bi-sort-numeric-down-alt"></i> Wariant c &ndash; ranking wejść</a></li>
					</ul>
				</li>
			</ul>

			<ul class="navbar-nav ms-auto">
			<?php if (isset($_SESSION['loggedin'])): ?>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
						<?php echo avatar_html((string)($_SESSION['avatar'] ?? ''), 24); ?>
						<?php echo htmlentities((string)($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
					</a>
					<ul class="dropdown-menu dropdown-menu-end">
						<li><a class="dropdown-item" href="index4.php"><i class="bi bi-house"></i> Strona główna portalu</a></li>
						<li><a class="dropdown-item" href="zmien_avatar.php"><i class="bi bi-image"></i> Zmień avatar</a></li>
						<li><a class="dropdown-item" href="goscie_podglad.php"><i class="bi bi-journal-check"></i> Rejestr wizyt (logowań)</a></li>
						<li><hr class="dropdown-divider"></li>
						<li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Wyloguj</a></li>
					</ul>
				</li>
			<?php else: ?>
				<li class="nav-item"><a class="nav-link" href="index3.php"><i class="bi bi-box-arrow-in-right"></i> Zaloguj się</a></li>
				<li class="nav-item"><a class="nav-link" href="rejestruj.php"><i class="bi bi-person-plus"></i> Zarejestruj się</a></li>
			<?php endif; ?>
			</ul>
		</div>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav"  aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

	</div>
</nav>

</div>
</header>
