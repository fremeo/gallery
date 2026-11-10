<?php
namespace fremeo\gallery\tests;

use PHPUnit\Framework\TestCase;
use phploader\CData;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integrationstest: frontend__gallery.php reicht $D an get_object durch, die Albumdaten (auch per Pattern ergänzte Felder wie DateTimeOriginal)
 * stehen danach in $D.ALBUM.D.<Id> für das Template bereit.
 */
class FrontendGalleryTest extends TestCase {

	private string $tmp;
	private string $oldCwd;
	private CData $db;

	protected function setUp(): void {
		$this->tmp = sys_get_temp_dir() . '/fremeo_gallery_fe_' . uniqid();
		mkdir($this->tmp);
		$this->oldCwd = getcwd();
		chdir($this->tmp);

		$this->db = new CData(['DB' => ['FILENAME' => "{$this->tmp}/data.db", 'FILENAME_C' => "{$this->tmp}/data_c.db"]]);
		$this->db->registerPattern([
			'ALBUM' => [
				'Active' => ['Type' => 'checkbox'], 'Title' => ['Type' => 'text'], 'Password' => ['Type' => 'text'],
				'D' => ['IMAGE' => [
					'Active' => ['Type' => 'checkbox'], 'FileId' => ['Type' => 'id'], 'Sort' => ['Type' => 'number'], 'DateTimeOriginal' => ['Type' => 'number'],
				]],
			],
		]);

		$data = ['ALBUM' => ['D' => [
			'open' => ['Active' => 1, 'Title' => 'Offen', 'IMAGE' => ['D' => [
				'neu'   => ['Active' => 1, 'FileId' => 'neu',   'Sort' => 0, 'DateTimeOriginal' => 300],
				'alt'   => ['Active' => 1, 'FileId' => 'alt',   'Sort' => 2, 'DateTimeOriginal' => 100],
				'mitte' => ['Active' => 1, 'FileId' => 'mitte', 'Sort' => 1, 'DateTimeOriginal' => 200],
				'aus'   => ['Active' => 0, 'FileId' => 'aus',   'Sort' => 3, 'DateTimeOriginal' => 50],
			]]],
			'locked' => ['Active' => 1, 'Title' => 'Gesperrt', 'Password' => 'geheim', 'IMAGE' => ['D' => [
				'x' => ['Active' => 1, 'FileId' => 'x', 'Sort' => 0, 'DateTimeOriginal' => 1],
			]]],
			'inactive' => ['Active' => 0, 'Title' => 'Inaktiv'],
		]]];
		$this->db->set_object($data);
	}

	protected function tearDown(): void {
		unset($this->db);
		gc_collect_cycles();
		chdir($this->oldCwd);
		(new Filesystem())->remove($this->tmp);
	}

	/** Führt frontend__gallery.php aus und gibt [$D, $R] zurück. */
	private function page(array $R, array $D = []): array {
		$C = ['fremeo/gallery' => ['CData' => $this->db]];
		$_SESSION = [];
		(function () use (&$C, &$D, &$R) {
			include __DIR__ . '/../system/frontend__gallery.php';
		})();
		return [$D, $R];
	}

	public function testAlbumDataIsPassedThroughInD(): void {
		[$D, $R] = $this->page(['Id' => 'open']);

		$album = $D['ALBUM']['D']['open'];
		$this->assertSame('Offen', $album['Title']);
		// Feld aus dem Pattern ist ohne Anpassung der Seite im Template verfügbar, Reihenfolge aufsteigend nach DateTimeOriginal.
		// Inaktive Bilder bleiben mit Active=0 erhalten, das Template entscheidet, was es anzeigt.
		$this->assertSame(['aus' => 50, 'alt' => 100, 'mitte' => 200, 'neu' => 300], array_map(fn($i) => (int)$i['DateTimeOriginal'], $album['IMAGE']['D']));
		$this->assertSame(['aus', 'alt', 'mitte', 'neu'], array_keys($album['IMAGE']['D']));
		$this->assertEquals(0, $album['IMAGE']['D']['aus']['Active']);
		$this->assertEquals(1, $album['IMAGE']['D']['alt']['Active']);
		$this->assertFalse($R['Gallery']['Locked']);
		$this->assertArrayNotHasKey('Password', $album);
	}

