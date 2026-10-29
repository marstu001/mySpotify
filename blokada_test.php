<?php declare(strict_types=1);
$tytul = 'Blokada logowania spoza Polski – test Twojego IP (wariant a)';
require 'gora.php';
require_once 'db.php';
require_once 'geo.php';

$e  = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
list($dostep, $kraj, $powod) = sprawdz_dostep_ip($ip);
?>
<table class="table table-bordered table-striped table-sm align-middle w-auto">
	<tr><th>Twoje IP</th><td><?php echo $e($ip); ?></td></tr>
	<tr><th>Kraj wskazywany przez IP</th><td><?php echo $kraj !== '' ? $e($kraj) : '—'; ?></td></tr>
	<tr><th>Biała lista krajów</th><td><?php echo $e(implode(', ', DOZWOLONE_KRAJE)); ?></td></tr>
	<tr><th>Czarna lista IP</th><td><?php echo count(ZABLOKOWANE_IP) ? $e(implode(', ', ZABLOKOWANE_IP)) : '(pusta)'; ?></td></tr>
	<tr><th>Decyzja</th><td><?php echo $dostep ? '<span class="text-success fw-bold">logowanie DOZWOLONE</span>' : '<span class="text-danger fw-bold">logowanie ZABLOKOWANE</span>'; ?> &ndash; <?php echo $powod; ?></td></tr>
</table>

<?php require 'dol.php'; ?>
