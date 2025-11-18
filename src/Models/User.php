<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;
use Radix\Enums\Role;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $avatar
 * @property string $role
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $deleted_at
 * @property-read \App\Models\Status|null $status
 * @property-read \App\Models\Token|null $token
 */
class User extends Model
{
    protected string $table = 'users'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = true;         // Vill du använda timestamps?
    protected bool $softDeletes = true;
    /** @var array<int,string> */
    protected array $fillable = ['id', 'first_name', 'last_name', 'email', 'avatar']; // Tillåtna att massfylla
    /** @var array<int,string> */
    protected array $guarded = ['password', 'role', 'deleted_at'];
    //protected array $autoloadRelations = ['status'];

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

    public function isPasswordValid(string $plainPassword): bool
    {
        if (!isset($this->attributes['password'])) {
            return false;
        }

        $hash = $this->attributes['password'];

        if (!is_string($hash)) {
            return false;
        }

        return password_verify($plainPassword, $hash);
    }

    // Hantera första bokstaven som stor bokstav för förnamn
    public function setFirstNameAttribute(string $value): void
    {
        $this->attributes['first_name'] = human_name($value);
    }

    // Hantera första bokstaven som stor bokstav för efternamn
    public function setLastNameAttribute(string $value): void
    {
        $this->attributes['last_name'] = human_name($value);
    }

    // Accessor för att hämta förnamnet i rätt format (kan vara identitet om settern redan normaliserar)
    public function getFirstNameAttribute(?string $value): ?string
    {
        return $value ?? null;
    }

    // Accessor för att hämta efternamnet i rätt format
    public function getLastNameAttribute(?string $value): ?string
    {
        return $value ?? null;
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

    /**
     * Relation till Status.
     *
     * @return \Radix\Database\ORM\Relationships\HasOne
     * @phpstan-return \Radix\Database\ORM\Relationships\HasOne
     */
    public function status(): \Radix\Database\ORM\Relationships\HasOne
    {
        return $this->hasOne(Status::class, 'user_id', 'id');
    }

    /**
     * Relation till Token.
     *
     * @return \Radix\Database\ORM\Relationships\HasOne
     * @phpstan-return \Radix\Database\ORM\Relationships\HasOne
     */
    public function token():  \Radix\Database\ORM\Relationships\HasOne
    {
        return $this->hasOne(Token::class, 'user_id', 'id');
    }

    public function setOnline(): self
    {
        /** @var \App\Models\Status|null $status */
        $status = $this->status;

        if (!$status instanceof Status) {
            /** @var \App\Models\Status|null $status */
            $status = $this->status()->first();
        }

        if ($status) {
            $status->goOnline(); // Markera som online
        } else {
            // Om status saknas, logga eller hantera detta beroende på applikationens behov.
            throw new \RuntimeException('Status saknas för användaren och går inte att sätta Online.');
        }

        return $this;
    }

    public function setOffline(): self
    {
        /** @var \App\Models\Status|null $status */
        $status = $this->status;

        if (!$status instanceof Status) {
            /** @var \App\Models\Status|null $status */
            $status = $this->status()->first();
        }

        if ($status) {
            $status->goOffline(); // Markera som offline
        } else {
            // Om status saknas, logga eller hantera detta beroende på applikationens behov.
            throw new \RuntimeException('Status saknas för användaren och går inte att sätta Offline.');
        }

        return $this;
    }

    public function isOnline(): bool
    {
        /** @var \App\Models\Status|null $status */
        $status = $this->status;

        if (!$status instanceof Status) {
            /** @var \App\Models\Status|null $status */
            $status = $this->status()->first();
        }

        // Returnera om användaren är online baserat på Status-objektet
        return $status?->isOnline() ?? false;
    }

    // Rollen som enum
    public function roleEnum(): ?Role
    {
        // Läs direkt från attribut om det finns där (t.ex. efter setRole i minnet)
        if (isset($this->attributes['role']) && is_string($this->attributes['role'])) {
            return Role::tryFrom($this->attributes['role']);
        }

        try {
            $value = $this->fetchGuardedAttribute('role');
        } catch (\InvalidArgumentException) {
            return null;
        }
        return is_string($value) ? Role::tryFrom($value) : null;
    }

    // Sätt roll via enum eller sträng
    public function setRole(Role|string $role): void
    {
        $enum = $role instanceof Role ? $role : Role::tryFromName($role);
        if (!$enum) {
            $roleLabel = $role instanceof Role ? $role->name : (string) $role;
            throw new \InvalidArgumentException('Ogiltig roll: ' . $roleLabel);
        }
        $this->attributes['role'] = $enum->value;
    }

    // Exakt match
    public function hasRole(Role|string $role): bool
    {
        $target = $role instanceof Role ? $role : Role::tryFromName($role);
        $current = $this->roleEnum();
        return $target !== null && $current?->value === $target->value;
    }

    // Miniminivå (om du vill ha hierarki; t.ex. admin >= user)
    public function hasAtLeast(Role|string $role): bool
    {
        $target = $role instanceof Role ? $role : Role::tryFromName($role);
        $current = $this->roleEnum();
        return $target !== null && $current !== null && $current->level() >= $target->level();
    }

    // Någon av flera roller
    public function hasAnyRole(Role|string ...$roles): bool
    {
        foreach ($roles as $r) {
            if ($this->hasRole($r)) {
                return true;
            }
        }
        return false;
    }

    // Syntaktiskt socker
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin);
    }

    public function isModerator(): bool
    {
        return $this->hasRole(Role::Moderator);
    }

    public function isEditor(): bool
    {
        return $this->hasRole(Role::Editor);
    }

    public function isSupport(): bool
    {
        return $this->hasRole(Role::Support);
    }

    public function isUser(): bool
    {
        return $this->hasRole(Role::User);
    }

//    public function vote(): \Radix\Database\ORM\Relationships\HasManyThrough
//    {
//        // Category -> Subject(category_id=id) -> Vote(subject_id=subjects.id)
//        return $this->hasManyThrough(
//            Vote::class,      // related
//            Subject::class,   // through
//            'category_id',    // firstKey on subjects referencing categories.id
//            'subject_id',     // secondKey on votes referencing subjects.id
//            'id',             // localKey on categories
//            'id'              // secondLocal on subjects
//        );
//    }
}