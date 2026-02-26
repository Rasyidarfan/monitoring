<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anomaly extends Model
{
    protected $fillable = ['code', 'rule', 'description'];

    public function getDescriptionByCode($code)
    {
        return self::where('code', $code)->first()?->description ?? "Anomali {$code}";
    }
}
