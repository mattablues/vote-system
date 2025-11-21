<?php

declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;
use Radix\Database\ORM\Model;
use RuntimeException;

/**
 * @property int $id
 * @property int $user_id
 * @property string $last_name
 * @property string $password_reset
 * @property string $reset_expires_at
 * @property string $activation
 * @property string $status
 * @property string $active
 * @property string|null $active_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class Status extends Model
{
    protected string $table = 'status'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id'; // Standard primärnyckel
    public bool $timestamps = true;
    /** @var array<int,string> */
    protected array $fillable = ['id', 'user_id', 'password_reset', 'reset_expires_at', 'activation', 'status', 'active', 'active_at']; // Tillåtna fält

    /**
     * Sätt användaren som "online".
     */
    public function goOnline(): self
    {
        $this->active = 'online';
        $this->active_at = (string) time();
        $this->save();

        return $this;
    }

    // Status.php
    public function goOffline(): self
    {
        if ($this->active !== 'online') {
            throw new RuntimeException("Status kan endast sättas till 'offline' om den tidigare var 'online'.");
        }

        $this->active = 'offline';

        // Använd rå Unix-tid istället för formaterat värde
        $rawActiveAt = $this->getRawActiveAt();
        $this->active_at = (string) ($rawActiveAt !== null ? $rawActiveAt : time());
        $this->save();

        return $this;
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Kontrollera om användaren är online.
     */
    public function isOnline(): bool
    {
        return $this->active === 'online';
    }

    // Status.php
    public function getRawActiveAt(): ?int
    {
        $value = $this->attributes['active_at'] ?? null;

        if ($value === null) {
            return null;
        }

        // Normalisera till int, annars null
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    public function getActiveAtAttribute(?int $value): ?string
    {
        // Om värdet är null, returnera null, annars formatera som läsbart datum
        return $value ? date('Y-m-d H:i:s', (int) $value) : null;
    }

    public function setActiveAtAttribute(int|float|string|null $value): void
    {
        if (is_null($value)) {
            $this->attributes['active_at'] = null;
        } elseif (is_numeric($value) && $value > 0) {
            $this->attributes['active_at'] = (int) $value;
        } elseif (is_string($value) && strtotime($value)) {
            $this->attributes['active_at'] = strtotime($value);
        } else {
            throw new InvalidArgumentException("Ogiltigt värde för active_at: $value");
        }
    }

    public function translateStatus(string $status): string
    {
        return match ($status) {
            'activate' => 'aktivera',
            'activated' => 'aktiverad',
            'blocked' => 'blockerad', // rättad
            'closed' => 'stängt',
            default => $status,
        };
    }

    public function user(): \Radix\Database\ORM\Relationships\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
