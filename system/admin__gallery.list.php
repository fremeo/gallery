<?php
// Admin list categories - populate $D for template admin__gallery.list.tpl


if(isset($C['fremeo/gallery']['CData'])) {
    $f['ALBUM']['IMAGE'] = [];
    $C['fremeo/gallery']['CData']->get_object($D,$f);
}

return;
