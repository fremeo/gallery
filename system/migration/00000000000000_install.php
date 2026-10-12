<?php
namespace fremeo\gallery;

return new class($D) {

	function __construct($D) {
		$this->C = $D['C'];
		$this->ModuleId = 'fremeo/gallery';
	}

	function up() {
		#SEO Link zur Alben-Liste im Backend: admin/fremeo/gallery/list
		$D['LINK']['D'][hash("crc32b", 'admin/fremeo/gallery/list')] = ['Active' => 1, 'FromURL' => 'admin/fremeo/gallery/list', 'ToURL' => 'R[Page]=admin__gallery.list'];
		$D['LINK']['D'][hash("crc32b", 'admin/fremeo/gallery/edit')] = ['Active' => 1, 'FromURL' => 'admin/fremeo/gallery/edit', 'ToURL' => 'R[Page]=admin__gallery.edit'];
		$D['LINK']['D'][hash("crc32b", 'gallery')] = ['Active' => 1, 'FromURL' => 'gallery', 'ToURL' => 'R[Page]=frontend__gallery.album&R[ModuleId]=fremeo/gallery'];
		
		$this->C[$this->ModuleId]['CData']->set_object($D);
	}

	function down() {
		$D['LINK']['D'][hash("crc32b", 'admin/fremeo/gallery/list')] = '__DELETE__';
		$D['LINK']['D'][hash("crc32b", 'admin/fremeo/gallery/edit')] = '__DELETE__';
		$D['LINK']['D'][hash("crc32b", 'gallery')] = '__DELETE__';

		$this->C[$this->ModuleId]['CData']->set_object($D);
	}
};
