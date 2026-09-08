{block name="inner_body"}

{** Admin: Edit/Create gallery category **}
<h1>Kategorie bearbeiten / anlegen</h1>

{if $D.MSG}
  <div class="msg">{$D.MSG}</div>
{/if}

<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="D[ACTION]" value='save'>
	{foreach $D.GALLERY_CATEGORY.D key=kCat item=Cat}
	<label>Title: <input name="D[GALLERY_CATEGORY][D][{$kCat}][Title]" value="{$Cat.Title|escape}"></label><br>
	<label>Active: <input type="checkbox" name="D[GALLERY_CATEGORY][D][{$kCat}][Active]" value="1" {if $Cat.Active}checked{/if}></label><br>
	<label>Date From: <input name="D[GALLERY_CATEGORY][D][{$kCat}][DateFrom]" value="{$Cat.DateFrom|escape}"></label><br>
	<label>Date To: <input name="D[GALLERY_CATEGORY][D][{$kCat}][DateTo]" value="{$Cat.DateTo|escape}"></label><br>
	<label>Password: <input name="D[GALLERY_CATEGORY][D][{$kCat}][Password]" value="{$Cat.Password|escape}"></label><br>
	<label>Start File (FileId): <input name="D[GALLERY_CATEGORY][D][{$kCat}][FileId]" value="{$Cat.FileId|escape}"></label><br>
	{/foreach}
	<button type="submit" name="save">Speichern</button>
</form>

<h2>Bilder hochladen</h2>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="images[]" multiple>
  <button type="submit">Upload</button>
  <input type="hidden" name="D[GALLERY_CATEGORY][Id]" value="{$D.GALLERY_CATEGORY.Id}">
</form>

{if $D.GALLERY_CATEGORY.D}
  <h3>Hochgeladene Bilder</h3>
  <ul>
  {foreach from=$D.GALLERY_CATEGORY.D[ $D.R.Id ].IMAGE.D item=img}
    <li>
	<picture>
		<source srcset="file/{$img.FileId}_50x50.avif" type="image/avif">
		<source srcset="file/{$img.FileId}_50x50.webp" type="image/webp">
		<img src="file/{$img.FileId}_50x50.png">
	</picture>
	</li>
  {/foreach}
  </ul>
{/if}

{/block}