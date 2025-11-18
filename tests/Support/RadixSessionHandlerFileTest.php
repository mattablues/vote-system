<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Session\RadixSessionHandler;

final class RadixSessionHandlerFileTest extends TestCase
{
    private string $tmpDir;
    private RadixSessionHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'radix_sess_' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR;
        @mkdir($this->tmpDir, 0755, true);

        $this->handler = new RadixSessionHandler([
            'driver' => 'file',
            'path' => $this->tmpDir,
            'lifetime' => 60,
        ]);
        // $this->handler->open($this->tmpDir, 'PHPSESSID'); // onödigt; open() gör inget
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    public function testWriteThenReadReturnsSameData(): void
    {
        $sid = 'abc123';
        $data = 'foo=bar;num=1';

        $ok = $this->handler->write($sid, $data);
        $this->assertTrue($ok, 'Write ska returnera true');
        $this->assertFileExists($this->tmpDir . "sess_{$sid}");

        $read = $this->handler->read($sid);
        $this->assertSame($data, $read, 'Read ska returnera samma data som skrivits');
    }

    public function testReadNonExistingReturnsEmptyString(): void
    {
        $read = $this->handler->read('does-not-exist');
        $this->assertSame('', $read);
    }

    public function testDestroyRemovesSessionFile(): void
    {
        $sid = 'to-destroy';
        $this->handler->write($sid, 'x=1');
        $file = $this->tmpDir . "sess_{$sid}";
        $this->assertFileExists($file);

        $ok = $this->handler->destroy($sid);
        $this->assertTrue($ok);
        $this->assertFileDoesNotExist($file);
    }

    public function testGcRemovesExpiredFiles(): void
    {
        // Skapa två sessioner: en gammal, en ny
        $old = $this->tmpDir . 'sess_old';
        $new = $this->tmpDir . 'sess_new';

        file_put_contents($old, 'old');
        file_put_contents($new, 'new');

        // Backa mtime för "old" så att den blir äldre än max_lifetime
        $past = time() - 3600;
        @touch($old, $past);

        $deleted = $this->handler->gc(10);
        $this->assertIsInt($deleted);
        $this->assertFileDoesNotExist($old, 'Gammal sessionfil ska ha raderats');
        $this->assertFileExists($new, 'Ny sessionfil ska finnas kvar');
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($p)) {
                $this->deleteDirectory($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}