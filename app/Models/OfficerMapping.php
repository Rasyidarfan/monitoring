<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficerMapping extends Model
{
    protected $fillable = [
        'activity_id',
        'village_code',
        'supervisor_email',
        'enumerator_email',
        'target',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
