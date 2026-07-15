<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    protected $fillable = ['name', 'slug', 'role', 'goal', 'instructions', 'status'];

    public function runs(): HasMany
    {
        return $this->hasMany(AgentRun::class);
    }

    public function latestRun(): HasOne
    {
        return $this->hasOne(AgentRun::class)->latestOfMany();
    }
}
