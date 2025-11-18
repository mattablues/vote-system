<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property string $category
 * @property string $created_at
 * @property string $updated_at
 */
class Category extends Model
{
    protected string $table = 'categories'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = true;         // Vill du använda timestamps?
    /** @var array<int,string> */
    protected array $fillable = ['id', 'category', 'description'];

    public function setCategoryAttribute(string $value): void
    {
        $this->attributes['category'] = mb_ucfirst(mb_strtolower(trim($value)));
    }

    public function getCategoryAttribute(?string $value): ?string
    {
        return $value ? mb_ucfirst(mb_strtolower($value)) : null;
    }

    public function setDescriptionAttribute(string $value): void
    {
        $this->attributes['description'] = mb_ucfirst(mb_strtolower(trim($value)));
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        return $value ? mb_ucfirst(mb_strtolower($value)) : null;
    }

   public function subject(): \Radix\Database\ORM\Relationships\HasMany
   {
       return $this->hasMany(Subject::class, 'category_id', 'id');
   }

    public function vote(): \Radix\Database\ORM\Relationships\HasManyThrough
    {
        // Category -> Subject(category_id=id) -> Vote(subject_id=subjects.id)
        return $this->hasManyThrough(
            Vote::class,      // related
            Subject::class,   // through
            'category_id',    // firstKey on subjects referencing categories.id
            'subject_id',     // secondKey on votes referencing subjects.id
            'id',             // localKey on categories
            'id'              // secondLocal on subjects
        );
    }
}