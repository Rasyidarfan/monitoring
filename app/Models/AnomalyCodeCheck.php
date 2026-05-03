<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyCodeCheck extends Model
{
    protected $fillable = ['anomaly_data_id', 'code', 'checked'];

    protected $casts = [
        'checked' => 'boolean',
    ];

    public function anomalyData(): BelongsTo
    {
        return $this->belongsTo(AnomalyData::class);
    }
}
