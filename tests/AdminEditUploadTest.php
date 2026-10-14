<?php
namespace fremeo\gallery\tests;

use fremeo\core\File;
use PHPUnit\Framework\TestCase;
use phploader\CData;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Integrationstest: admin__gallery.edit.php speichert beim Upload ALBUM.IMAGE.DateTimeOriginal aus den Bilddaten:
 * EXIF-Aufnahmedatum, sonst Dateidatum des Browsers, sonst kein Datum (nie der Upload-Zeitpunkt).
 */
class AdminEditUploadTest extends TestCase {

	private string $tmp;
	private string $oldCwd;
	private array $C;
	private CData $db;

	protected function setUp(): void {
		$this->tmp = sys_get_temp_dir() . '/fremeo_gallery_up_' . uniqid();
		mkdir($this->tmp);
		$this->oldCwd = getcwd();
		chdir($this->tmp);

		$this->db = new CData(['DB' => ['FILENAME' => "{$this->tmp}/data.db", 'FILENAME_C' => "{$this->tmp}/data_c.db"]]);
		$this->db->registerPattern([
			'FILE' => ['Name' => ['Type' => 'text'], 'Size' => ['Type' => 'number'], 'Extension' => ['Type' => 'text']],
			'ALBUM' => [
				'Active' => ['Type' => 'checkbox'], 'Title' => ['Type' => 'text'], 'FileId' => ['Type' => 'id'], 'LinkId' => ['Type' => 'id'],
				'D' => ['IMAGE' => [
					'Active' => ['Type' => 'checkbox'], 'FileId' => ['Type' => 'id'], 'Sort' => ['Type' => 'number'], 'DateTimeOriginal' => ['Type' => 'number'],
				]],
			],
		]);

		$this->C = ['Filesystem' => new Filesystem(), 'fremeo/gallery' => ['CData' => $this->db]];
		$this->C['fremeo/gallery']['File'] = new class($this->C, 'fremeo/gallery') extends File {
			protected function isUpload(string $tmp): bool { return is_file($tmp); } // in Tests gibt es keine echten HTTP-Uploads
		};
	}

	protected function tearDown(): void {
		unset($this->C, $this->db);
		gc_collect_cycles();
		chdir($this->oldCwd);
		(new Filesystem())->remove($this->tmp);
	}

	/** JPEG mit EXIF-Segment (DateTimeOriginal im Exif-IFD), ohne Bibliotheken zum Schreiben von EXIF. */
	private function jpegWithExif(string $dateTimeOriginal, int $color): string {
		$im = imagecreatetruecolor(8, 8);
		imagefill($im, 0, 0, $color);
		ob_start();
		imagejpeg($im);
		$jpeg = (string)ob_get_clean();

		$tiff = 'MM' . pack('n', 42) . pack('N', 8)                    // Big Endian, IFD0 bei Offset 8
			. pack('n', 1) . pack('nnNN', 0x8769, 4, 1, 26) . pack('N', 0) // IFD0: Zeiger auf das Exif-IFD
			. pack('n', 1) . pack('nnNN', 0x9003, 2, 20, 44) . pack('N', 0) // Exif-IFD: DateTimeOriginal, ASCII, 20 Byte bei Offset 44
			. $dateTimeOriginal . "\0";
		$app1 = "\xFF\xE1" . pack('n', 2 + 6 + strlen($tiff)) . "Exif\0\0" . $tiff;

		return substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
	}

	private function png(int $color): string {
		$im = imagecreatetruecolor(8, 8);
		imagefill($im, 0, 0, $color);
		ob_start();
		imagepng($im);
		return (string)ob_get_clean();
	}

	/** Lädt Dateien [Name => Inhalt] in das Album hoch (wie ein Klick auf "Speichern") und gibt ALBUM.IMAGE.D zurück. */
	private function upload(array $files, ?array $modified = null): array {
		$_FILES = ['images' => ['name' => [], 'tmp_name' => [], 'error' => [], 'size' => []]];
		foreach ($files as $name => $content) {
			$tmp = "{$this->tmp}/" . md5($name);
			file_put_contents($tmp, $content);
			$_FILES['images']['name'][] = $name;
			$_FILES['images']['tmp_name'][] = $tmp;
			$_FILES['images']['error'][] = 0;
			$_FILES['images']['size'][] = strlen($content);
		}

		(function () use ($modified) {
			$C = &$this->C;
			$R = ['Id' => 'album1'] + ($modified === null ? [] : ['FileModified' => json_encode($modified)]);
			$D = ['ACTION' => 'save', 'ALBUM' => ['D' => ['album1' => ['Active' => 1, 'Title' => 'Test']]]];
			include __DIR__ . '/../system/admin__gallery.edit.php';
		})();

		$f['ALBUM']['W'][0]['ID'] = 'album1';
		$f['ALBUM']['IMAGE']['O'][0]['Sort'] = 'ASC'; // Unterdaten (Bilder) müssen angefordert werden
		$this->db->get_object($d, $f);
		return (array)($d['ALBUM']['D']['album1']['IMAGE']['D'] ?? []);
	}

	public function testExifDateIsReadFromImage(): void {
		$expected = (new \DateTime('2021-07-04 15:30:12'))->getTimestamp(); // EXIF kennt keine Zeitzone, es gilt die des Servers

		$images = $this->upload(['urlaub.jpg' => $this->jpegWithExif('2021:07:04 15:30:12', 0xFF0000)]);

		$this->assertCount(1, $images);
		$this->assertSame($expected, (int)array_values($images)[0]['DateTimeOriginal']);
	}

	public function testExifDateWinsOverBrowserFileDate(): void {
		$expected = (new \DateTime('2021-07-04 15:30:12'))->getTimestamp();

		$images = $this->upload(['urlaub.jpg' => $this->jpegWithExif('2021:07:04 15:30:12', 0xFF0000)], ['urlaub.jpg' => 1600000000000]);

		$this->assertSame($expected, (int)array_values($images)[0]['DateTimeOriginal']);
	}

	public function testBrowserFileDateIsUsedWhenImageHasNoExif(): void {
		$images = $this->upload(['Screenshot.PNG' => $this->png(0x00FF00)], ['screenshot.png' => 1600000000123]);

		$this->assertSame(1600000000, (int)array_values($images)[0]['DateTimeOriginal']);
	}

	public function testNoDateIsStoredWithoutExifAndWithoutBrowserDate(): void {
		$images = $this->upload(['ohne.png' => $this->png(0x0000FF)]);

		$this->assertCount(1, $images);
		$this->assertArrayNotHasKey('DateTimeOriginal', array_values($images)[0]); // kein Upload-Zeitpunkt
	}

	public function testInvalidExifDateIsIgnored(): void {
		$images = $this->upload(['kaputt.jpg' => $this->jpegWithExif('0000:00:00 00:00:00', 0x808080)]);

		$this->assertArrayNotHasKey('DateTimeOriginal', array_values($images)[0]);
	}
}
