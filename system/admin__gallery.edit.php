<?php
// Admin edit/create category and handle multi-upload

$Id = $_REQUEST['Id'] ?? null;
// also accept Id from posted structure
$postedId = $_POST['D']['GALLERY_CATEGORY']['Id'] ?? null;
if(!$Id && $postedId) $Id = $postedId;
#$D['GALLERY_CATEGORY'] = [];
$D['UPLOADED'] = [];

if(($D['ACTION']??null) == 'save') {
    $C['fremeo/gallery']['CData']->set_object($D); 
    $D['MSG'] = 'Gespeichert';
    return;
}

if(isset($_FILES['images'])) {
    // handle multiple images upload: copy to module data dir and register in CData
    $uploadDir = PROJECT_ROOT . 'data/fremeo~gallery/files/';
    @mkdir($uploadDir,0755,true);

    foreach($_FILES['images']['tmp_name'] as $i => $tmp){
        if(is_uploaded_file($tmp)){
            $name = basename($_FILES['images']['name'][$i]);
            $dst = $uploadDir . time() . '_' . $name;
            if(move_uploaded_file($tmp, $dst)){
                // compute md5 and rename file to md5.<ext>
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $md5 = md5_file($dst);
                $finalName = $md5 . ($ext ? '.' . $ext : '');
                $finalPath = $uploadDir . $finalName;
                rename($dst, $finalPath);

                // register file metadata under global FILE pattern with md5 as identifier
                $fileMeta = [
                    'Name' => $name,
                    'Size' => filesize($finalPath),
                    'Extension' => $ext,
                    'Active' => 1,
                ];
                if(isset($C['fremeo/gallery']['CData'])){
                    $dFile = ['FILE' => ['D' => [ $md5 => $fileMeta ] ]];
                    $C['fremeo/gallery']['CData']->set_object($dFile);
                }

                // ensure category id exists
                if(!$Id) {
                    $Id = md5(uniqid('gallery', true));
                    // create minimal category record
                    $dCat = ['GALLERY_CATEGORY' => ['D' => [ $Id => ['Title' => '', 'Active' => 1] ] ]];
                    $C['fremeo/gallery']['CData']->set_object($dCat);
                }

                // register gallery image as nested IMAGE under the category
                $imageId = md5(uniqid($md5, true));
                $img = ['Active'=>1,'FileId'=>$md5,'Title'=>$name,'Sort'=>0];
                if(isset($C['fremeo/gallery']['CData'])){
                    $dImg = ['GALLERY_CATEGORY' => ['D' => [ $Id => [ 'IMAGE' => [ $imageId => $img ] ] ] ]];
                    $C['fremeo/gallery']['CData']->set_object($dImg);
                }

                $D['UPLOADED'][] = ['Title'=>$name,'FileId'=>$md5];
            }
        }
    }
    $D['MSG'] = 'Upload fertig';

}

// load existing category for edit

if($R['Id'] && isset($C['fremeo/gallery']['CData'])){
    $out = [];
    $f['GALLERY_CATEGORY']['W'][0]['ID'] = $R['Id'];
	$f['GALLERY_CATEGORY']['IMAGE'] = [];
    $C['fremeo/gallery']['CData']->get_object($D, $f);
}

return;
