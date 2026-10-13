<?php
namespace fremeo\gallery\tests;

use fremeo\core\File;
use PHPUnit\Framework\TestCase;
use phploader\CData;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integrationstest: admin__gallery.list.php löscht markierte Alben samt Bildern.
 * Arbeitet mit einer eigenen temporären Datenbank und einem temporären Arbeitsverzeichnis.
 */
class AdminListDeleteTest extends TestCase {

	private string $tmp;
	private string $oldCwd;
	private array $C;
	private CData $db;

	protected function setUp(): void {
		$this->tmp = sys_get_temp_dir() . '/fremeo_gallery_' . uniqid();
		mkdir($this->tmp);
		$this->oldCwd = getcwd();
		chdir($this->tmp);

		$this->db = new CData(['DB' => ['FILENAME' => "{$this->tmp}/data.db", 'FILENAME_C' => "{$this->tmp}/data_c.db"]]);
		$this->db->registerPattern([
			'FILE' => ['Name' => ['Type' => 'text'], 'Size' => ['Type' => 'number'], 'Extension' => ['Type' => 'text']],
			'ALBUM' => [
				'Active' => ['Type' => 'checkbox'], 'Title' => ['Type' => 'text'], 'FileId' => ['Type' => 'id'], 'LinkId' => ['Type' => 'id'],
				'D' => ['IMAGE' => ['Active' => ['Type' => 'checkbox'], 'FileId' => ['Type' => 'id'], 'Sort' => ['Type' => 'number']]],
			],
		]);

		$this->C = ['Filesystem' => new Filesystem(), 'fremeo/gallery' => ['CData' => $this->db]];
		$this->C['fremeo/gallery']['File'] = new File($this->C, 'fremeo/gallery');
	}

	protected function tearDown(): void {
		unset($this->C, $this->db);
		gc_collect_cycles();
		chdir($this->oldCwd);
		(new Filesystem())->remove($this->tmp);
	}

	/** Legt ein Album mit Bildern an (Dateien auf der Platte und in FILE). Das erste Bild ist das Startbild. */
	private function createAlbum(string $id, array $contents): array {
		$files = [];
		$images = [];
		foreach (array_values($contents) as $i => $content) {
			$fid = md5($content);
			$files[$fid] = ['Name' => "bild{$i}", 'Size' => strlen($content), 'Extension' => 'jpg'];
			(new Filesystem())->dumpFile("data/fremeo~gallery/file/{$fid}.jpg", $content);
			$images[$fid] = ['Active' => 1, 'FileId' => $fid, 'Sort' => $i];
		}
		$data = ['FILE' => ['D' => $files], 'ALBUM' => ['D' => [$id => [
			'Active' => 1, 'Title' => $id, 'FileId' => array_key_first($files), 'IMAGE' => ['D' => $images],
		]]]];
		$this->db->set_object($data);
		return array_keys($files);
	}

	/** Führt admin__gallery.list.php wie bei einem Klick auf "Speichern" aus. */
	private function save(array $post): void {
		$run = function () use ($post) {
			$C = &$this->C;
			$D = ['ACTION' => 'save', 'ALBUM' => ['D' => $post]];
			$R = [];
			include __DIR__ . '/../system/admin__gallery.list.php';
		};
		$run();
	}

	private function fileEntries(): array {
		$f['FILE'] = [];
		$this->db->get_object($d, $f);
		return array_keys($d['FILE']['D'] ?? []);
	}

	public function testDeleteAlbumRemovesAllImagesAndDatabaseEntries(): void {
		$ids = $this->createAlbum('a1', ['eins', 'zwei', 'drei', 'vier']);
		$this->assertCount(4, $this->fileEntries());

		$this->save(['a1' => ['__DELETE__' => 1]]);

		foreach ($ids as $fid) {
			$this->assertFileDoesNotExist("data/fremeo~gallery/file/{$fid}.jpg");
		}
		$this->assertSame([], $this->fileEntries());

		$f['ALBUM'] = [];
		$this->db->get_object($d, $f);
		$this->assertEmpty($d['ALBUM']['D'] ?? []);
	}

	public function testFileUsedByOtherAlbumIsKept(): void {
		$a = $this->createAlbum('a1', ['eins', 'gemeinsam']);
		$this->createAlbum('a2', ['gemeinsam']);

		$this->save(['a1' => ['__DELETE__' => 1]]);

		$this->assertFileDoesNotExist("data/fremeo~gallery/file/{$a[0]}.jpg");
		$this->assertFileExists('data/fremeo~gallery/file/' . md5('gemeinsam') . '.jpg');
		$this->assertContains(md5('gemeinsam'), $this->fileEntries());
		$this->assertNotContains($a[0], $this->fileEntries());
	}

	public function testOtherAlbumsAreUntouched(): void {
		$this->createAlbum('a1', ['eins', 'zwei']);
		$b = $this->createAlbum('a2', ['drei']);

		$this->save(['a1' => ['__DELETE__' => 1], 'a2' => ['Active' => 0]]);

		$this->assertFileExists("data/fremeo~gallery/file/{$b[0]}.jpg");
		$this->assertSame($b, $this->fileEntries());
	}
}
