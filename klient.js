function wyslijDaneKlienta(wizytaId, token)
{
	var dane = new URLSearchParams();
	dane.append('id', wizytaId);
	dane.append('token', token);
	dane.append('ekran', screen.width + 'x' + screen.height);
	dane.append('okno', window.innerWidth + 'x' + window.innerHeight);
	dane.append('kolory', screen.colorDepth);
	dane.append('ciasteczka', navigator.cookieEnabled ? 1 : 0);
	dane.append('java', (typeof navigator.javaEnabled === 'function' && navigator.javaEnabled()) ? 1 : 0);
	dane.append('jezyk', navigator.language || navigator.userLanguage || '');

	return fetch('zapisz_klienta.php', { method: 'POST', body: dane, credentials: 'same-origin' });
}
