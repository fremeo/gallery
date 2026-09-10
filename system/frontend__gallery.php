<?php

	$F['ALBUM']['W'][0]['ID'] = $R['CategoryId']; 
    $F['ALBUM']['IMAGE'] = []; 


	$C['fremeo/gallery']['CData']->get_object($D,$F);

// Frontend page wrapper expected by core: frontend__gallery
// Delegate to existing gallery logic (gallery.php)
##require_once __DIR__ . '/gallery.php';

// gallery.php fills $D['GALLERY'] for template rendering
return;
