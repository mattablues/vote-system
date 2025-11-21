<?php

declare(strict_types=1);

namespace Radix\Tests;

use App\Models\Status;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testGetActiveAtAttributeReturnsFormattedDate(): void
    {
        $status = new Status();
        $timestamp = 1672531200; // 2023-01-01 00:00:00 UTC

        // Vi sätter attributet manuellt eftersom vi testar accessorn specifikt
        $status->setActiveAtAttribute($timestamp);

        // Verifiera att vi får tillbaka en formaterad sträng, inte null (vilket mutanten skulle returnera)
        // Observera att datumet formateras i lokal tidzon, så exakt sträng kan bero på serverns inställning.
        // Vi nöjer oss med att det inte är null och är en sträng.
        $formatted = $status->getAttribute('active_at');

        $this->assertNotNull($formatted, 'ActiveAt ska returnera ett datum, inte null när värdet är satt.');
        $this->assertIsString($formatted);
        // Vi kan också göra en lös kontroll på formatet
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $formatted);
    }

    public function testGetActiveAtAttributeReturnsNullWhenNotSet(): void
    {
        $status = new Status();
        $status->setActiveAtAttribute(null);

        $this->assertNull($status->getAttribute('active_at'));
    }

    public function testSetActiveAtCastsNumericStringToInteger(): void
    {
        $status = new Status();
        // Skicka in en numerisk sträng
        $status->setActiveAtAttribute('1672531200');

        // Hämta det råa attributvärdet (inte via accessorn som formaterar det)
        // Vi måste använda reflection eller på något sätt komma åt $attributes arrayen direkt
        // Men Status::getAttribute() (från Model) kommer att returnera värdet som det ligger i attributes
        // OM det inte finns en accessor. Men det FINNS en accessor (getActiveAtAttribute) som returnerar sträng.
        // Så $status->getAttribute('active_at') returnerar strängen "2023-01-01...".

        // Vi måste komma åt det interna värdet för att verifiera typen.
        // Model har metoden getAttributes() som returnerar hela arrayen.
        $attributes = $status->getAttributes();

        $this->assertArrayHasKey('active_at', $attributes);
        $this->assertIsInt($attributes['active_at'], 'Attributet active_at ska lagras som integer.');
        $this->assertSame(1672531200, $attributes['active_at']);
    }

    public function testSetActiveAtCastsFloatToInteger(): void
    {
        $status = new Status();
        $status->setActiveAtAttribute(1672531200.0);

        $attributes = $status->getAttributes();
        $this->assertIsInt($attributes['active_at']);
        $this->assertSame(1672531200, $attributes['active_at']);
    }

    public function testSetActiveAtThrowsExceptionForNegativeNumbers(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ogiltigt värde för active_at: -1');

        $status = new Status();
        $status->setActiveAtAttribute(-1);
    }

    public function testSetActiveAtParsesValidDateString(): void
    {
        $status = new Status();
        // Giltigt datum
        $dateString = '2023-05-01 12:00:00';

        // Använd strtotime för att få förväntat värde i samma tidszon som koden använder
        $expectedTimestamp = strtotime($dateString);

        $status->setActiveAtAttribute($dateString);

        // Verifiera att det sparades korrekt
        // Vi hämtar det råa attributet via getAttributes för att undvika accessorn som formaterar om det
        $attributes = $status->getAttributes();
        $this->assertSame($expectedTimestamp, $attributes['active_at']);
    }

    public function testGetActiveAtAttributeIsPublic(): void
    {
        $status = new Status();
        // Anropa metoden direkt för att verifiera att den är publik.
        // Mutanten som gör den protected kommer att orsaka ett fatal error.
        $this->assertNull($status->getActiveAtAttribute(null));
    }

    public function testSetActiveAtThrowsExceptionForZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Ogiltigt värde för active_at: 0');

        $status = new Status();
        // Originalkoden kräver > 0, så 0 ska kasta exception.
        // Mutanten (>= 0) skulle acceptera 0 och inte kasta exception.
        $status->setActiveAtAttribute(0);
    }
}
