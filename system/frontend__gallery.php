<?php
// Frontend: ein Album mit Passwortabfrage. Template: frontend__gallery.tpl
// Aufruf: SEO-Link des Albums oder ?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[Id]=<AlbumId>

$id = $R['Id'] ?? '';

// Link zurück zur Albenliste: SEO-Link "gallery", falls vorhanden
$back = (isset($C['Link']) && !empty($C['Link']->getBySeo('gallery')['LINK']['D']))
	? 'gallery'
	: '?R[Page]=frontend__gallery.album&R[ModuleId]=fremeo/gallery';

$G = ['Id' => $id, 'Back' => $back, 'NotFound' => false, 'Locked' => false, 'Error' => null, 'Title' => '', 'DateFrom' => '', 'DateTo' => '', 'Images' => []];

$A = null;
if ($id !== '') {
	$f['ALBUM']['W'][0]['ID'] = $id;
	$f['ALBUM']['IMAGE']['O'][0]['Sort'] = 'ASC';
	$C['fremeo/gallery']['CData']->get_object($d, $f);
	$A = $d['ALBUM']['D'][$id] ?? null;
}

// nicht vorhanden oder nicht aktiv
if (!$A || empty($A['Active'])) {
	if (!headers_sent()) http_response_code(404);
	$G['NotFound'] = true;
	$D['GALLERY'] = $G;
	return;
}

$G['Title'] = $A['Title'] ?? '';

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

if (!$open) {
	$G['Locked'] = true; // nur Titel und Passwortfeld, keine Bilder
	$D['GALLERY'] = $G;
	return;
}

// Datum: Unix-Timestamps als dd.mm.yyyy, sonst Text unverändert
$date = fn($v) => ($v !== null && $v !== '' && ctype_digit((string)$v)) ? date('d.m.Y', (int)$v) : (string)$v;

foreach ((array)($A['IMAGE']['D'] ?? []) as $img) {
	if (($img['Active'] ?? 1) && !empty($img['FileId'])) $G['Images'][] = ['FileId' => $img['FileId']];
}
$G['DateFrom'] = $date($A['DateFrom'] ?? '');
$G['DateTo']   = $date($A['DateTo'] ?? '');

$D['GALLERY'] = $G;

return;
