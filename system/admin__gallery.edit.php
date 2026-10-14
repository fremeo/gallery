<?php
// Admin: Album bearbeiten (Daten, Startbild, Bilder hochladen / sortieren / löschen). Template: admin__gallery.edit.tpl

$DB       = $C['fremeo/gallery']['CData'];
$File     = $C['fremeo/gallery']['File'];
$id       = $R['Id'] ?? null;
$imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif']; // erlaubte Dateitypen

$E = ['Id' => $id, 'Msg' => null, 'Info' => null, 'Error' => null];
if (!$id || !preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
	$E['Id'] = null;
	$E['Error'] = 'Kein gültiges Album gewählt.';
	$R['GalleryEdit'] = $E; // Hilfswerte fürs Template ($D.R.GalleryEdit), $D enthält nur die Datenbankstruktur
	return;
}

// Album mit Bildern laden (nach Sort sortiert)
$load = function () use ($DB, $id) {
	$f['ALBUM']['W'][0]['ID'] = $id;
	$f['ALBUM']['IMAGE']['O'][0]['Sort'] = 'ASC';
	$DB->get_object($d, $f);
	return $d['ALBUM']['D'][$id] ?? [];
};

// Wird die Datei noch von einem anderen Album (als Bild oder Startbild) verwendet?
$inUse = function ($fileId) use ($DB, $id) {
	$byImage['ALBUM']['W'][0]['IMAGE']['W'][0]['FileId'] = $fileId;
	$byCover['ALBUM']['W'][0]['FileId'] = $fileId;
	foreach ([$byImage, $byCover] as $q) {
		$DB->get_object($u, $q);
		if (array_diff(array_keys($u['ALBUM']['D'] ?? []), [$id])) return true;
		unset($u);
	}
	return false;
};

