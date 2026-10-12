{** frontend gallery index **}
{block name="inner_body"}

<div class="row row-cols-1 row-cols-md-3 g-4">
      {foreach from=$D.ALBUM.D key=kCat item=img}
		<div class="col">
			<div class="card h-100" style="width: 18rem;">
				<picture>
					<source srcset="file/{$img.FileId}_50x50.avif" type="image/avif">
					<source srcset="file/{$img.FileId}_50x50.webp" type="image/webp">
					<img class="card-img-top" src="file/{$img.FileId}_50x50.png">
				</picture>
				<div class="card-body">
					<h5 class="card-title">{$img.Title}</h5>
					<p class="card-text">{$img.DateFrom} - {$img.DateTo}</p>
				</div>
				{if $img.Password}
				<div>
					{input p=['name' => "R[ALBUM][D][{$kCat}][Password]", 'type' => 'password', 'placeholder' => 'Password' ]}
				</div>
				{/if}
				<div class="card-footer text-body-secondary text-center">
					<a href="?R[Page]=frontend__gallery&R[ModuleId]=fremeo/gallery&R[CategoryId]={$kCat}" class="btn btn-primary">Album</a>
				</div>
			</div>
		</div>
      {/foreach}
</div>

{/block}