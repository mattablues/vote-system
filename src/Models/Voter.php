<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $password_reset
 * @property string $reset_expires_at
 * @property string $activation
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Voter extends Model
{
    protected string $table = 'voters'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = true;         // Vill du använda timestamps?
    /** @var array<int,string> */
    protected array $fillable = ['id', 'email', 'token', 'password_reset', 'reset_expires_at', 'activation', 'status'];
    protected array $guarded = ['password'];

    public function setPasswordAttribute(string $value): void
    {
        // Kontrollera om värdet redan är hashat
        if (!password_get_info($value)['algo']) {
            // Endast hash lösenord som inte redan är hashat
            $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
        } else {
            // Om det redan är hashat, spara det direkt
            $this->attributes['password'] = $value;
        }
    }

    // Hantera e-postlagring i små bokstäver
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    // Accessor för att hämta e-post (texten ska redan vara korrekt formaterad)
    public function getEmailAttribute(?string $value): ?string
    {
        return $value ?? null; // Returnera null om det inte finns
    }

    public function vote(): \Radix\Database\ORM\Relationships\HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id', 'id');
    }

    public function translateStatus(string $status): string
    {
        return match ($status) {
            'activate' => 'aktivera',
            'activated' => 'aktiverad',
            'blocked' => 'blockerad',
            default => $status,
        };
    }

    public function subject(): \Radix\Database\ORM\Relationships\BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,   // Relaterad modell
            'votes',          // Pivot-tabell
            'voter_id',       // Främmande nyckel för denna modell
            'subject_id',     // Främmande nyckel för relaterad modell
            'id'              // Primärnyckel för denna modell
        );
    }
}
