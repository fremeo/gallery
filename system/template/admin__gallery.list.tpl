{block name="inner_body"}
	{** Admin: Alben-Liste (Daten aus admin__gallery.list.php, $D.GALLERY_ADMIN) **}
	{$G = $D.GALLERY_ADMIN}
	{$new_id = hash("crc32b", time())}
	{$base = "?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.list"}

	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h4 mb-0"><i class="fa-solid fa-images me-1"></i> Bildergalerie <span class="badge text-bg-secondary">{$G.Total}</span></h1>
		<a class="btn btn-primary btn-sm" href="?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.edit&R[Id]={$new_id}&D[ALBUM][D][{$new_id}][Active]=1"><i class="fa-solid fa-plus"></i> Neues Album</a>
	</div>

	{if $G.Rows}
	<form method="post">
		<input type="hidden" name="D[ACTION]" value="save">
		<div class="table-responsive">
			<table class="table table-striped table-hover align-middle">
				<thead class="table-light">
					<tr>
						<th scope="col" style="width:50px"></th>
						<th scope="col">Titel</th>
						<th scope="col" class="text-center">Aktiv</th>
						<th scope="col">Datum von</th>
						<th scope="col">Datum bis</th>
						<th scope="col" class="text-center" title="Passwortgeschützt"><i class="fa-solid fa-lock"></i></th>
						<th scope="col"></th>
					</tr>
				</thead>
				<tbody>
				{foreach $G.Rows as $id => $row}
					<tr>
						<td>
							{if $row.FileId}
								<img src="file/{$row.FileId}_50x50.webp" width="25" height="25" class="rounded border" alt="" loading="lazy">
							{else}
								<span class="text-muted"><i class="fa-regular fa-image"></i></span>
							{/if}
						</td>
						<td><a href="?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.edit&R[Id]={$id}">{$row.Title|default:'(ohne Titel)'|escape}</a></td>
						<td class="text-center">{input p=['name'=>"D[ALBUM][D][{$id}][Active]", 'value'=>$row.Active, 'type'=>'checkbox']}</td>
						<td>{$row.DateFrom|escape}</td>
						<td>{$row.DateTo|escape}</td>
						<td class="text-center">{if $row.Locked}<i class="fa-solid fa-lock text-warning" title="Passwortgeschützt"></i>{/if}</td>
						<td class="text-end text-nowrap">
							<a class="btn btn-outline-primary btn-sm" href="?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.edit&R[Id]={$id}" title="Bearbeiten"><i class="fa-solid fa-pen"></i></a>
							<input type="hidden" name="D[ALBUM][D][{$id}][__DELETE__]" value="1" disabled>
							<button type="button" class="btn btn-outline-danger btn-sm" title="Löschen" data-title="{$row.Title|default:'(ohne Titel)'|escape}" onclick="if (confirm('Album \'' + this.dataset.title + '\' mit allen Bildern wirklich löschen?\nDas Löschen erfolgt erst beim Speichern.')) { var r = this.closest('tr'); r.querySelector('input[name$=\'[__DELETE__]\']').disabled = false; r.classList.add('d-none'); }"><i class="fa-solid fa-trash"></i></button>
						</td>
					</tr>
				{/foreach}
				</tbody>
			</table>
		</div>

		<div class="d-flex justify-content-between align-items-center">
			<button type="submit" class="btn btn-primary btn-sm">Speichern</button>
			<small class="text-muted">{$G.From}&ndash;{$G.To} von {$G.Total}</small>
		</div>
	</form>

	{if $G.Pages > 1}
	<nav class="mt-3" aria-label="Seiten">
		<ul class="pagination pagination-sm justify-content-center mb-0">
			<li class="page-item{if $G.Page <= 1} disabled{/if}"><a class="page-link" href="{$base}&R[P]={$G.Page - 1}">&laquo;</a></li>
			{foreach $G.PageList as $p}
				{if $p == 0}
					<li class="page-item disabled"><span class="page-link">&hellip;</span></li>
				{else}
					<li class="page-item{if $p == $G.Page} active{/if}"><a class="page-link" href="{$base}&R[P]={$p}">{$p}</a></li>
				{/if}
			{/foreach}
			<li class="page-item{if $G.Page >= $G.Pages} disabled{/if}"><a class="page-link" href="{$base}&R[P]={$G.Page + 1}">&raquo;</a></li>
		</ul>
	</nav>
	{/if}
	{else}
		<div class="alert alert-info">Keine Alben vorhanden.</div>
	{/if}
{/block}
