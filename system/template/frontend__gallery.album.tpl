{** Frontend: Liste aller aktiven Alben (Daten: $D.R.GalleryAlbums aus frontend__gallery.album.php) **}
{block name="inner_body"}
	<div class="container py-4">
		<h1 class="h3 mb-4"><i class="fa-regular fa-images me-2"></i>Bildergalerie</h1>

		{if $D.R.GalleryAlbums}
		<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
			{foreach $D.R.GalleryAlbums as $id => $a}
			<div class="col">
				<a href="{$a.Url|escape}" class="album-card card h-100 text-decoration-none shadow-sm">
					<div class="ratio ratio-4x3 bg-light rounded-top overflow-hidden">
						{if $a.FileId}
							<img src="file/{$a.FileId}_480x360.webp" alt="{$a.Title|escape}" class="object-fit-cover" loading="lazy">
						{else}
							<div class="d-flex align-items-center justify-content-center text-secondary fs-1">
								{if $a.Locked}<i class="fa-solid fa-lock"></i>{else}<i class="fa-regular fa-image"></i>{/if}
							</div>
						{/if}
					</div>
					<div class="card-body">
						<h2 class="h5 card-title text-body mb-1">{if $a.Locked}<i class="fa-solid fa-lock text-warning me-1" title="Passwortgeschützt"></i>{/if}{$a.Title|default:'(ohne Titel)'|escape}</h2>
						{if $a.DateFrom || $a.DateTo}
							<p class="card-text small text-secondary mb-0"><i class="fa-regular fa-calendar me-1"></i>{if $a.DateFrom}{date('d.m.Y', (int)$a.DateFrom)}{/if}{if $a.DateTo} &ndash; {date('d.m.Y', (int)$a.DateTo)}{/if}</p>
						{/if}
					</div>
				</a>
			</div>
			{/foreach}
		</div>
		{else}
			<div class="alert alert-info">Es sind noch keine Alben vorhanden.</div>
		{/if}
	</div>
	{literal}
	<style>
		.album-card { transition: transform .15s, box-shadow .15s; }
		.album-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important; }
	</style>
	{/literal}
{/block}