if (($D['ACTION'] ?? null) == 'save') {
	$post     = $D['ALBUM']['D'][$id] ?? [];
	$old      = $load();
	$images   = (array)($old['IMAGE']['D'] ?? []);
	$oldCover = $old['FileId'] ?? '';

	$U = ['ALBUM' => ['D' => [$id => array_intersect_key($post, array_flip(['Active', 'Title', 'DateFrom', 'DateTo', 'Password', 'FileId']))]]];
	$row = &$U['ALBUM']['D'][$id];

	// Datum immer als Unix-Timestamp speichern (Zahl bleibt Zahl, Text wie 2026-10-14 oder 14.10.2026 wird umgerechnet, leer/ungültig = leer)
	foreach (['DateFrom', 'DateTo'] as $k) {
		if (!array_key_exists($k, $row)) continue;
		$v = trim((string)$row[$k]);
		$row[$k] = ($v !== '' && ctype_digit($v)) ? (int)$v : (($ts = $v !== '' ? strtotime($v) : false) !== false ? $ts : '');
	}
	// Sortierung und Löschen: nur Bilder, die zu diesem Album gehören
	$deleted = [];
	foreach ((array)($post['IMAGE']['D'] ?? []) as $imgId => $img) {
		if (!isset($images[$imgId])) continue;
		if (!empty($img['__DELETE__'])) {
			$row['IMAGE']['D'][$imgId] = '__DELETE__';
			unset($images[$imgId]);
			$deleted[] = $imgId;
		} elseif (isset($img['Sort'])) {
			$row['IMAGE']['D'][$imgId]['Sort'] = (int)$img['Sort'];
		}
	}

	// Startbild: ein gelöschtes Album-Bild ist kein Startbild mehr, eine neue Datei ersetzt es
	if (in_array($row['FileId'] ?? '', $deleted, true)) $row['FileId'] = '';
	if (array_filter((array)($_FILES['cover']['tmp_name'] ?? []))) {
		$cover = array_key_first($File->add($_FILES['cover'], $imageExt));
		if ($cover) $row['FileId'] = $cover;
	}

	// Neue Bilder (keine Begrenzung)
	// Aufnahmedatum eines Bildes (ALBUM.IMAGE.DateTimeOriginal, Unix-Timestamp), nur aus den Bilddaten:
	// 1. EXIF DateTimeOriginal bzw. DateTimeDigitized aus der gespeicherten Datei,
	// 2. sonst das Dateidatum, das der Browser mitschickt (R[FileModified] = {"dateiname.jpg": lastModified in ms}).
	// Gibt es beides nicht, wird kein Datum gespeichert (kein Upload-Zeitpunkt).
	$modified = json_decode((string)($R['FileModified'] ?? ''), true);
	$taken = function (string $path, array $info) use ($modified): ?int {
		if (function_exists('exif_read_data')) {
			$exif = @exif_read_data($path, 'EXIF'); // liest JPEG und TIFF, bei anderen Formaten false
			foreach (['DateTimeOriginal', 'DateTimeDigitized'] as $key) {
				$value = is_array($exif) ? ($exif[$key] ?? null) : null;
				if (is_string($value) && !str_starts_with($value, '0000') && ($date = \DateTime::createFromFormat('Y:m:d H:i:s', $value))) {
					return $date->getTimestamp();
				}
			}
		}
		$ms = is_array($modified) ? ($modified[strtolower($info['Name'] . '.' . $info['Extension'])] ?? null) : null;
		return (is_int($ms) || is_float($ms)) && $ms > 0 ? intdiv((int)$ms, 1000) : null;
	};
	if (array_filter((array)($_FILES['images']['tmp_name'] ?? []))) {
		$sort = $images ? max(array_map(fn($i) => (int)($i['Sort'] ?? 0), $images)) + 1 : 0;
		$uploaded = $File->add($_FILES['images'], [...$imageExt, 'zip']);

		// ZIP: entpacken, nur Bilder übernehmen, ZIP samt Eintrag entfernen
		foreach ($uploaded as $zipId => $zipInfo) {
			if ($zipInfo['Extension'] !== 'zip') continue;
			$tmp = sys_get_temp_dir() . '/gallery_' . bin2hex(random_bytes(8));
			foreach ($File->unzip($File->path($zipId, 'zip'), $tmp, $imageExt) as $path) {
				$name = preg_replace('/^\d+_/', '', basename($path));
				if ($img = $File->addFile($path, $name, $imageExt)) {
					$uploaded[$img['Id']] = ['Name' => $img['Name'], 'Size' => $img['Size'], 'Extension' => $img['Extension']];
				}
			}
			$C['Filesystem']->remove($tmp);
			$File->delete($zipId);
			unset($uploaded[$zipId]);
		}

		foreach ($uploaded as $fid => $info) {
			if (isset($images[$fid])) continue; // schon im Album
			$row['IMAGE']['D'][$fid] = ['Active' => 1, 'FileId' => $fid, 'Sort' => $sort++];
			if (($date = $taken($File->path($fid, $info['Extension']), $info)) !== null) {
				$row['IMAGE']['D'][$fid]['DateTimeOriginal'] = $date;
			}
			$images[$fid] = [];
		}
	}

	// Ohne Startbild: erstes Bild des Albums
	if (empty($row['FileId']) && $images) $row['FileId'] = array_key_first($images);
	$newCover = $row['FileId'] ?? '';

	// SEO-Link: Eingabe leer = automatisch "gallery/titel" (nur wenn noch kein Link existiert), sonst anlegen bzw. umbenennen
	if (isset($C['Link'])) {
		$Link   = $C['Link'];
		$linkId = $old['LinkId'] ?? '';
		$seoNow = $linkId ? ($Link->getById($linkId)['LINK']['D'][$linkId]['FromURL'] ?? '') : '';
		if (!$seoNow) $linkId = '';

		$seo = $Link->normalize(trim($post['SeoUrl'] ?? ''));
		if (!$seo && !$linkId && ($slug = $Link->normalize($post['Title'] ?? ''))) $seo = "gallery/{$slug}";

		if ($seo && $seo !== $seoNow) {
			if ($linkId) {
				$res = $Link->rename(['OldSeoId' => $linkId, 'NewSeo' => $seo])[0];
				$final = $res['NewSeo'];
				if ($res['Success']) $linkId = $res['NewId'];
			} else {
				$res = $Link->create(['SeoURL' => $seo, 'Page' => 'frontend__gallery', 'ModuleId' => 'fremeo/gallery', 'Param' => "R[Id]={$id}"])[0];
				$final = $res['SeoUrl'];
				$linkId = $res['LinkId'];
			}
			if ($final !== $seo) $E['Info'] = "Der SEO-Link \"{$seo}\" war schon vergeben, verwendet wurde \"{$final}\".";
		}
		$row['LinkId'] = $linkId; // ALBUM.LinkId immer passend zum aktuellen Link
	}
	unset($row);

	$DB->set_object($U);

	// Nicht mehr benötigte Dateien löschen (gelöschte Bilder, ersetztes Startbild), sofern sie nirgends mehr verwendet werden
	foreach (array_unique(array_filter([...$deleted, $oldCover !== $newCover ? $oldCover : null])) as $fileId) {
		if (!isset($images[$fileId]) && $fileId !== $newCover && !$inUse($fileId)) $File->delete($fileId);
	}

	$E['Msg'] = 'Gespeichert';
	unset($D['ALBUM']); // gepostete Daten nicht mit der Ausgabe vermischen
}

$album  = $load();
$images = [];
foreach ((array)($album['IMAGE']['D'] ?? []) as $imgId => $img) {
	$images[$imgId] = ['FileId' => $img['FileId'] ?? $imgId, 'Sort' => $img['Sort'] ?? 0];
}
$cover = $album['FileId'] ?? '';
$linkId = $album['LinkId'] ?? '';
$seoUrl = ($linkId && isset($C['Link'])) ? ($C['Link']->getById($linkId)['LINK']['D'][$linkId]['FromURL'] ?? '') : '';

$R['GalleryEdit'] = $E + [
	'Album'  => $album + ['Active' => 1],
	'Images' => $images,
	'Cover'  => $cover ? [$cover => ['FileId' => $cover]] : [],
	'SeoUrl' => $seoUrl,
];

return;
