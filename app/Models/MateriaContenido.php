<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MateriaContenido extends Model
{
    protected $table = 'materia_contenidos';

    protected $fillable = [
        'materia_id',
        'creado_por',
        'titulo',
        'tipo',
        'nivel',
        'resumen',
        'contenido_texto',
        'url',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function archivos()
    {
        return $this->hasMany(MateriaArchivo::class);
    }
}
