<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\File\Image;
use InvalidArgumentException;

class ImageTest extends TestCase
{
    protected string $testImagePath;
    protected string $watermarkImagePath;

    protected function setUp(): void
    {
        // Skapa en testbild
        $image = imagecreatetruecolor(800, 600);

        $color = imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för testbilden.');
        }

        imagefill($image, 0, 0, $color); // Röd färg
        $this->testImagePath = __DIR__ . '/test_image.jpg';
        imagejpeg($image, $this->testImagePath);
        imagedestroy($image);

        // Skapa vattenmärkesbild
        $watermark = imagecreatetruecolor(100, 50);

        $wmColor = imagecolorallocate($watermark, 0, 0, 255);
        if ($wmColor === false) {
            $this->fail('Kunde inte allokera färg för vattenmärkesbilden.');
        }

        imagefill($watermark, 0, 0, $wmColor); // Blå färg
        $this->watermarkImagePath = __DIR__ . '/watermark_image.png';
        imagepng($watermark, $this->watermarkImagePath);
        imagedestroy($watermark);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testImagePath)) {
            unlink($this->testImagePath);
        }

        if (file_exists($this->watermarkImagePath)) {
            unlink($this->watermarkImagePath);
        }

        $files = glob(__DIR__ . '/result_*');
        if ($files !== false) {
            array_map('unlink', $files);
        }
    }

    public function testRotateImage(): void
    {
        $image = new Image($this->testImagePath);

        $image->rotateImage(90);

        $info = $image->getImageInfo();

        // Eftersom rotationen inte ändrar dimensionerna internt
        $this->assertEquals(800, $info['width']);
        $this->assertEquals(600, $info['height']);
    }

    public function testAddWatermark(): void
    {
        $image = new Image($this->testImagePath);

        $resultPath = __DIR__ . '/result_with_watermark.jpg';
        $image->addWatermark($this->watermarkImagePath, 50, 50);
        $image->saveImage($resultPath);

        $this->assertFileExists($resultPath);
    }

    public function testGetImageInfo(): void
    {
        $image = new Image($this->testImagePath);

        $info = $image->getImageInfo();

        // Kontrollera att dimensionerna matchar originalbildens storlek
        $this->assertEquals(800, $info['width'], 'Originalbredden ska vara 800 pixlar.');
        $this->assertEquals(600, $info['height'], 'Originalhöjden ska vara 600 pixlar.');

        // Kontrollera att inga dimensioner för resized bild finns när ingen ändring har gjorts
        $this->assertNull($info['resizedWidth'], 'Den ändrade bredden ska vara null om ingen ändring gjorts.');
        $this->assertNull($info['resizedHeight'], 'Den ändrade höjden ska vara null om ingen ändring gjorts.');

        // Ändra storlek och verifiera
        $image->resizeImage(400, 300);
        $infoAfterResize = $image->getImageInfo();

        $this->assertEquals(400, $infoAfterResize['resizedWidth'], 'Den ändrade bredden efter resize ska vara 400 pixlar.');
        $this->assertEquals(300, $infoAfterResize['resizedHeight'], 'Den ändrade höjden efter resize ska vara 300 pixlar.');
    }

    public function testConstructorValidImage(): void
    {
        $image = new Image($this->testImagePath);
        $this->assertInstanceOf(Image::class, $image);
    }

    public function testConstructorInvalidPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bilden "non_existing_image.jpg" kunde inte hittas.');

        new Image('non_existing_image.jpg');
    }

    public function testConstructorUnsupportedFormat(): void
    {
        $unsupportedPath = __DIR__ . '/unsupported_image.bmp';

        // Skapa en riktig BMP-fil
        $bmpHeader = hex2bin('424D460000000000000036000000280000000100000001000000010018000000000010000000C40E0000C40E00000000000000000000');
        file_put_contents($unsupportedPath, $bmpHeader);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bildformat "image/bmp" stöds inte.');

        try {
            new Image($unsupportedPath);
        } finally {
            unlink($unsupportedPath);
        }
    }

    public function testResizeImage(): void
    {
        $image = new Image($this->testImagePath);

        $image->resizeImage(400, 300);

        $resized = $image->getImageResized();
        $this->assertNotNull($resized, 'Resized image should not be null.');
        $this->assertEquals(400, imagesx($resized));
        $this->assertEquals(300, imagesy($resized));
    }

    public function testResizeImageCrop(): void
    {
        $image = new Image($this->testImagePath);

        $image->resizeImage(400, 300, 'crop');

        $resized = $image->getImageResized();
        $this->assertNotNull($resized, 'Resized image should not be null.');
        $this->assertEquals(400, imagesx($resized));
        $this->assertEquals(300, imagesy($resized));
    }

    public function testSaveImage(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 300);
        $outputPath = __DIR__ . '/resized_image.jpg';

        $image->saveImage($outputPath);

        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath));
    }

    public function testSaveThumb(): void
    {
        $image = new Image($this->testImagePath);
        $thumbPath = __DIR__ . '/test_thumb.jpg';

        $image->resizeImage(400, 300); // Se till att resizeImage körs
        $image->saveThumb($thumbPath);

        $expectedThumbPath = __DIR__ . '/test_thumb.thumb.jpg';
        $this->assertFileExists($expectedThumbPath);
        $this->assertGreaterThan(0, filesize($expectedThumbPath));
    }

    public function testSaveImageUnsupportedFormat(): void
    {
        $image = new Image($this->testImagePath);
        $image->resizeImage(400, 400);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Okänt filformat "txt".');

        $image->saveImage(__DIR__ . '/test_image.txt');
    }
}