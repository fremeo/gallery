<?php
// Admin edit/create category and handle multi-upload

$Id = $_REQUEST['Id'] ?? null;
// also accept Id from posted structure
$postedId = $_POST['D']['ALBUM']['Id'] ?? null;
if(!$Id && $postedId) $Id = $postedId;
#$D['ALBUM'] = [];
$D['UPLOADED'] = [];

if(($D['ACTION']??null) == 'save') {
    $C['fremeo/gallery']['CData']->set_object($D); 
    $D['MSG'] = 'Gespeichert';
    return;
}

if(isset($_FILES['images'])) {

	$_arrImg = $C['fremeo/gallery']['File']->add($_FILES['images']);
	if($_arrImg) {
		foreach($_arrImg AS $kIMG => $IMG) {
			$img = ['Active'=>1,'FileId'=>$kIMG,'Title'=>$IMG['Name'],'Sort'=>0];
			$dImg = ['ALBUM' => ['D' => [ $D['R']['Id'] => [ 'IMAGE' => [ 'D' => [ $kIMG => $img ] ] ] ] ]];
			
			$C['fremeo/gallery']['CData']->set_object($dImg);
		}
	}


}

// load existing category for edit

if($R['Id'] && isset($C['fremeo/gallery']['CData'])) {

    $f['ALBUM']['W'][0]['ID'] = $R['Id'];
	$f['ALBUM']['IMAGE'] = [];
    $C['fremeo/gallery']['CData']->get_object($D, $f);
}

return;
