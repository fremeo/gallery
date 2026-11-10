<?php
namespace fremeo\gallery\tests;

use fremeo\core\File;
use fremeo\core\Container;
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
	private Container $C;
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

		$this->C = new Container();
		$this->C->set('Filesystem', new Filesystem());
		$this->C->set('CData', $this->db, 'fremeo/gallery');
		$this->C->set('File', new class($this->C, 'fremeo/gallery') extends File {
			protected function isUpload(string $tmp): bool { return is_file($tmp); } // in Tests gibt es keine echten HTTP-Uploads
		}, 'fremeo/gallery');
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
			$R = ['Id' => 'album1', 'ACTION' => 'save'] + ($modified === null ? [] : ['FileModified' => json_encode($modified)]);
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

	private function queueRequest(array $files, array $request = [], array $data = []): array {
		$this->db->get_Pattern($pattern);
		$script = '<?php require ' . var_export(__DIR__ . '/bootstrap.php', true) . '; chdir(' . var_export($this->tmp, true) . ');'
			. '$db = new \phploader\CData(["DB"=>["FILENAME"=>"data.db","FILENAME_C"=>"data_c.db"]]);'
			. '$db->registerPattern(' . var_export($pattern['PATTERN'], true) . ');'
			. '$C = new \fremeo\core\Container(); $C->set("Filesystem",new \Symfony\Component\Filesystem\Filesystem()); $C->set("CData",$db,"fremeo/gallery");'
			. '$C->set("File",new class($C,"fremeo/gallery") extends \fremeo\core\File { protected function isUpload(string $tmp): bool { return is_file($tmp); } },"fremeo/gallery");'
			. '$_SESSION=["GalleryUploadToken"=>"test-token"]; $_SERVER["REQUEST_METHOD"]="POST"; $D=' . var_export($data, true) . ';'
			. '$R=' . var_export($request + ['Id' => 'album1', 'ACTION' => 'save', 'UploadQueue' => '1', 'UploadOnly' => '1', 'UploadToken' => 'test-token'], true) . ';'
			. '$_FILES=' . var_export($files, true) . '; include ' . var_export(__DIR__ . '/../system/admin__gallery.edit.php', true) . ';';
		$file = $this->tmp . '/queue-request.php';
		file_put_contents($file, $script);
		$process = proc_open([PHP_BINARY, $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
		$this->assertIsResource($process);
		$output = stream_get_contents($pipes[1]);
		$error = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$this->assertSame(0, proc_close($process), $error);
		return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
	}

	public function testQueuedUploadPreservesMetadataCoverAndOrderAndRejectsMissingFiles(): void {
		$images = $this->upload(['original.png' => $this->png(0xFF0000)]);
		$cover = array_key_first($images);
		$update = ['ALBUM' => ['D' => ['album1' => ['Title' => 'Unchanged', 'FileId' => $cover]]]];
		$this->db->set_object($update);
		$tmp = $this->tmp . '/queued.png';
		file_put_contents($tmp, $this->png(0x00FF00));
		$files = ['images' => ['name' => ['queued.png'], 'tmp_name' => [$tmp], 'error' => [UPLOAD_ERR_OK], 'size' => [filesize($tmp)]]];
		$this->assertSame(['saved' => true, 'received' => 1], $this->queueRequest($files, ['FileModified' => '{"queued.png":1600000000123}']));
		// Eine verlorene Antwort darf beim erneuten Senden kein zweites Bild erzeugen.
		$this->assertSame(['saved' => true, 'received' => 1], $this->queueRequest($files));
		$f = ['ALBUM' => ['W' => [0 => ['ID' => 'album1']], 'IMAGE' => []]];
		$this->db->get_object($stored, $f);
		$album = $stored['ALBUM']['D']['album1'];
		$this->assertSame('Unchanged', $album['Title']);
		$this->assertSame($cover, $album['FileId']);
		$this->assertCount(2, $album['IMAGE']['D']);
		$this->assertSame(1, (int)$album['IMAGE']['D'][md5_file($tmp)]['Sort']);
		$this->assertSame(1600000000, (int)$album['IMAGE']['D'][md5_file($tmp)]['DateTimeOriginal']);
		$this->assertArrayHasKey('error', $this->queueRequest([]));
		$this->assertArrayHasKey('error', $this->queueRequest($files, ['UploadToken' => 'wrong']));
		$files['images']['error'][0] = UPLOAD_ERR_INI_SIZE;
		$this->assertStringContainsString('queued.png', $this->queueRequest($files)['error']);
	}

	public function testQueueSavesFormOnceAndStillAcceptsZipArchives(): void {
		$this->upload(['original.png' => $this->png(0xFF0000)]);
		$this->assertSame(['saved' => true, 'received' => 0], $this->queueRequest([], ['UploadOnly' => '0'],
			['ALBUM' => ['D' => ['album1' => ['Title' => 'Changed']]]]));
		$path = $this->tmp . '/images.zip';
		$zip = new \ZipArchive();
		$zip->open($path, \ZipArchive::CREATE);
		$zip->addFromString('archived.png', $this->png(0x00FF00));
		$zip->close();
		$files = ['images' => ['name' => ['images.zip'], 'tmp_name' => [$path], 'error' => [UPLOAD_ERR_OK], 'size' => [filesize($path)]]];
		$this->assertSame(['saved' => true, 'received' => 1], $this->queueRequest($files));
		$f = ['ALBUM' => ['W' => [0 => ['ID' => 'album1']], 'IMAGE' => []]];
		$this->db->get_object($stored, $f);
		$this->assertSame('Changed', $stored['ALBUM']['D']['album1']['Title']);
		$this->assertCount(2, $stored['ALBUM']['D']['album1']['IMAGE']['D']);
	}
}
