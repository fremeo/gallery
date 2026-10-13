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
									{input p=['name'=>'R[Passwort]', 'type'=>'password', 'placeholder'=>'Passwort']}
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
			<div class="gallery-grid" id="gallery-grid">
				{foreach $G.Images as $img}
				<a href="file/{$img.FileId}_2000x2000.avif" class="gallery-item glightbox3{if $img@index >= 60} d-none gallery-more{/if}" data-download="file/{$img.FileId}_2000x2000.jpg" data-gallery="gallery1">
					<picture>
						<source srcset="file/{$img.FileId}_200x200.avif" type="image/avif">
						<source srcset="file/{$img.FileId}_200x200.webp" type="image/webp">
						<img src="file/{$img.FileId}_200x200.png" alt="" loading="lazy">
					</picture>
				</a>
				{/foreach}
			</div>
			{if count($G.Images) > 60}
			<div class="text-center mt-4">
				<button type="button" class="btn btn-outline-primary" id="gallery-more"><i class="fa-solid fa-angles-down me-1"></i> Weitere Bilder anzeigen <span class="badge text-bg-secondary" id="gallery-rest"></span></button>
			</div>
			{/if}
			{else}
				<div class="alert alert-info">In diesem Album sind noch keine Bilder.</div>
			{/if}

			{* Lightbox: https://biati-digital.github.io/glightbox/ *}
			<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
			<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
			{literal}
			<style>
				.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 6px; }
				.gallery-item { position: relative; display: block; aspect-ratio: 1 / 1; overflow: hidden; border-radius: .375rem; background: #f1f3f5; }
				.gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
				.gallery-item::after { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0); transition: background .3s; }
				.gallery-item:hover img { transform: scale(1.08); }
				.gallery-item:hover::after { background: rgba(0,0,0,.15); }
				@media (min-width: 992px) { .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; } }
				.gdownload-custom { position: absolute; right: 75px; top: 15px; z-index: 99999; width: 40px; height: 40px; display: inline-flex; align-items: center; justify-content: center; color: #fff; opacity: .7; transition: opacity .2s; }
				.gdownload-custom:hover { opacity: 1; }
			</style>
			<script>
				// Weitere Bilder schrittweise einblenden (60 pro Klick)
				(function() {
					var btn = document.getElementById('gallery-more');
					if (!btn) return;
					var rest = document.getElementById('gallery-rest');
					function update() {
						var n = document.querySelectorAll('.gallery-more.d-none').length;
						if (!n) btn.parentNode.remove(); else rest.textContent = n;
					}
					btn.addEventListener('click', function() {
						document.querySelectorAll('.gallery-more.d-none').forEach(function(el, i) {
							if (i < 60) el.classList.remove('d-none');
						});
						update();
					});
					update();
				})();

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
