<?php
// frontend controller for gallery pages

$page = $D['R']['Page'] ?? null;

if($page === 'frontend__gallery'){
    // determine category id from request
    $catId = $R['Id'] ?? null;

    // fetch category
    $f=[]; $cat=[];
    if(isset($C['fremeo/gallery']['CData'])){
        $C['fremeo/gallery']['CData']->get_object($cat,$f);
    }

    $G = $cat['GALLERY_CATEGORY']['D'][$catId] ?? null;
    if(!$G){
        $D['_PAGE'] = 'error.404';
        return;
    }

    // password check
    if(!empty($G['Password'])){
        $entered = $_POST['gallery_password'] ?? null;
        if($entered !== $G['Password']){
            $D['GALLERY'] = $G;
            return; // template will show password form
        }
    }

    // gather images nested under the category
    $images = [];
    $filesMap = $cat['FILE']['D'] ?? [];
    $nested = $G['IMAGE'] ?? ($G['D']['IMAGE'] ?? []);
    if(is_array($nested)){
        foreach($nested as $img){
            $fileId = $img['FileId'] ?? null;
            if(!$fileId) continue;
            $fileUrl = null;
            if(isset($filesMap[$fileId])){
                $entry = $filesMap[$fileId];
                $ext = $entry['Extension'] ?? '';
                $fileUrl = PROJECT_ROOT . 'data/fremeo~gallery/files/' . $fileId . ($ext?'.'.$ext:'');
                $fileUrl = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(PROJECT_ROOT, '/', $fileUrl));
            } else {
                $fileUrl = '/data/fremeo~gallery/files/' . $fileId;
            }
            $images[] = ['File'=>$fileUrl,'Thumb'=>$fileUrl,'Title'=>$img['Title'] ?? ''];
        }
    }

    $D['GALLERY'] = [
        'Id'=>$catId,
        'Title'=>$G['Title'],
        'Password'=>$G['Password'] ?? null,
        'DateFrom'=>$G['DateFrom'] ?? null,
        'DateTo'=>$G['DateTo'] ?? null,
        'StartFile'=> $G['StartFileId'] ?? null,
        'IMAGES'=>$images
    ];

}
