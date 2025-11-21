<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\File\Upload;
use Radix\Support\Validator;
use Radix\Tests\Support\TestableValidator;

class UploadTest extends TestCase
{
    protected string $uploadDirectory;

    protected function setUp(): void
    {
        // Skapa tillfällig uppladdningsmapp
        $this->uploadDirectory = __DIR__ . '/uploads';
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0o755, true);
        }

        // Skapa en mockad bildfil för att simulera en uppladdning
        $image = imagecreatetruecolor(100, 100);

        $color = imagecolorallocate($image, 255, 0, 0);
        if ($color === false) {
            $this->fail('Kunde inte allokera färg för testbilden.');
        }

        imagefill($image, 0, 0, $color); // Röd bild
        imagejpeg($image, $this->uploadDirectory . '/test_image.jpg');
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        // Ta bort uppladdningsmappen och dess innehåll
        $files = glob($this->uploadDirectory . '/*');

        if ($files === false) {
            $files = [];
        }

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($this->uploadDirectory);
    }

    public function testNullableFileUpload(): void
    {
        $data = [
            'avatar' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE, // Ingen fil skickades
                'size' => 0,
            ],
        ];

        $rules = [
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
        ];

        $validator = new Validator($data, $rules);

        // Validering ska passera om ingen fil skickades och fältet är nullable
        $this->assertTrue($validator->validate(), 'Valideringen ska passera eftersom avatar är nullable.');
    }



    /**
     * Mocka move_uploaded_file för att kopiera filer istället för att använda native-behaviour under test.
     */
    protected function mockMoveUploadedFile(): void
    {
        if (!function_exists('Radix\File\move_uploaded_file')) {
            eval('namespace Radix\File; function move_uploaded_file($from, $to) {
                if (!file_exists($from)) {
                    return false;
                }
                return copy($from, $to);
            }');
        }
    }

    public function testValidateFileTypeAndSizeIndividually(): void
    {
        $mockFile = [
            'name' => 'example.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'size' => 102400, // 100 KB
            'error' => UPLOAD_ERR_OK, // Mocka som en korrekt uppladdad fil
        ];

        $validator = new TestableValidator($mockFile, []);

        // Testa filtyp individuellt
        $isTypeValid = $validator->testFileType($mockFile, 'image/jpeg,image/png');
        $this->assertTrue($isTypeValid, 'MIME-typ-valideringen ska godkänna image/jpeg.');

        // Testa filstorlek individuellt
        $isSizeValid = $validator->testFileSize($mockFile, '2'); // 2 MB
        $this->assertTrue($isSizeValid, 'Filstorleksvalideringen ska godkänna 100 KB.');
    }

    public function testValidateValidFile(): void
    {
        $data = [
            'avatar' => [
                'name' => 'test_image.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
                'error' => UPLOAD_ERR_OK,
                'size' => 102400, // 100 KB
            ],
        ];

        $rules = [
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png',
        ];

        $validator = new Validator($data, $rules);

        // Validering ska passera för en giltig fil
        $this->assertTrue($validator->validate(), 'Filvalideringen ska godkänna en giltig fil.');
    }


    public function testValidateInvalidFileType(): void
    {
        $this->mockMoveUploadedFile();

        $file = [
            'name' => 'test_invalid.pdf',
            'type' => 'application/pdf', // Felaktig MIME-typ
            'tmp_name' => $this->uploadDirectory . '/test_invalid.pdf',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400, // 100 KB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'avatar' => 'file_type:image/jpeg,image/png',
        ]);

        $this->assertFalse($isValid, 'Filvalideringen ska misslyckas för en ogiltig MIME-typ.');
        $this->assertNotEmpty($upload->getErrors(), 'Felmeddelanden ska genereras.');
    }


    public function testValidateExceededFileSize(): void
    {
        $this->mockMoveUploadedFile();

        $file = [
            'name' => 'test_large_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 5120000, // 5 MB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'avatar' => 'file_size:2', // Max 2 MB
        ]);

        $this->assertFalse($isValid, 'Filvalideringen ska misslyckas för en för stor fil.');
    }

    public function testSaveValidFile(): void
    {
        $this->mockMoveUploadedFile();

        // Mockad giltig upload-fil
        $file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 102400, // 100 KB
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $savedPath = $upload->save();
        $this->assertFileExists($savedPath, 'Filen ska sparas korrekt på målplatsen.');
    }

    public function testErrorHandling(): void
    {
        $this->mockMoveUploadedFile();

        // Mocka en fil utan uppladdning
        $file = [
            'name' => 'test_image.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->uploadDirectory . '/test_image.jpg',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $upload = new Upload($file, $this->uploadDirectory);

        $isValid = $upload->validate([
            'type' => 'file_type:image/jpeg,image/png',
        ]);

        $this->assertFalse($isValid, 'Valideringen ska misslyckas om ingen giltig fil laddas upp.');

        $this->assertNotEmpty($upload->getErrors(), 'Felmeddelanden ska genereras.');
    }
}
