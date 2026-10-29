<?php declare(strict_types=1);
date_default_timezone_set('Europe/Warsaw');

const DB_HOST = 'localhost';
const DB_USER = 'srv126134_z6a';
const DB_PASS = 'Zadanie6aTest2026';
const DB_NAME = 'srv126134_z6a';

const DOZWOLONE_KRAJE = ['PL'];
const ZABLOKOWANE_IP  = [];

const TAJNY_KLUCZ = 'f285239340b37dd58da6b4866a52cf628450e2a2f695af2e';

function db_polacz(): ?mysqli
{
	mysqli_report(MYSQLI_REPORT_OFF);
	$link = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	if (!$link) {
		return null;
	}
	mysqli_set_charset($link, 'utf8mb4');
	mysqli_query($link, "SET time_zone = '" . date('P') . "'");
	return $link;
}
