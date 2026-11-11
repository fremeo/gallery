<?php
namespace fremeo\gallery\tests;

use fremeo\core\Container;
use fremeo\core\Security;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class AdminListTemplateTest extends TestCase {
	public function testListActionsAndPagingAreOutsideTheForm(): void {
		$tmp = sys_get_temp_dir() . '/fremeo_gallery_list_' . uniqid();
		mkdir($tmp);
		$container = new Container();
		$container->set('Security', new Security());
		$smarty = new \Smarty();
		$smarty->setCompileDir($tmp);
		$data = ['Rows' => ['a1' => [
			'Title' => 'Album', 'Active' => 1, 'FileId' => '', 'DateFrom' => '', 'DateTo' => '', 'Locked' => false,
		]], 'From' => 11, 'To' => 20, 'Total' => 21, 'Page' => 2, 'Pages' => 3, 'PageList' => [1, 2, 3]];
		$smarty->assign('D', ['C' => $container, 'R' => ['GalleryAdmin' => $data]]);
		$template = 'string:' . file_get_contents(__DIR__ . '/../../core/system/template/include/input.tpl')
			. file_get_contents(__DIR__ . '/../system/template/admin__gallery.list.tpl');
		$previous = libxml_use_internal_errors(true);
		try {
			$html = $smarty->fetch($template);
			$document = new \DOMDocument();
			$document->loadHTML($html);
			$xpath = new \DOMXPath($document);
			$this->assertSame(1, $xpath->query('//form[@id="galleryListForm"]')->length);
			$this->assertSame(1, $xpath->query('//button[@type="submit"][@form="galleryListForm"]')->length);
			$this->assertSame(0, $xpath->query('//form//button[@type="submit"] | //form//nav')->length);
			$this->assertSame(1, $xpath->query('//a[text()="Abbrechen"][@href="admin/fremeo/gallery/list?R[P]=2"]')->length);
			$this->assertSame(1, $xpath->query('//nav[@aria-label="Seiten"]//li[contains(@class,"active")]/a[text()="2"]')->length);
			$this->assertStringContainsString('11&ndash;20 von 21', $html);
			$this->assertStringContainsString('bg-body-tertiary border-top p-2', $html);
			$data['Rows'] = [];
			$smarty->assign('D', ['C' => $container, 'R' => ['GalleryAdmin' => $data]]);
			$html = $smarty->fetch($template);
			$this->assertStringContainsString('Keine Alben vorhanden.', $html);
			$this->assertStringNotContainsString('form="galleryListForm"', $html);
		} finally {
			libxml_clear_errors();
			libxml_use_internal_errors($previous);
			$smarty->clearAllAssign();
			unset($smarty);
			(new Filesystem())->remove($tmp);
		}
	}
}
