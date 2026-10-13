<?php
// Frontend: Liste aller aktiven Alben. Template: frontend__gallery.album.tpl

$DB = $C['fremeo/gallery']['CData'];

$f['ALBUM']['W'][0]['Active'] = 1;
$f['ALBUM']['O'][0]['DateFrom'] = 'DESC';
$DB->get_object($d, $f);
$albums = (array)($d['ALBUM']['D'] ?? []);

// SEO-Links der Alben in einer Abfrage holen
$linkIds = array_values(array_filter(array_column($albums, 'LinkId')));
$links = ($linkIds && isset($C['Link'])) ? ($C['Link']->getById($linkIds)['LINK']['D'] ?? []) : [];

// Datum: Unix-Timestamps als dd.mm.yyyy, sonst Text unverändert
$date = fn($v) => ($v !== null && $v !== '' && ctype_digit((string)$v)) ? date('d.m.Y', (int)$v) : (string)$v;

$rows = [];
foreach ($albums as $id => $a) {
	$locked = !empty($a['Password']);
	$rows[$id] = [
		'Title'    => $a['Title'] ?? '',
		'DateFrom' => $date($a['DateFrom'] ?? ''),
		'DateTo'   => $date($a['DateTo'] ?? ''),
		'FileId'   => $date($a['FileId'] ?? ''),
		'Locked'   => $locked,
		'Url'      => ($links[$a['LinkId'] ?? '']['FromURL'] ?? '') ?: "?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[Id]={$id}",
	];
}

$R['GalleryAlbums'] = $rows;

return;
