<?php declare(strict_types=1);
session_start();
header('Location: ' . (isset($_SESSION['loggedin']) ? 'index4.php' : 'index3.php'));
exit();
