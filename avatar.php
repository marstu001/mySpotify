<?php declare(strict_types=1);
const AVATAR_KATALOG    = 'media/avatars/';
const AVATAR_MAX_BAJTOW = 2097152;

function avatar_upewnij_kolumne(mysqli $link): void
{
	$r = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'avatar'");
	if ($r && mysqli_num_rows($r) === 0) {
		mysqli_query($link, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL DEFAULT NULL");
	}
}

function avatar_zapisz(array $plik, string $login): array
{
	$kod = $plik['error'] ?? UPLOAD_ERR_NO_FILE;
	if ($kod === UPLOAD_ERR_NO_FILE) {
		return [null, ''];
	}
	if ($kod !== UPLOAD_ERR_OK) {
		return [null, 'Nie udało się przesłać pliku (kod błędu ' . (int)$kod . ').'];
	}
	if (($plik['size'] ?? 0) > AVATAR_MAX_BAJTOW) {
		return [null, 'Plik jest za duży (maksymalnie 2 MB).'];
	}

	$dozwolone = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
	$typ = function_exists('mime_content_type') ? (string)@mime_content_type($plik['tmp_name']) : '';
	if ($typ === '') {
		$info = @getimagesize($plik['tmp_name']);
		$typ  = $info ? (string)$info['mime'] : '';
	}
	if (!isset($dozwolone[$typ]) || @getimagesize($plik['tmp_name']) === false) {
		return [null, 'Avatar musi być obrazem PNG lub JPEG.'];
	}

	if (!is_dir(AVATAR_KATALOG) && !@mkdir(AVATAR_KATALOG, 0755, true)) {
		return [null, 'Nie udało się utworzyć katalogu ' . AVATAR_KATALOG . ' na serwerze.'];
	}
	$nazwa    = substr(preg_replace('/[^a-zA-Z0-9]/', '', $login), 0, 12) . '_' . bin2hex(random_bytes(6)) . '.' . $dozwolone[$typ];
	$docelowy = AVATAR_KATALOG . $nazwa;
	if (!move_uploaded_file($plik['tmp_name'], $docelowy)) {
		return [null, 'Nie udało się zapisać pliku na serwerze.'];
	}
	return [$docelowy, ''];
}

function avatar_usun_plik(?string $sciezka): void
{
	if (is_string($sciezka) && preg_match('#^media/avatars/[A-Za-z0-9_]+\.(png|jpg)$#', $sciezka) && is_file($sciezka)) {
		@unlink($sciezka);
	}
}

function avatar_html(?string $sciezka, int $px = 24): string
{
	if (is_string($sciezka) && preg_match('#^media/avatars/[A-Za-z0-9_]+\.(png|jpg)$#', $sciezka)) {
		return '<img src="' . htmlspecialchars($sciezka, ENT_QUOTES, 'UTF-8') . '" width="' . $px . '" height="' . $px . '" alt="avatar" style="border-radius:50%; object-fit:cover; background:#fff;">';
	}
	return '<i class="bi bi-person-circle" style="font-size:' . $px . 'px; line-height:1;"></i>';
}
