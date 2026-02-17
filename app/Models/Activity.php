<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'last_data_upload_at',
        'json_filename',
        'zip_filename',
    ];

    protected function casts(): array
    {
        return [
            'last_data_upload_at' => 'datetime',
        ];
    }

    /**
     * Monitoring data for this activity
     */
    public function monitoringData(): HasMany
    {
        return $this->hasMany(MonitoringData::class);
    }

    /**
     * PJ mappings for this activity
     */
    public function pjMappings(): HasMany
    {
        return $this->hasMany(PjMapping::class);
    }

    /**
     * Upload histories for this activity
     */
    public function uploadHistories(): HasMany
    {
        return $this->hasMany(UploadHistory::class);
    }

    /**
     * Get total target for this activity (from pj_mappings)
     */
    public function getTotalTargetAttribute(): int
    {
        return $this->pjMappings()->sum('target');
    }

    /**
     * Get total open for this activity
     */
    public function getTotalOpenAttribute(): int
    {
        return $this->monitoringData()->sum('open');
    }

    /**
     * Get total submitted for this activity
     */
    public function getTotalSubmittedAttribute(): int
    {
        return $this->monitoringData()->sum('submitted');
    }

    /**
     * Get total approved for this activity
     */
    public function getTotalApprovedAttribute(): int
    {
        return $this->monitoringData()->sum('approved');
    }

    /**
     * Get total rejected for this activity
     */
    public function getTotalRejectedAttribute(): int
    {
        return $this->monitoringData()->sum('rejected');
    }

    /**
     * Get unique regencies for this activity
     */
    public function getRegenciesAttribute(): array
    {
        return $this->monitoringData()
            ->distinct('regency_code')
            ->pluck('regency_code')
            ->toArray();
    }
}
