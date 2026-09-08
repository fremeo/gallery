<?php
// initial install migration for fremeo/gallery

return function(&$D, &$C) {
    // register that this migration exists — the core Migrations class will handle execution
    $D['MIGRATION']['D']['00000000000000'] = ['File' => '00000000000000_install.php','Timestamp' => time()];
    return true;
};
