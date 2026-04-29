<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function getAnomalyDetails()
    {
        if (!$this->anomali) {
            return [];
        }

        $codes = explode(' ', trim($this->anomali));
        return Anomaly::whereIn('code', $codes)->get(['code', 'description', 'rule'])->toArray();
    }
}
