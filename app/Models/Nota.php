<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Materia;

class Nota extends Model
{
    protected $fillable = [
        'user_id',
        'materia_id',
        'corte1',
        'corte2',
        'corte3'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    // 🎯 PROMEDIO AUTOMÁTICO
    public function getPromedioAttribute()
    {
        $c1 = $this->corte1 ?? 0;
        $c2 = $this->corte2 ?? 0;
        $c3 = $this->corte3 ?? 0;

        return round(($c1 * 0.3) + ($c2 * 0.3) + ($c3 * 0.4), 2);
    }
}