<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Support\Logger;

final class LoggerTest extends TestCase
{
    private string $tmpRoot;
    private string $logsDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/radix_logger_test_' . bin2hex(random_bytes(4));
        $this->logsDir = $this->tmpRoot . '/storage/logs';
        @mkdir($this->logsDir, 0755, true);

        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', $this->tmpRoot);
        }
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->tmpRoot);
        parent::tearDown();
    }

    public function testWritesToTodaysFile(): void
    {
        $logger = new Logger('unittest', $this->logsDir);
        $logger->info('hello {name}', ['name' => 'world']);

        $file = $this->logsDir . '/unittest-' . date('Y-m-d') . '.log';
        $this->assertFileExists($file);
        $this->assertStringContainsString('unittest.INFO hello world', file_get_contents($file) ?: '');
    }

    public function testRotatesWhenMaxBytesExceeded(): void
    {
        $smallMax = 64; // tvinga rotation snabbt
        $logger = new Logger('rotate', $this->logsDir, $smallMax);

        // Skriv tills basfilen måste rotera
        for ($i = 0; $i < 50; $i++) {
            $logger->info(str_repeat('x', 40));
        }

        $base = $this->logsDir . '/rotate-' . date('Y-m-d') . '.log';
        $r1 = $base . '.1';
        $r2 = $base . '.2';

        $this->assertTrue($this->anyExisting([$base, $r1, $r2]), 'Expected at least one rotated file to exist');
        // Verifiera att inga filer överstiger gränsen med stor marginal (lite overhead för metadata)
        foreach (glob($this->logsDir . '/rotate-' . date('Y-m-d') . '.log*') ?: [] as $f) {
            $size = filesize($f) ?: 0;
            $this->assertLessThan($smallMax + 256, $size, 'Rotated file unexpectedly large: ' . $f);
        }
    }

    public function testRetentionRemovesOldFiles(): void
    {
        $retentionDays = 1;

        // Skapa en artificiellt gammal fil (2 dagar gammal) FÖRE logger initieras
        $oldFile = $this->logsDir . '/retention-' . date('Y-m-d', time() - 2 * 86400) . '.log';
        file_put_contents($oldFile, 'old');
        @touch($oldFile, time() - 2 * 86400);

        // Initiera logger EFTER att den gamla filen finns så cleanup ser den
        $logger = new Logger('retention', $this->logsDir, 1024 * 1024, $retentionDays);

        // Trigger cleanup via write
        $logger->info('trigger cleanup');

        $this->assertFileDoesNotExist($oldFile, 'Old log should be deleted by retention');
    }

    /**
     * @param array<int, string> $files
     */
    private function anyExisting(array $files): bool
    {
        foreach ($files as $f) {
            if (is_file($f)) return true;
        }
        return false;
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_dir($p)) {
                $this->deleteDir($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}