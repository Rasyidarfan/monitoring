<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnomalyData extends Model
{
    protected $table = 'anomaly_data';

    protected $fillable = [
        'activity_id',
        'kode_daerah',
        'kecamatan',
        'desa',
        'dsrt',
        'no_art',
        'nama_krt',
        'nama_art',
        'link',
        'anomali',
        'checked'
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function codeChecks(): HasMany
    {
        return $this->hasMany(AnomalyCodeCheck::class);
    }

    public function getAnomalyDetails()
    {
        if (!$this->anomali) {
            return [];
        }

        $codes = explode(' ', trim($this->anomali));
        $anomalies = Anomaly::where('activity_id', $this->activity_id)
            ->whereIn('code', $codes)
            ->get(['code', 'description', 'rule'])
            ->toArray();

        // Load check status for each code
        $checks = $this->codeChecks()
            ->whereIn('code', $codes)
            ->pluck('checked', 'code')
            ->toArray();

        // Merge check status into anomaly details
        foreach ($anomalies as &$anomaly) {
            $anomaly['checked'] = $checks[$anomaly['code']] ?? false;
        }

        return $anomalies;
    }
}
