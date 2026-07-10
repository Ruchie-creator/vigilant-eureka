<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyReport extends Model
{
    protected $fillable = [
        'title',
        'week_start',
        'week_end',
        'summary',
        'wins',
        'issues',
        'recommendations',
        'next_actions',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
        ];
    }
}
