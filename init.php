<?php
// init.php for fremeo/gallery

$Pattern = [];

$Pattern['ALBUM'] = [
    'Active' => ['Type' => 'checkbox'],
    'DateFrom' => ['Type' => 'text'],
    'DateTo' => ['Type' => 'text'],
    'Title' => ['Type' => 'text'],
    'Password' => ['Type' => 'text'],
    'FileId' => ['Type' => 'id'],
];

$Pattern['ALBUM']['D']['IMAGE'] = [
            'Active' => ['Type' => 'checkbox'],
            'FileId' => ['Type' => 'id'],
            'Sort' => ['Type' => 'number']
];


$C['fremeo/gallery']['CData'] = new \phploader\CData([
    'DB' => [
        'FILENAME' => PROJECT_ROOT . 'data/fremeo~gallery/data.db',
        'FILENAME_C' => PROJECT_ROOT . 'data_c/fremeo~gallery/data.db'
    ]
]);

// register patterns with keys
// register module-specific patterns (core will register global patterns such as FILE and LINK)
$C['fremeo/gallery']['CData']->registerPattern([
    'ALBUM' => $Pattern['ALBUM']
]);
/*
// ensure files directory exists
if (!is_dir(PROJECT_ROOT . 'data/fremeo~gallery/file/')) {
    @mkdir(PROJECT_ROOT . 'data/fremeo~gallery/file/', 0755, true);
}

// ensure module data dir exists
if (!is_dir(PROJECT_ROOT . 'data/fremeo~gallery/')) {
    @mkdir(PROJECT_ROOT . 'data/fremeo~gallery/', 0755, true);
}
*/

// expose minimal module metadata
/*
$D['MODULE']['D']['fremeo/gallery'] = $D['MODULE']['D']['fremeo/gallery'] ?? [];
$D['MODULE']['D']['fremeo/gallery']['Id'] = 'fremeo/gallery';
$D['MODULE']['D']['fremeo/gallery']['ModulDir'] = __DIR__;
$D['MODULE']['D']['fremeo/gallery']['CacheDir'] = 'data_c/fremeo~gallery/';
$D['MODULE']['D']['fremeo/gallery']['DataDir'] = 'data/fremeo~gallery/';
*/
// File upload handler: will be used by admin pages
// Note: actual upload handling implemented in admin__gallery.edit.php
