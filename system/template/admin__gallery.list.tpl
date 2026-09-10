{block name="inner_body"}
	{** Admin: Gallery categories list **}
	<h1>Gallery Kategorien</h1>
	{$new_id = hash("crc32b", time())}
	<a href="?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.edit&R[Id]={$new_id}&D[ALBUM][D][{$new_id}][Active]=1">Neue Kategorie anlegen</a>

	{if $D.ALBUM}
	<ul>
	{foreach from=$D.ALBUM.D key=id item=cat}
		<li>
		<a href="?R[ModuleId]=fremeo/gallery&R[Page]=admin__gallery.edit&R[Id]={$id}">{$cat.Title|escape}</a>
		Bilder: {$cat.IMAGE.COUNT}
		<div>{$cat.DateFrom} - {$cat.DateTo}</div>
		</li>
	{/foreach}
	</ul>
	{else}
	<p>Keine Kategorien gefunden.</p>
	{/if}
{/block}