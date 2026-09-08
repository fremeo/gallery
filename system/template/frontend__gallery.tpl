{** frontend gallery index **}
{block name="inner_body" prepend}
<h1>{$D.GALLERY_CATEGORY.Title|escape}</h1>
{if $D.GALLERY_CATEGORY.Password}
  <form method="post">
    <label>Passwort: <input type="password" name="gallery_password"></label>
    <input type="hidden" name="D[GalleryId]" value="{$D.GALLERY_CATEGORY.Id}">
    <button type="submit">Öffnen</button>
  </form>
{else}
  <div class="gallery-start">
    {$D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].Title}
    {if $D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].FileId}
      <picture>
			<source srcset="file/{$D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].FileId}_50x50.avif" type="image/avif">
			<source srcset="file/{$D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].FileId}_50x50.webp" type="image/webp">
			<img src="file/{$D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].FileId}_50x50.png">
		</picture>
    {/if}
    <p>{$D.GALLERY_CATEGORY.DateFrom} - {$D.GALLERY_CATEGORY.DateTo}</p>
    <div class="gallery-images">
      {foreach from=$D.GALLERY_CATEGORY.D[{$D.R.CategoryId}].IMAGE.D item=img}
        <a href="#" class="gallery-thumb" data-file="./file/{$img.FileId}_1000x1000.jpg">
			<picture>
				<source srcset="file/{$img.FileId}_200x200.avif" type="image/avif">
				<source srcset="file/{$img.FileId}_200x200.webp" type="image/webp">
				<img src="file/{$img.FileId}_200x200.png">
			</picture>
        </a>
      {/foreach}
    </div>
  </div>
{/if}

<script>
document.addEventListener('DOMContentLoaded', function(){
  const thumbs = Array.from(document.querySelectorAll('.gallery-thumb'));
  if(!thumbs.length) return;
  const files = thumbs.map(t => t.dataset.file);

  thumbs.forEach((t,i) => t.addEventListener('click', function(e){
    e.preventDefault();
    openGallery(i);
  }));

  function openGallery(startIndex){
    let idx = startIndex;
    const modal = document.createElement('div');
    modal.className = 'gallery-modal';
    Object.assign(modal.style, {
      position: 'fixed', left:0, top:0, right:0, bottom:0,
      background: 'rgba(0,0,0,0.85)', display:'flex', alignItems:'center', justifyContent:'center', zIndex:9999
    });

    const container = document.createElement('div');
    Object.assign(container.style, { position:'relative', maxWidth:'90%', maxHeight:'90%'});

    const img = document.createElement('img');
    img.style.maxWidth='100%'; img.style.maxHeight='100%'; img.src = files[idx] || '';
    container.appendChild(img);

    const btnClose = document.createElement('button');
    btnClose.setAttribute('aria-label','Schließen');
    btnClose.innerHTML = '✕';
    Object.assign(btnClose.style, {
      position:'absolute', right:'-10px', top:'-10px', background:'#fff', border:'none', borderRadius:'50%', width:'36px', height:'36px', cursor:'pointer', fontSize:'18px', lineHeight:'1'
    });
    btnClose.addEventListener('click', close);
    container.appendChild(btnClose);

    const btnPrev = document.createElement('button');
    btnPrev.innerHTML = '◀';
    Object.assign(btnPrev.style, { position:'absolute', left:'-60px', top:'50%', transform:'translateY(-50%)', background:'transparent', border:'none', color:'#fff', fontSize:'34px', cursor:'pointer'});
    btnPrev.addEventListener('click', function(e){ e.stopPropagation(); idx = (idx - 1 + files.length) % files.length; update(); });
    container.appendChild(btnPrev);

    const btnNext = document.createElement('button');
    btnNext.innerHTML = '▶';
    Object.assign(btnNext.style, { position:'absolute', right:'-60px', top:'50%', transform:'translateY(-50%)', background:'transparent', border:'none', color:'#fff', fontSize:'34px', cursor:'pointer'});
    btnNext.addEventListener('click', function(e){ e.stopPropagation(); idx = (idx + 1) % files.length; update(); });
    container.appendChild(btnNext);

    modal.appendChild(container);

    modal.addEventListener('click', function(e){ if(e.target === modal) close(); });
    document.addEventListener('keydown', keyHandler);
    document.body.appendChild(modal);

    function update(){ img.src = files[idx] || ''; }
    function close(){ document.removeEventListener('keydown', keyHandler); modal.remove(); }
    function keyHandler(e){
      if(e.key === 'ArrowLeft'){ idx = (idx - 1 + files.length) % files.length; update(); }
      else if(e.key === 'ArrowRight'){ idx = (idx + 1) % files.length; update(); }
      else if(e.key === 'Escape'){ close(); }
    }
  }
});
</script>
{/block}