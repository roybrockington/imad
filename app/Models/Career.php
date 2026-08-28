<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Career extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'location',
        'published',
        // Multilingual position fields
        'position_en',
        'position_de',
        'position_fr',
        'position_nl',
        'position_pl',
        // Multilingual tasks fields
        'tasks_en',
        'tasks_de',
        'tasks_fr',
        'tasks_nl',
        'tasks_pl',
        // Multilingual profile fields
        'profile_en',
        'profile_de',
        'profile_fr',
        'profile_nl',
        'profile_pl',
        // Multilingual expectations fields
        'expectations_en',
        'expectations_de',
        'expectations_fr',
        'expectations_nl',
        'expectations_pl',
    ];

    protected $casts = [
        'start_date' => 'date',
        'published' => 'boolean',
    ];
}
