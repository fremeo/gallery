{** Frontend: ein Album (Daten: $D.GALLERY aus frontend__gallery.php) **}
{block name="inner_body"}
	{$G = $D.GALLERY}
	<div class="container py-4">
		<a href="{$G.Back|escape}" class="btn btn-link px-0 mb-2"><i class="fa-solid fa-arrow-left"></i> Alle Alben</a>

		{if $G.NotFound}
			<div class="alert alert-warning">Dieses Album wurde nicht gefunden.</div>

		{elseif $G.Locked}
			<div class="row justify-content-center">
				<div class="col-sm-10 col-md-6 col-lg-4">
					<div class="card shadow-sm">
						<div class="card-body text-center">
							<i class="fa-solid fa-lock fs-1 text-warning mb-3"></i>
							<h1 class="h4">{$G.Title|default:'(ohne Titel)'|escape}</h1>
							<p class="text-secondary">Dieses Album ist passwortgeschützt.</p>
							{if $G.Error}<div class="alert alert-danger py-2">{$G.Error|escape}</div>{/if}
							<form method="post">
								<div class="input-group">
									{input p=['name'=>'gallery_password', 'type'=>'password', 'placeholder'=>'Passwort']}
									<button type="submit" class="btn btn-primary">Öffnen</button>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>

		{else}
			<h1 class="h3 mb-1">{$G.Title|default:'(ohne Titel)'|escape}</h1>
			<p class="text-secondary mb-4">
				{if $G.DateFrom || $G.DateTo}<i class="fa-regular fa-calendar me-1"></i>{$G.DateFrom|escape}{if $G.DateTo} &ndash; {$G.DateTo|escape}{/if} &middot; {/if}
				{count($G.Images)} Bilder
			</p>

			{if $G.Images}
			<div class="row g-2 row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6">
				{foreach $G.Images as $img}
				<div class="col">
					<a href="file/{$img.FileId}_2000x2000.avif" class="glightbox3 ratio ratio-1x1 rounded overflow-hidden bg-light" data-download="file/{$img.FileId}_2000x2000.jpg" data-gallery="gallery1">
						<picture>
							<source srcset="file/{$img.FileId}_200x200.avif" type="image/avif">
							<source srcset="file/{$img.FileId}_200x200.webp" type="image/webp">
							<img class="w-100 h-100 object-fit-cover" src="file/{$img.FileId}_200x200.png" alt="" loading="lazy">
						</picture>
					</a>
				</div>
				{/foreach}
			</div>
			{else}
				<div class="alert alert-info">In diesem Album sind noch keine Bilder.</div>
			{/if}

			{* Lightbox: https://biati-digital.github.io/glightbox/ *}
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
			<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
			{literal}
			<style>
				.gdownload-custom { position: absolute; right: 75px; top: 15px; z-index: 99999; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; color: #fff; opacity: .7; transition: opacity .2s; }
				.gdownload-custom:hover { opacity: 1; }
			</style>
			<script>
				var lightbox = GLightbox({ selector: '.glightbox3', closeOnOutsideClick: false });

				// Download-Button neben dem Schließen-Kreuz
				lightbox.on('open', function() {
					var closeBtn = document.querySelector('.gclose');
					if (!closeBtn || document.querySelector('.gdownload-custom')) return;
					var a = document.createElement('a');
					a.className = 'gdownload-custom';
					a.setAttribute('download', '');
					a.title = 'Original herunterladen';
					a.innerHTML = '<i class="fa-solid fa-download fa-lg"></i>';
					closeBtn.parentNode.appendChild(a);
				});

				// bei jedem Bildwechsel den Download-Link aktualisieren
				lightbox.on('slide_changed', function(e) {
					var a = document.querySelector('.gdownload-custom');
					if (!a) return;
					var url = e.current.trigger.getAttribute('data-download');
					if (url) a.href = url;
					a.style.display = url ? 'inline-flex' : 'none';
				});
			</script>
			{/literal}
		{/if}
	</div>
{/block}
