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

    /**
     * Clean cv_text before saving to database
     */
    public function setCvTextAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['cv_text'] = $this->cleanTextForStorage($value);
        } else {
            $this->attributes['cv_text'] = $value;
        }
    }

    /**
     * Clean text for database storage
     */
    protected function cleanTextForStorage($text): string
    {
        // Remove null bytes and other control characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        
        // Remove invalid UTF-8 sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove any remaining invalid characters
        $text = preg_replace('/[\x80-\x9F]/', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Ensure the text is not too long for the database column
        $maxLength = 65535; // LONGTEXT max length
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        
        return trim($text);
    }
}
