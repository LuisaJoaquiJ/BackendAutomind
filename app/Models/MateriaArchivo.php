<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MateriaArchivo extends Model
{
    protected $table = 'materia_archivos';

    protected $fillable = [
        'materia_id',
        'materia_contenido_id',
        'subido_por',
        'titulo',
        'tipo',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano',
        'descargable',
    ];

    protected $casts = [
        'descargable' => 'boolean',
    ];

    protected $appends = ['url'];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function contenido()
    {
        return $this->belongsTo(MateriaContenido::class, 'materia_contenido_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
