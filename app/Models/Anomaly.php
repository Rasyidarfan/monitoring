<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Anomaly extends Model
{
    protected $fillable = ['activity_id', 'code', 'rule', 'description'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function getDescriptionByCode(string $code, ?int $activityId = null): string
    {
        $query = self::where('code', $code);

        if ($activityId) {
            $query->where('activity_id', $activityId);
        }

        return $query->first()?->description ?? "Anomali {$code}";
    }
}
