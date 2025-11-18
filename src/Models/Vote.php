<?php

declare(strict_types=1);

namespace App\Models;

use Radix\Database\ORM\Model;

/**
 * @property int $id
 * @property int $subject_id
 * @property int $voter_id
 * @property string $vote
 * @property string $voted_at
 */
class Vote extends Model
{
    protected string $table = 'votes'; // Dynamiskt genererat tabellnamn
    protected string $primaryKey = 'id';     // Standard primärnyckel
    public bool $timestamps = false;         // Vill du använda timestamps?

    public function setVoteAttribute(int $value): void
    {
        if (!in_array($value, [0, 1, 2], true)) {
            throw new \InvalidArgumentException("Invalid vote value. Allowed values are: 0 = No, 1 = Not Sure, 2 = Yes.");
        }

        $this->attributes['vote'] = $value;
    }

    public function getVoteAttribute(?int $value): ?int
    {
        // returnera heltal oförändrat, ingen sträng/formattering
        return $value;
    }

   public function voter(): \Radix\Database\ORM\Relationships\BelongsTo
   {
       return $this->belongsTo(Voter::class, 'voter_id', 'id');
   }

   public function subject(): \Radix\Database\ORM\Relationships\BelongsTo
   {
       return $this->belongsTo(Subject::class, 'subject_id', 'id');
   }
}