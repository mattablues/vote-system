<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use DateInterval;
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
        @mkdir($this->tmpDir, 0o755, true);
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

    public function testSetWithDateInterval(): void
    {
        // Testa att använda DateInterval som TTL
        $ttl = new DateInterval('PT1H'); // 1 timme
        $this->assertTrue($this->cache->set('interval', 'value_interval', $ttl));
        $this->assertSame('value_interval', $this->cache->get('interval'));
    }

    public function testGetHandlesCorruptedJson(): void
    {
        // Skapa en korrupt cachefil manuellt
        // För nyckeln 'corrupt' blir filnamnet 'corrupt.cache'
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'corrupt.cache';
        file_put_contents($file, '{invalid_json');

        // get() ska returnera default (null) om JSON är ogiltig
        $this->assertNull($this->cache->get('corrupt'));
    }

    public function testGetHandlesNonArrayPayload(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'not_array.cache';
        // Valid JSON men inte en array (som förväntas av FileCache implementationen)
        file_put_contents($file, '"just_a_string"');

        $this->assertNull($this->cache->get('not_array'));
    }

    public function testGetHandlesMissingExpiresKey(): void
    {
        $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'no_expires.cache';
        // Payload utan 'e' (expires) nyckel
        file_put_contents($file, json_encode(['v' => 'value']));

        // Ska defaulta expires till 0 (aldrig utgången) och returnera värdet
        $this->assertSame('value', $this->cache->get('no_expires'));
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
