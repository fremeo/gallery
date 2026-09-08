<?php
// module index controller - prepares data for template/index.tpl

$list = [];
if (isset($C['fremeo/gallery']['CData'])) {
    $f = [];
    $C['fremeo/gallery']['CData']->get_object($out, $f);
    foreach ($out['GALLERY_CATEGORY']['D'] ?? [] as $id => $cat) {
        $list[$id] = [
            'Title' => $cat['Title'] ?? '',
            'DateFrom' => $cat['DateFrom'] ?? '',
            'DateTo' => $cat['DateTo'] ?? ''
        ];
    }
}

$D['GALLERY_LIST'] = $list;
