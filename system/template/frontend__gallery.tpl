{** frontend gallery index **}
{block name="inner_body" prepend}
<h1>{$D.ALBUM.Title|escape}</h1>
{if $D.ALBUM.Password}
  <form method="post">
    <label>Passwort: <input type="password" name="gallery_password"></label>
    <input type="hidden" name="D[GalleryId]" value="{$D.ALBUM.Id}">
    <button type="submit">Öffnen</button>
  </form>
{else}



  <div class="gallery-start">
    <h1>{$D.ALBUM.D[{$D.R.CategoryId}].Title}</h1>
    {*if $D.ALBUM.D[{$D.R.CategoryId}].FileId}
      <picture>
			<source srcset="file/{$D.ALBUM.D[{$D.R.CategoryId}].FileId}_50x50.avif" type="image/avif">
			<source srcset="file/{$D.ALBUM.D[{$D.R.CategoryId}].FileId}_50x50.webp" type="image/webp">
			<img src="file/{$D.ALBUM.D[{$D.R.CategoryId}].FileId}_50x50.png">
		</picture>
    {/if*}
	{if {$D.ALBUM.DateFrom}}<p>{$D.ALBUM.DateFrom} - {$D.ALBUM.DateTo}</p>{/if}
    <div class="gallery-images">

		{foreach from=$D.ALBUM.D[{$D.R.CategoryId}].IMAGE.D item=img}
			<a href="file/{$img.FileId}_2000x2000.avif" class="glightbox3" data-download="file/{$img.FileId}_2000x2000.jpg" data-gallery="gallery1">	
				<picture>
					<source srcset="file/{$img.FileId}_200x200.avif" type="image/avif">
					<source srcset="file/{$img.FileId}_200x200.webp" type="image/webp">
					<img class="rounded" src="file/{$img.FileId}_200x200.png">
				</picture>
			</a>
		{/foreach}
		
    </div>
  </div>



{/if}





{*https://biati-digital.github.io/glightbox/*}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
	<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>

	
  
{*glightbx version 3.3.1 https://github.com/biati-digital/glightbox/tree/master*}
<script type="text/javascript">
    // 1. Initialisiere die Galerie ganz normal (ohne das fehleranfällige Template)
    const lightbox = GLightbox({ 
        selector: '.glightbox3',
		closeOnOutsideClick: false
    });

    // 2. Sobald die Lightbox das erste Mal geöffnet wird, bauen wir den Button oben rechts ein
    lightbox.on('open', () => {
        // Suche das Schließen-Kreuz, um unseren Button direkt daneben zu platzieren
        const closeBtn = document.querySelector('.gclose');
        
        if (closeBtn && !document.querySelector('.gdownload-custom')) {
            // Erstelle das Link-Element für den Download
            const downloadBtn = document.createElement('a');
            downloadBtn.className = 'gdownload-custom';
            downloadBtn.setAttribute('download', '');
            downloadBtn.title = 'Original herunterladen';
            
            // CSS-Styling, damit er sich perfekt links neben das Schließen-Kreuz (X) einfügt
            downloadBtn.style.cssText = `
                position: absolute;
                right: 75px;
                top: 15px;
                z-index: 99999;
                color: #fff;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                opacity: 0.7;
                transition: opacity 0.2s;
            `;
            
            // Ein sauberes SVG-Icon (Download-Pfeil) einfügen
            downloadBtn.innerHTML = `
                <svg xmlns="http://w3.org" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
            `;

            // Hover-Effekt per JS hinzufügen
            downloadBtn.addEventListener('mouseenter', () => downloadBtn.style.opacity = '1');
            downloadBtn.addEventListener('mouseleave', () => downloadBtn.style.opacity = '0.7');

            // Den Button in das Elternelement des Schließen-Buttons einfügen
            closeBtn.parentNode.appendChild(downloadBtn);
        }
    });

    // 3. Bei jedem Bildwechsel den Download-Link aktualisieren
    lightbox.on('slide_changed', ({ current }) => {
        const customDownloadBtn = document.querySelector('.gdownload-custom');
        if (customDownloadBtn) {
            // Hole das data-download Attribut des aktuellen Bildes
            const downloadUrl = current.trigger.getAttribute('data-download');
            
            if (downloadUrl) {
                customDownloadBtn.href = downloadUrl;
                customDownloadBtn.style.display = 'inline-flex'; // Anzeigen, wenn URL vorhanden
            } else {
                customDownloadBtn.style.display = 'none'; // Verstecken, falls kein Download-Pfad existiert
            }
        }
    });
</script>


{/block}