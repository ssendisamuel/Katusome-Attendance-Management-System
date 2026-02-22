<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'parent_id', 'latitude', 'longitude', 'radius_meters',
    ];

    protected $casts = [
        'latitude'      => 'float',
        'longitude'     => 'float',
        'radius_meters' => 'integer',
    ];

    // ── Relationships ──

    public function parent()
    {
        return $this->belongsTo(Venue::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Venue::class, 'parent_id')->orderBy('name');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function series()
    {
        return $this->hasMany(ScheduleSeries::class);
    }

    // ── Helpers ──

    public function isBuilding(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * "ADB Building → Lab 1" or just "Block 2"
     */
    public function fullName(): string
    {
        if ($this->parent) {
            return $this->parent->name . ' → ' . $this->name;
        }
        return $this->name;
    }

    /**
     * Return this venue's coordinates if set, otherwise the venue's parent,
     * otherwise the global LocationSetting.
     */
    public function getLocationCoordinates(): array
    {
        // Check this venue
        if ($this->latitude && $this->longitude) {
            return [
                'latitude'      => $this->latitude,
                'longitude'     => $this->longitude,
                'radius_meters' => $this->radius_meters ?? 150,
            ];
        }

        // Check parent building
        if ($this->parent && $this->parent->latitude && $this->parent->longitude) {
            return [
                'latitude'      => $this->parent->latitude,
                'longitude'     => $this->parent->longitude,
                'radius_meters' => $this->parent->radius_meters ?? 150,
            ];
        }

        // Fall back to global setting
        $setting = LocationSetting::current();
        return [
            'latitude'      => $setting->latitude,
            'longitude'     => $setting->longitude,
            'radius_meters' => $setting->radius_meters ?? 150,
        ];
    }
}
