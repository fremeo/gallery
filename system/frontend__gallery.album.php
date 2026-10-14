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

// Datum: Unix-Timestamps (roh), die Formatierung erfolgt im Template

$rows = [];
foreach ($albums as $id => $a) {
	$locked = !empty($a['Password']);
	$rows[$id] = [
		'Title'    => $a['Title'] ?? '',
		'DateFrom' => ($a['DateFrom'] ?? 0),
		'DateTo'   => ($a['DateTo'] ?? 0),
		'FileId'   => $a['FileId'] ?? '',
		'Locked'   => $locked,
		'Url'      => ($links[$a['LinkId'] ?? '']['FromURL'] ?? '') ?: "?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[Id]={$id}",
	];
}

$R['GalleryAlbums'] = $rows;

return;
