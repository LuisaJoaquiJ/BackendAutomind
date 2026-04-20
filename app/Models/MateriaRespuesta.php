<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaRespuesta extends Model
{
    protected $table = 'materia_respuestas';

    protected $fillable = [
        'materia_actividad_id',
        'user_id',
        'respuesta',
        'codigo',
        'lenguaje',
        'estado',
        'puntaje',
        'feedback',
        'pistas_generadas',
    ];

    protected $casts = [
        'pistas_generadas' => 'array',
    ];

    public function actividad()
    {
        return $this->belongsTo(MateriaActividad::class, 'materia_actividad_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
