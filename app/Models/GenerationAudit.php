<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenerationAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'series_id', 'generated_dates', 'overwrite', 'skip_overlaps'
    ];

    protected $casts = [
        'generated_dates' => 'array',
        'overwrite' => 'boolean',
        'skip_overlaps' => 'boolean',
    ];

    public function series()
    {
        return $this->belongsTo(ScheduleSeries::class, 'series_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}