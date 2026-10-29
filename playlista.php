<?php declare(strict_types=1);
require_once 'media.php';
[$uid, $login] = wymagaj_logowania();

$idpl = (int)($_GET['idpl'] ?? 0);
$link = polacz_lub_zakoncz();
$pl = $idpl > 0 ? playlista($link, $idpl) : null;
if ($pl === null || !czy_dostep_playlisty($pl, $uid)) {
	mysqli_close($link);
	flash('danger', 'Nie ma takiej playlisty albo jest prywatna.');
	header('Location: playlisty.php');
	exit();
}
$elementy = elementy_playlisty($link, $idpl);
mysqli_close($link);

$dane = [];
foreach ($elementy as $p) {
	$vtt = ma_vtt($p['tekst']);
	$dane[] = [
		'id'    => (int)$p['id'],
		'tytul' => $p['tytul'],
		'autor' => $p['autor'],
		'gat'   => (string)$p['gatunek'],
		'wl'    => $p['wlasciciel'],
		'src'   => url_strumien((int)$p['id']),
		'vtt'   => $vtt ? 'napisy.php?id=' . (int)$p['id'] : '',
		'tekst' => $vtt ? '' : (string)$p['tekst'],
	];
}

$tytul = 'Playlista: ' . $pl['name'];
require 'gora.php';
flash_wypisz();
$wideo = m('tag') === 'video';
?>
<p class="text-muted">Właściciel: <strong><?php echo e($pl['wlasciciel']); ?></strong> &middot; <?php echo (int)$pl['public'] === 1 ? 'publiczna' : 'prywatna'; ?> &middot; <?php echo count($dane); ?> <?php echo e(liczba_pl(count($dane), m('jedn'), m('mn'), m('dop_mn'))); ?></p>
<?php if (!$dane): ?>
<p class="text-muted">Ta playlista jest pusta.</p>
<?php else: ?>
<div class="row g-4">
	<div class="col-12 col-lg-8">
		<div class="card">
			<div class="card-body">
				<h2 class="h5 mb-0" id="biezacy-tytul"></h2>
				<div class="text-muted small mb-2" id="biezacy-opis"></div>
<?php if ($wideo): ?>
				<video id="odtwarzacz" class="w-100 bg-black" style="max-height:68vh;" controls preload="auto" playsinline></video>
<?php else: ?>
				<audio id="odtwarzacz" class="w-100" controls preload="auto"></audio>
<?php endif; ?>
				<div class="d-flex flex-wrap gap-2 align-items-center mt-3">
					<button class="btn btn-success" type="button" id="btn-start"><i class="bi bi-play-fill"></i> Odtwórz playlistę od początku</button>
					<button class="btn btn-outline-secondary" type="button" id="btn-prev" title="Poprzedni"><i class="bi bi-skip-backward-fill"></i></button>
					<button class="btn btn-outline-secondary" type="button" id="btn-next" title="Następny"><i class="bi bi-skip-forward-fill"></i></button>
					<div class="form-check ms-2 mb-0">
						<input class="form-check-input" type="checkbox" id="powtarzaj">
						<label class="form-check-label" for="powtarzaj">Powtarzaj playlistę</label>
					</div>
				</div>
				<div class="small text-muted mt-2" id="status"></div>
				<div id="biezacy-tekst-blok" class="mt-3" hidden>
					<h3 class="h6"><?php echo e(m('tekst_l')); ?></h3>
					<pre class="tekst-pre mb-0" id="biezacy-tekst"></pre>
				</div>
			</div>
		</div>
	</div>
	<div class="col-12 col-lg-4">
		<div class="list-group" id="lista"></div>
	</div>
</div>
<script>
(function () {
	var utwory = <?php echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
	var wideo = <?php echo $wideo ? 'true' : 'false'; ?>;
	var media = document.getElementById('odtwarzacz');
	var lista = document.getElementById('lista');
	var powtarzaj = document.getElementById('powtarzaj');
	var status = document.getElementById('status');
	var idx = -1;

	utwory.forEach(function (u, i) {
		var a = document.createElement('a');
		a.href = '#';
		a.className = 'list-group-item list-group-item-action';
		a.setAttribute('data-i', i);
		var s = document.createElement('strong');
		s.textContent = (i + 1) + '. ' + u.tytul;
		var d = document.createElement('div');
		d.className = 'small text-muted';
		d.textContent = u.autor + ' (' + u.wl + ')';
		a.appendChild(s);
		a.appendChild(d);
		a.addEventListener('click', function (ev) { ev.preventDefault(); ustaw(i, true); });
		lista.appendChild(a);
	});

	function graj() {
		var p = media.play();
		if (p && p.catch) { p.catch(function () { status.textContent = 'Naciśnij odtwarzanie, aby rozpocząć (przeglądarka blokuje samoczynny start).'; }); }
	}

	function ustaw(i, startuj) {
		idx = i;
		var u = utwory[i];
		Array.prototype.slice.call(media.querySelectorAll('track')).forEach(function (t) { media.removeChild(t); });
		media.src = u.src;
		if (wideo && u.vtt) {
			var t = document.createElement('track');
			t.kind = 'subtitles';
			t.srclang = 'pl';
			t.label = 'Polski';
			t.src = u.vtt;
			t.default = true;
			media.appendChild(t);
		}
		document.getElementById('biezacy-tytul').textContent = u.tytul;
		document.getElementById('biezacy-opis').textContent = u.autor + ' · ' + u.gat + ' · dodał(a): ' + u.wl + ' · pozycja ' + (i + 1) + ' z ' + utwory.length;
		var blok = document.getElementById('biezacy-tekst-blok');
		blok.hidden = !u.tekst;
		document.getElementById('biezacy-tekst').textContent = u.tekst;
		Array.prototype.forEach.call(lista.children, function (el, k) { el.classList.toggle('active', k === i); });
		status.textContent = '';
		if (startuj) { graj(); }
	}

	function nastepny() {
		if (idx + 1 < utwory.length) { ustaw(idx + 1, true); return true; }
		if (powtarzaj.checked) { ustaw(0, true); return true; }
		return false;
	}

	media.addEventListener('ended', function () {
		if (!nastepny()) { status.textContent = 'Koniec playlisty.'; }
	});
	media.addEventListener('error', function () {
		status.textContent = 'Nie można odtworzyć tej pozycji, przechodzę do następnej.';
		setTimeout(nastepny, 800);
	});
	document.getElementById('btn-start').addEventListener('click', function () { ustaw(0, true); });
	document.getElementById('btn-next').addEventListener('click', function () { if (idx + 1 < utwory.length) { ustaw(idx + 1, true); } });
	document.getElementById('btn-prev').addEventListener('click', function () { if (idx > 0) { ustaw(idx - 1, true); } });

	ustaw(0, false);
	graj();
})();
</script>
<?php endif; ?>
<p class="mt-3"><a class="btn btn-outline-secondary" href="playlisty.php"><i class="bi bi-arrow-left"></i> Playlisty</a></p>
<?php require 'dol.php'; ?>
