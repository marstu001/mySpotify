<?php declare(strict_types=1);
$tytul = 'Adresy IP i nazwy domenowe (gethostbyname, gethostbyaddr, REMOTE_ADDR)';
require 'gora.php';

$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$ip = gethostbyname('pbs.edu.pl');
echo '<p>IP serwera pbs.edu.pl: <strong>' . $e($ip) . '</strong></p>';

$ip = $_SERVER["REMOTE_ADDR"];
echo '<p>Twoje IP (IP gościa portalu): <strong>' . $e($ip) . '</strong></p>';

$hostname = gethostbyaddr("8.8.8.8");
echo '<p>Nazwa domenowa skojarzona z IP 8.8.8.8: <strong>' . $e($hostname) . '</strong></p>';

$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
echo '<p>Nazwa domenowa Twojego IP (hosta gościa portalu): <strong>' . $e($hostname) . '</strong></p>';

require 'dol.php';
