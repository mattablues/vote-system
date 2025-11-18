<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Support\FileCache;

final class FileCacheTest extends TestCase
{
    private string $tmpDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'radix_filecache_' . bin2hex(random_bytes(4));
        @mkdir($this->tmpDir, 0755, true);
        $this->cache = new FileCache($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    public function testSetAndGet(): void
    {
        $this->assertNull($this->cache->get('missing'));

        $this->assertTrue($this->cache->set('key1', 'value1', 60));
        $this->assertSame('value1', $this->cache->get('key1'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('def', $this->cache->get('nope', 'def'));
    }

    public function testDelete(): void
    {
        $this->cache->set('k', ['a' => 1], 60);
        $this->assertNotNull($this->cache->get('k'));
        $this->assertTrue($this->cache->delete('k'));
        $this->assertNull($this->cache->get('k'));
    }

    public function testClear(): void
    {
        $this->cache->set('a', 1, 60);
        $this->cache->set('b', 2, 60);
        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('a'));
        $this->assertNull($this->cache->get('b'));
    }

    public function testTtlExpiry(): void
    {
        $this->cache->set('short', 'x', 1);
        $this->assertSame('x', $this->cache->get('short'));

        // simulera utgången TTL
        sleep(2);
        $this->assertNull($this->cache->get('short'));
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $dir . DIRECTORY_SEPARATOR . $f;
            if (is_dir($p)) {
                $this->deleteDirectory($p);
            } else {
                @unlink($p);
            }
        }
        @rmdir($dir);
    }
}