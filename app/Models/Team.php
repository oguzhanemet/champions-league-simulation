<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'attack_strength', 'defense_strength'];

    public function fixturesAsHome(): HasMany
    {
        return $this->hasMany(Fixture::class, 'home_team_id');
    }

    public function fixturesAsAway(): HasMany
    {
        return $this->hasMany(Fixture::class, 'away_team_id');
    }

    public function standing(): HasOne
    {
        return $this->hasOne(Standing::class);
    }
}