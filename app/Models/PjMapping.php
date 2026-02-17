<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PjMapping extends Model
{
    protected $fillable = [
        'activity_id',
        'village_code',
        'desa_nama',
        'pj_code',
        'pj_name',
        'target',
    ];

    /**
     * Activity that this PJ mapping belongs to
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
