<?php declare(strict_types=1);
$tytul = 'Informacje o serwerze i PHP (phpinfo)';
require 'gora.php';

ob_start();
phpinfo();
$info = ob_get_clean();
?>
<iframe class="w-100 border rounded bg-white" style="height:78vh" title="phpinfo" srcdoc="<?php echo htmlspecialchars($info, ENT_QUOTES, 'UTF-8'); ?>"></iframe>
<?php require 'dol.php'; ?>
