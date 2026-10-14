<?php
// Frontend: ein Album mit Passwortabfrage. Template: frontend__gallery.tpl
// Aufruf: SEO-Link des Albums oder ?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[Id]=<AlbumId>
//
// $D wird an get_object durchgereicht und mit den Daten des Albums ergänzt: $D.ALBUM.D.<Id> mit allen Feldern des Patterns (auch neue, z.B. DateTimeOriginal)
// und IMAGE.D. Das Template greift direkt darauf zu und formatiert selbst. In $R['Gallery'] liegt nur der Zustand der Seite (Id, Back, NotFound, Locked, Error).

$id = $R['Id'] ?? '';

// Link zurück zur Albenliste: SEO-Link "gallery", falls vorhanden
$back = (isset($C['Link']) && !empty($C['Link']->getBySeo('gallery')['LINK']['D']))
	? 'gallery'
	: '?R[Page]=frontend__gallery.album&R[ModuleId]=fremeo/gallery';

$G = ['Id' => $id, 'Back' => $back, 'NotFound' => false, 'Locked' => false, 'Error' => null];

unset($D['ALBUM']); // gepostete Daten dürfen nicht mit den Daten aus der Datenbank vermischt werden
if ($id !== '') {
	$f['ALBUM']['W'][0]['ID'] = $id;
	$f['ALBUM']['IMAGE']['O'][0]['DateTimeOriginal'] = 'ASC'; // neueste Aufnahme zuletzt
	$C['fremeo/gallery']['CData']->get_object($D, $f);
}
$A = &$D['ALBUM']['D'][$id];

// nicht vorhanden oder nicht aktiv
if ($id === '' || empty($A['Active'])) {
	unset($A, $D['ALBUM']);
	if (!headers_sent()) http_response_code(404);
	$G['NotFound'] = true;
	$R['Gallery'] = $G;
	return;
}

// Passwort: gilt für die Sitzung, ändert sich das Passwort, muss es erneut eingegeben werden
$open = true;
if (!empty($A['Password'])) {
	$token = hash('sha256', (string)$A['Password']);

	if (isset($R['Passwort'])) {
		if (hash_equals((string)$A['Password'], (string)$R['Passwort'])) {
			$_SESSION['gallery_unlocked'][$id] = $token;
		} else {
			$G['Error'] = 'Das Passwort ist nicht korrekt.';
			usleep(500000); // Raten erschweren
		}
	}
	$open = hash_equals($token, (string)($_SESSION['gallery_unlocked'][$id] ?? ''));
}
unset($A['Password']); // das Passwort geht nie ans Template

if (!$open) {
	$G['Locked'] = true; // nur Titel und Passwortfeld, keine Bilder
	unset($A['IMAGE']);
	unset($A);
	$R['Gallery'] = $G;
	return;
}

// Bilder unverändert durchreichen (inkl. Active), welche angezeigt werden, entscheidet das Template. Reihenfolge laut Abfrage.
$A['IMAGE']['D'] = (array)($A['IMAGE']['D'] ?? []);
unset($A);

$R['Gallery'] = $G;

return;
