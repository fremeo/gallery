{block name="inner_body"}
	{** Admin: Album bearbeiten (Daten aus admin__gallery.edit.php, $D.R.GalleryEdit) **}
	{$E = $D.R.GalleryEdit}
	{$A = $E.Album}
	{$id = $E.Id}
	{$list = "?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.list"}

	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 mb-0"><i class="fa-solid fa-images me-1"></i> Album bearbeiten</h1>
		<a class="btn btn-outline-secondary btn-sm" href="{$list}"><i class="fa-solid fa-arrow-left"></i> Zur Liste</a>
	</div>

	{if $E.Error}<div class="alert alert-danger">{$E.Error|escape}</div>{/if}
	{if $E.Msg}<div class="alert alert-success">{$E.Msg|escape}</div>{/if}
	{if $E.Info}<div class="alert alert-warning">{$E.Info|escape}</div>{/if}

	{if $id}
	<form method="post" enctype="multipart/form-data">
		<input type="hidden" name="D[ACTION]" value="save">

		<div class="row g-3">
			<div class="col-md-4 d-flex align-items-end">
				<label class="form-check form-switch fs-5 mb-1">
					{input p=['name'=>"D[ALBUM][D][{$id}][Active]", 'type'=>'checkbox', 'value'=>$A.Active]}
					<span class="form-check-label">Aktiv</span>
				</label>
			</div>
			<div class="col-md-8">
				<label class="form-label" for="album_title">Titel</label>
				{input p=['name'=>"D[ALBUM][D][{$id}][Title]", 'id'=>'album_title', 'type'=>'text', 'value'=>$A.Title, 'required'=>1]}
			</div>
			
			<div class="col-md-4">
				<label class="form-label">Datum von</label>
				{input p=['name'=>"D[ALBUM][D][{$id}][DateFrom]", 'type'=>'datetime', 'value'=>$A.DateFrom]}
			</div>
			<div class="col-md-4">
				<label class="form-label">Datum bis</label>
				{input p=['name'=>"D[ALBUM][D][{$id}][DateTo]", 'type'=>'datetime', 'value'=>$A.DateTo]}
			</div>
			<div class="col-md-4">
				<label class="form-label" for="album_password"><i class="fa-solid fa-lock"></i> Passwort</label>
				{input p=['name'=>"D[ALBUM][D][{$id}][Password]", 'id'=>'album_password', 'type'=>'text', 'value'=>$A.Password]}
				<div class="form-text">Leer lassen = Album ist nicht passwortgeschützt.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="album_seo"><i class="fa-solid fa-link"></i> SEO-Link</label>
				<div class="input-group">
					<span class="input-group-text">/</span>
					{input p=['name'=>"D[ALBUM][D][{$id}][SeoUrl]", 'id'=>'album_seo', 'type'=>'text', 'value'=>$E.SeoUrl, 'placeholder'=>'gallery/titel-des-albums']}
				</div>
				<div class="form-text">Ohne Link wird er automatisch aus dem Titel gebildet (gallery/titel, Umlaute und Leerzeichen werden ersetzt). Eine Änderung ersetzt den bisherigen Link, ist die URL vergeben, wird _1, _2 … angehängt.</div>
			</div>
			<div class="col-12">
				<label class="form-label">Startbild</label>
				{* Einzelbild-Feld für ALBUM.FileId: max 1, nur Bildtypen; neue Datei kommt als $_FILES['cover'] *}
				{input p=[
					'type'=>'image',
					'value'=>$E.Cover,
					'max'=>1,
					'deletable'=>1,
					'upload'=>'cover',
					'accept'=>'.jpg,.jpeg,.png,.gif,.webp,.avif',
					'mainname'=>"D[ALBUM][D][{$id}][FileId]",
					'main'=>$A.FileId,
					'size'=>96,
					'help'=>'Leer = erstes Bild des Albums'
				]}
			</div>
		</div>

		<h2 class="h5 mt-4 mb-2"><i class="fa-regular fa-images me-1"></i> Bilder</h2>
		{input p=[
			'name'=>"D[ALBUM][D][{$id}][IMAGE][D]",
			'type'=>'image',
			'value'=>$E.Images,
			'sortable'=>1,
			'deletable'=>1,
			'upload'=>'images',
			'accept'=>'.jpg,.jpeg,.png,.gif,.webp,.avif,.zip',
			'size'=>64,
			'help'=>'Ziehen = sortieren, Papierkorb = entfernen, Plus = Bilder hinzufügen (Upload beim Speichern)'
		]}

		<div class="mt-4">
			<button type="submit" class="btn btn-primary">Speichern</button>
			<a class="btn btn-outline-secondary" href="{$list}">Abbrechen</a>
		</div>
	</form>
	{literal}
	<script>
		// SEO-Link live aus dem Titel vorschlagen, solange das Feld leer / unverändert automatisch ist (Server normalisiert endgültig, siehe Link::normalize)
		$(function() {
			var map = {'ä':'a','ö':'o','ü':'u','ß':'ss','é':'e','è':'e','ê':'e','à':'a','â':'a','á':'a','ô':'o','ó':'o','û':'u','ú':'u','î':'i','í':'i','ç':'c','ñ':'n'};
			var slug = function(s) {
				s = s.toLowerCase().replace(/[äöüßéèêàâáôóûúîíçñ]/g, function(c) { return map[c]; })
					.replace(/[^a-z0-9\/._-]+/g, '_').replace(/_+/g, '_').replace(/_*\/+_*/g, '/');
				return s.replace(/^[\/_.-]+|[\/_.-]+$/g, '');
			};
			var $t = $('#album_title'), $s = $('#album_seo'), auto = !$s.val();
			$s.on('input', function() { auto = !$s.val(); });
			$t.on('input', function() {
				if (!auto) return;
				var v = slug($t.val());
				$s.val(v ? 'gallery/' + v : '');
			});
		});
	</script>
	{/literal}
	{/if}
{/block}
