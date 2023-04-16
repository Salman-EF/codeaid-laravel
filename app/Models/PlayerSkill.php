<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSkill extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $fillable = [
        'skill',
        'value'
    ];

    protected $casts = [
        'skill' => \App\Enums\PlayerSkill::class
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
