{** frontend gallery index **}
{block name="inner_body"}

    <div class="gallery-images">
      {foreach from=$D.GALLERY_CATEGORY.D key=kCat item=img}
		{$img.Title} - {$img.DateFrom}
        <a href="?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[CategoryId]={$kCat}" class="gallery-thumb" data-file="{$img.File}">
		
		<picture>
			<source srcset="file/{$img.FileId}_50x50.avif" type="image/avif">
			<source srcset="file/{$img.FileId}_50x50.webp" type="image/webp">
			<img src="file/{$img.FileId}_50x50.png">
		</picture>
		</a>
      {/foreach}
    </div>

{/block}