<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringData extends Model
{
    protected $table = 'monitoring_data';

    protected $fillable = [
        'activity_id',
        'village_code',
        'village_name',
        'regency_code',
        'open',
        'submitted',
        'approved',
        'rejected',
    ];

    protected function casts(): array
    {
        return [
            'open' => 'integer',
            'submitted' => 'integer',
            'approved' => 'integer',
            'rejected' => 'integer',
        ];
    }

    /**
     * Activity that this monitoring data belongs to
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get completion percentage (from pj_mappings target)
     */
    public function getCompletionPercentageAttribute(): float
    {
        $target = $this->pjMapping?->target ?? 0;
        if ($target == 0) {
            return 0;
        }

        return round(($this->approved / $target) * 100, 2);
    }

    /**
     * Get progress percentage (submitted + approved) from pj_mappings target
     */
    public function getProgressPercentageAttribute(): float
    {
        $target = $this->pjMapping?->target ?? 0;
        if ($target == 0) {
            return 0;
        }

        return round((($this->submitted + $this->approved) / $target) * 100, 2);
    }

    /**
     * Get PJ mapping for this monitoring data
     */
    public function pjMapping(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PjMapping::class, 'village_code', 'village_code')
            ->where('activity_id', $this->activity_id);
    }
}
