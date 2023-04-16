<?php

namespace App\Models;

use App\Enums\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property integer $id
 * @property string $name
 * @property PlayerPosition $position
 * @property PlayerSkill $skill
 */
class Player extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'position'
    ];

    protected $casts = [
        'position' => PlayerPosition::class
    ];

    protected $with = ['skills'];

    public function skills(): HasMany
    {
        return $this->hasMany(PlayerSkill::class);
    }

    public function hasSkill(string $skill): bool
    {
        foreach ($this->skills as $playerSkill) {
            if ($playerSkill->skill->value === $skill)
                return true;
        }
        return false;
    }

    public function getSkillValue(string $skillType): int 
    {
        foreach ($this->skills as $skill) {
            if ($skill->skill->value == $skillType) 
                return $skill->value;
        }  
        return 0;
    }

    public function getHighestSkillValue(): int
    {
        $max = 0;
        foreach ($this->skills as $skill) {
            if ($skill->value > $max) 
                $max = $skill->value;
        }
        return $max;
    }


}
