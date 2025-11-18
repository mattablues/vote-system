<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\File\Reader;
use Radix\File\Writer;

final class ReaderWriterTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'radix_file_' . bin2hex(random_bytes(4)) . DIRECTORY_SEPARATOR;
        mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tmpDir);
        parent::tearDown();
    }

    public function testJsonReadWrite(): void
    {
        $path = $this->tmpDir . 'data.json';
        $data = ['a' => 1, 'b' => ['x' => 'y'], 'utf' => 'ÅÄÖ'];
        Writer::json($path, $data, pretty: true);
        $this->assertFileExists($path);

        $read = Reader::json($path, assoc: true);
        $this->assertSame($data, $read);
    }

    public function testCsvReadWriteWithHeaders(): void
    {
        $path = $this->tmpDir . 'data.csv';
        $rows = [
            ['id' => 1, 'name' => 'Alice', 'city' => 'Stockholm'],
            ['id' => 2, 'name' => 'Bob', 'city' => 'Göteborg'],
        ];
        Writer::csv($path, $rows, headers: null, delimiter: ',');
        $this->assertFileExists($path);

        $read = Reader::csv($path, delimiter: ',', hasHeader: true);
        $this->assertSame($rows, $read);
    }

    public function testTsvStreamReadWrite(): void
    {
        $path = $this->tmpDir . 'data.tsv';
        $headers = ['id', 'val'];

        // Låt Writer skriva header-raden via $headers-argumentet
        Writer::csvStream($path, function (callable $write): void {
            for ($i = 1; $i <= 3; $i++) {
                $write([$i, "v{$i}"]);
            }
        }, $headers, "\t");

        $collected = [];
        Reader::csvStream($path, function (array $row) use (&$collected): void {
            $collected[] = $row;
        }, "\t", hasHeader: true);

        $this->assertSame(
            [
                ['id' => 1, 'val' => 'v1'],
                ['id' => 2, 'val' => 'v2'],
                ['id' => 3, 'val' => 'v3'],
            ],
            $collected
        );
    }

    public function testCsvDelimiterAutodetect(): void
    {
        $path = $this->tmpDir . 'semi.csv';
        $rows = [
            ['id' => 1, 'n' => 'A'],
            ['id' => 2, 'n' => 'B'],
        ];
        Writer::csv($path, $rows, headers: ['id', 'n'], delimiter: ';');
        $read = Reader::csv($path, delimiter: null, hasHeader: true);
        $this->assertSame(
            [
                ['id' => 1, 'n' => 'A'],
                ['id' => 2, 'n' => 'B'],
            ],
            $read
        );
    }

    public function testNdjsonStreamReadWrite(): void
    {
        $path = $this->tmpDir . 'data.ndjson';
        $items = [
            ['i' => 1, 't' => 'a'],
            ['i' => 2, 't' => 'b'],
        ];
        Writer::ndjsonStream($path, function (callable $write) use ($items): void {
            foreach ($items as $it) {
                $write($it);
            }
        });

        $collected = [];
        Reader::ndjsonStream($path, function ($item) use (&$collected): void {
            $collected[] = $item;
        }, assoc: true);

        $this->assertSame($items, $collected);
    }

    public function testEncodingConversionIsoToUtf8AndBack(): void
    {
        $path = $this->tmpDir . 'latin1.tsv';
        $rows = [
            ['id', 'name'],
            [1, 'Åsa'],
            [2, 'Björn'],
        ];
        // Skriv som ISO-8859-1 TSV
        Writer::csv($path, [ ['id','name'], ['1','Åsa'], ['2','Björn'] ], delimiter: "\t", targetEncoding: 'ISO-8859-1');

        // Läs som UTF-8 med explicit källa, men behåll strängar
        $read = Reader::csv($path, delimiter: "\t", hasHeader: true, encoding: 'ISO-8859-1', castNumeric: false);
        $this->assertSame(
            [
                ['id' => '1', 'name' => 'Åsa'],
                ['id' => '2', 'name' => 'Björn'],
            ],
            $read
        );
    }

    public function testTextStreamAndWrite(): void
    {
        $path = $this->tmpDir . 'big.txt';
        $content = str_repeat("radix\n", 1000);
        Writer::text($path, $content);

        $buf = '';
        Reader::textStream($path, function (string $chunk) use (&$buf): void {
            $buf .= $chunk;
        }, 4096);

        $this->assertSame($content, $buf);
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

    public function testXmlWriteAndReadAssoc(): void
    {
        $path = $this->tmpDir . 'data.xml';
        $data = [
            'user' => [
                'id' => 1,
                'name' => 'Anna',
                'active' => true,
            ],
        ];

        // Skriv XML
        Writer::xml($path, $data, rootName: 'root');
        $this->assertFileExists($path);

        // Läs som assoc-array
        $arr = Reader::xml($path, assoc: true);
        $this->assertSame(
            ['user' => ['id' => '1', 'name' => 'Anna', 'active' => 'true']],
            $arr
        );
    }

    public function testXmlWriteAndReadSimpleXml(): void
    {
        $path = $this->tmpDir . 'data2.xml';
        $data = [
            'items' => [
                'item' => [
                    ['id' => 1, 'label' => 'A'],
                    ['id' => 2, 'label' => 'B'],
                ],
            ],
        ];

        Writer::xml($path, $data, rootName: 'root');
        $xml = Reader::xml($path, assoc: false);

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertSame('1', (string)$xml->items->item->item[0]->id);
        $this->assertSame('B', (string)$xml->items->item->item[1]->label);
    }
}