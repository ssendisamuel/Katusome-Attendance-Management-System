<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationSetting extends Model
{
    use HasFactory;

    protected $table = 'location_settings';

    protected $fillable = [
        'latitude',
        'longitude',
        'radius_meters',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // Retrieve the single current location setting, creating a default if missing
    public static function current(): self
    {
        // Prefer the most recently updated record in case multiple exist
        $setting = static::orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
        if (!$setting) {
            $setting = static::create([
                'latitude' => 0.332931,
                'longitude' => 32.621927,
                'radius_meters' => 150,
            ]);
        }
        return $setting;
    }
}