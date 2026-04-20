<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MateriaActividad extends Model
{
    protected $table = 'materia_actividades';

    protected $fillable = [
        'materia_id',
        'creado_por',
        'titulo',
        'tipo',
        'nivel',
        'descripcion',
        'enunciado',
        'solucion_referencia',
        'pistas',
        'criterios',
        'orden',
        'puntaje_maximo',
        'activo',
    ];

    protected $casts = [
        'pistas' => 'array',
        'criterios' => 'array',
        'activo' => 'boolean',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function respuestas()
    {
        return $this->hasMany(MateriaRespuesta::class);
    }
}
