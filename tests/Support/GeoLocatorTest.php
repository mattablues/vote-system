<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Http\Exception\GeoLocatorException;
use Radix\Support\GeoLocator;
use ReflectionClass;

class GeoLocatorTest extends TestCase
{
    public function testGetLocationThrowsExceptionOnApiError(): void
    {
        $geoLocator = new GeoLocator();

        // Förvänta undantag för API-fel
        $this->expectException(GeoLocatorException::class);
        $this->expectExceptionMessage('API fel: invalid query');

        // Testa med en IP som leder till API-fel
        $geoLocator->getLocation('256.256.256.256');
    }

    public function testGetLocationThrowsExceptionOnNetworkError(): void
    {
        $geoLocator = new GeoLocator();

        // Mocka ett nätverksfel genom att ändra baseUrl
        $reflection = new ReflectionClass($geoLocator);
        $property = $reflection->getProperty('baseUrl');
        $property->setValue($geoLocator, 'http://nonexistent-domain.test');

        $this->expectException(GeoLocatorException::class);
        $this->expectExceptionMessage('Kunde inte nå API');

        // Testa med en giltig IP men ogiltigt nätverk
        $geoLocator->getLocation('8.8.8.8');
    }

    public function testGetSpecificValue(): void
    {
        $geoLocator = new GeoLocator();

        // Hämta land för 8.8.8.8
        $country = $geoLocator->get('country', '8.8.8.8');

        // Kontrollera att rätt land returneras
        $this->assertIsString($country);
        $this->assertEquals('United States', $country);
    }

    public function testGetLocationSuccess(): void
    {
        $geoLocator = new GeoLocator();

        // Testa giltig hämtning av platsdata
        $location = $geoLocator->getLocation('8.8.8.8');

        // Verifiera att data är en array och innehåller nyckeln country
        $this->assertNotEmpty($location);
        $this->assertArrayHasKey('country', $location);
        $this->assertEquals('United States', $location['country']);
    }
}
