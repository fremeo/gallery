<h1>Photo Gallery</h1>
{if $D.GALLERY_LIST}
  <ul>
  {foreach from=$D.GALLERY_LIST key=id item=cat}
    <li>
      <a href="?R[ModuleId]=fremeo/gallery&R[Page]=frontend__gallery&Id={$id}">{$cat.Title|escape}</a>
      <div>{$cat.DateFrom} - {$cat.DateTo}</div>
    </li>
  {/foreach}
  </ul>
{else}
  <p>Keine Kategorien gefunden.</p>
{/if}
