<h1>Photo Gallery</h1>
{if $D.R.GalleryList}
  <ul>
  {foreach from=$D.R.GalleryList key=id item=cat}
    <li>
      <a href="?R[ModuleId]=fremeo/gallery&R[Page]=frontend__gallery&Id={$id}">{$cat.Title|escape}</a>
      <div>{$cat.DateFrom} - {$cat.DateTo}</div>
    </li>
  {/foreach}
  </ul>
{else}
  <p>Keine Kategorien gefunden.</p>
{/if}
