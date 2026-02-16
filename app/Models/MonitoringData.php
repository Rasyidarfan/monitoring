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
        'target',
        'open',
        'submitted',
        'approved',
        'rejected',
        'pj_code',
        'pj_name',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'integer',
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
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->target == 0) {
            return 0;
        }

        return round(($this->approved / $this->target) * 100, 2);
    }

    /**
     * Get progress percentage (submitted + approved)
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target == 0) {
            return 0;
        }

        return round((($this->submitted + $this->approved) / $this->target) * 100, 2);
    }
}
