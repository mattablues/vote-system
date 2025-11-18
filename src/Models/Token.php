<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $value
 * @property string|null $description
 * @property string|null $expires_at
 * @property string $created_at
 * @property string $updated_at
 */
class Token extends Model
{
    protected string $table = 'tokens'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id'; // Standard primärnyckel
    public bool $timestamps = true; // Använd timestamps (created_at, updated_at)

    // Tillåtna fält för mass assignment
    /** @var array<int,string> */
    protected array $fillable = ['id', 'user_id', 'value', 'description', 'expires_at'];

    /**
     * Kontrollera om token är giltig.
     */
    public function isValid(): bool
    {
        if (empty($this->value)) {
            return false;
        }

        // Kontrollera om token har ett utgångsdatum
        if (!is_null($this->expires_at) && strtotime($this->expires_at) < time()) {
            return false; // Giltighetstiden har passerat
        }

        return true; // Token är giltig
    }

    /**
     * Generera en ny tokensträng.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16)); // Genererar 32-tecken lång kryptografiskt säker token
    }

    /**
     * Skapa och spara en ny token.
     */
    public static function createToken(int $userId, ?string $description = null, ?int $validityDays = 30): self
    {
        $token = new self();
        $token->user_id = $userId;
        $token->value = self::generateToken();
        $token->description = $description;

        // Sätt utgångsdatum (om det anges)
        if (!is_null($validityDays)) {
            $timestamp = strtotime("+$validityDays days");
            if ($timestamp === false) {
                throw new \RuntimeException('Kunde inte beräkna utgångsdatum för token.');
            }

            $token->expires_at = date('Y-m-d H:i:s', $timestamp);
        }

        $token->save(); // Spara token i databasen

        return $token;
    }

    /**
     * Markera en token som förbrukad.
     */
    public function expire(): void
    {
        $this->expires_at = date('Y-m-d H:i:s'); // Sätt utgångsdatumet till nuvarande tid
        $this->save();
    }

    /**
     * Relation till användaren (om det behövs i framtiden).
     */
    public function user(): \Radix\Database\ORM\Relationships\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id'); // Om koppling till en användare finns
    }

    /**
     * Skapa en mer användarvänlig representation.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'description' => $this->description,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
