<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $fillable = [
        'materia_id',
        'dia',
        'hora_inicio',
        'hora_fin',
        'salon',
    ];

    // 🔥 Relación correcta
    public function materia()
    {
        return $this->belongsTo(\App\Models\Materia::class);
    }
}