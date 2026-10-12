<?php
// Admin: Liste aller Alben mit Paging. Template: admin__gallery.list.tpl

$DB = $C['fremeo/gallery']['CData'];
$perPage = 50;

// Speichern: nur das Feld Active der angezeigten Alben
if (($D['ACTION'] ?? null) == 'save') {
	foreach ((array)($D['ALBUM']['D'] ?? []) as $id => $row) {
		if (isset($row['Active'])) $U['ALBUM']['D'][$id]['Active'] = (int)$row['Active'];
	}
	if (!empty($U)) $DB->set_object($U);
}
unset($D['ALBUM']); // POST-Daten nicht mit der Ausgabe vermischen

// Alben der aktuellen Seite laden (neueste zuerst)
$page = max(1, (int)($R['P'] ?? 1));
$f['ALBUM']['O'][0]['DateFrom'] = 'DESC';
$f['ALBUM']['L'] = ['START' => ($page - 1) * $perPage, 'STEP' => $perPage];
$DB->get_object($d, $f);

$total = (int)($d['ALBUM']['COUNT'] ?? 0);
$pages = max(1, (int)ceil($total / $perPage));

// Datum: Unix-Timestamps als dd.mm.yyyy, sonst Text unverändert
$date = fn($v) => ($v !== null && ctype_digit((string)$v) && $v !== '') ? date('d.m.Y', (int)$v) : (string)$v;

$rows = [];
foreach ((array)($d['ALBUM']['D'] ?? []) as $id => $album) {
	$rows[$id] = [
		'Title'    => $album['Title'] ?? '',
		'Active'   => $album['Active'] ?? 0,
		'DateFrom' => $date($album['DateFrom'] ?? ''),
		'DateTo'   => $date($album['DateTo'] ?? ''),
		'FileId'   => $album['FileId'] ?? '',
		'Locked'   => !empty($album['Password']), // das Passwort selbst geht nicht ans Template
	];
}

// Seitenzahlen: erste, letzte und die 2 Nachbarn der aktuellen Seite, Lücken als 0 (…)
$show = array_unique([1, $pages, ...range(max(1, $page - 2), min($pages, $page + 2))]);
sort($show);
$pageList = [];
foreach ($show as $i => $p) {
	if ($i > 0 && $p - $show[$i - 1] > 1) $pageList[] = 0;
	$pageList[] = $p;
}

$D['GALLERY_ADMIN'] = [
	'Rows'     => $rows,
	'Total'    => $total,
	'Page'     => $page,
	'Pages'    => $pages,
	'PageList' => $pageList,
	'From'     => $total ? ($page - 1) * $perPage + 1 : 0,
	'To'       => ($page - 1) * $perPage + count($rows),
];

return;