	public function testPreviewTemplatesUseUnpaddedImagesAndThemeBackgrounds(): void {
		$smarty = new \Smarty();
		$smarty->setCompileDir($this->tmp);
		$smarty->error_reporting = E_ALL & ~E_WARNING & ~E_DEPRECATED;
		$smarty->assign('D', ['R' => ['Gallery' => ['Id' => 'open', 'Back' => 'gallery', 'NotFound' => false, 'Locked' => false], 'GalleryAlbums' => [
			'open' => ['FileId' => 'preview', 'Title' => 'Album', 'Url' => 'gallery/album', 'Locked' => false, 'DateFrom' => '', 'DateTo' => ''],
		]], 'ALBUM' => ['D' => ['open' => ['Title' => 'Album', 'IMAGE' => ['D' => [
			'image' => ['FileId' => 'preview', 'Active' => 1],
		]]]]]]);
		try {
			$album = $smarty->fetch(__DIR__ . '/../system/template/frontend__gallery.album.tpl');
			$images = $smarty->fetch('string:' . file_get_contents(__DIR__ . '/../../core/system/template/include/input.tpl')
				. file_get_contents(__DIR__ . '/../system/template/frontend__gallery.tpl'));
			$this->assertStringContainsString('bg-body-tertiary', $album);
			$this->assertStringContainsString('file/preview_480.avif', $album);
			$this->assertStringNotContainsString('_480x360', $album);
			$this->assertStringContainsString('file/preview_200.avif', $images);
			$this->assertStringNotContainsString('_200x200', $images);
			$this->assertStringContainsString('background: var(--bs-tertiary-bg)', $images);
			$this->assertStringContainsString('object-fit: contain', $images);
		} finally {
			$smarty->clearAllAssign();
		}
	}

	public function testLockedAlbumHasNoImagesAndNoPassword(): void {
		[$D, $R] = $this->page(['Id' => 'locked']);

		$this->assertTrue($R['Gallery']['Locked']);
		$this->assertSame('Gesperrt', $D['ALBUM']['D']['locked']['Title']);
		$this->assertArrayNotHasKey('IMAGE', $D['ALBUM']['D']['locked']);
		$this->assertArrayNotHasKey('Password', $D['ALBUM']['D']['locked']);
	}

	public function testCorrectPasswordOpensAlbum(): void {
		[$D, $R] = $this->page(['Id' => 'locked', 'Passwort' => 'geheim']);

		$this->assertFalse($R['Gallery']['Locked']);
		$this->assertSame(['x'], array_keys($D['ALBUM']['D']['locked']['IMAGE']['D']));
	}

	public function testInactiveOrUnknownAlbumIsNotFoundAndLeavesNoData(): void {
		foreach (['inactive', 'gibtesnicht', ''] as $id) {
			[$D, $R] = $this->page(['Id' => $id]);
			$this->assertTrue($R['Gallery']['NotFound'], "Id '{$id}'");
			$this->assertArrayNotHasKey('ALBUM', $D);
		}
	}

	public function testPostedAlbumDataIsNotMixedIntoOutput(): void {
		// Angreifer schickt D[ALBUM][D][gibtesnicht][Active]=1 mit eigenen Bildern
		$forged = ['ALBUM' => ['D' => ['gibtesnicht' => ['Active' => 1, 'Title' => 'Fake', 'IMAGE' => ['D' => ['f' => ['FileId' => 'f']]]]]]];
		[$D, $R] = $this->page(['Id' => 'gibtesnicht'], $forged);

		$this->assertTrue($R['Gallery']['NotFound']);
		$this->assertArrayNotHasKey('ALBUM', $D);
	}
}
