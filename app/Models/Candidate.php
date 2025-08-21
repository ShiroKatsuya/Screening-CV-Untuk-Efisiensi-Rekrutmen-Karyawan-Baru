<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'position_applied',
        'skills',
        'years_experience',
        'education_level',
        'cv_file_path',
        'cv_text',
        'features',
        'score',
        'recommendation',
    ];

    protected $casts = [
        'features' => 'array',
        'score' => 'float',
        'years_experience' => 'integer',
    ];
}
