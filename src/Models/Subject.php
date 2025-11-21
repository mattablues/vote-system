<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property int $category_id
 * @property string $subject
 * @property string $created_at
 * @property string $updated_at
 */
class Subject extends Model
{
    protected string $table = 'subjects'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = true;         // Vill du använda timestamps?
    /** @var array<int,string> */
    protected array $fillable = ['id', 'subject', 'category_id', 'published'];

    public function setSubjectAttribute(string $value): void
    {
        $this->attributes['subject'] = mb_ucfirst(mb_strtolower(trim($value)));
    }

    public function getSubjectAttribute(?string $value): ?string
    {
        return $value ? mb_ucfirst(mb_strtolower($value)) : null;
    }

    public function category(): \Radix\Database\ORM\Relationships\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function vote(): \Radix\Database\ORM\Relationships\HasMany
    {
        return $this->hasMany(Vote::class, 'subject_id', 'id');
    }

    public function voter(): \Radix\Database\ORM\Relationships\BelongsToMany
    {
        return $this->belongsToMany(
            Voter::class,      // Relaterad modell
            'votes',           // Pivot-tabell
            'subject_id',      // Främmande nyckel för denna modell
            'voter_id',        // Främmande nyckel för relaterad modell
            'id'               // Primärnyckel för denna modell
        );
    }
}
