<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Materia extends Model
{
    protected $fillable = [
        'user_id',
        'nombre',
        'codigo',
        'creditos',
        'docente',
        'horario',
        'sala'
    ];

    // 👤 Relación: materia pertenece a un usuario (docente)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🗓️ Relación: materia tiene muchos horarios
    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function contenidos()
    {
        return $this->hasMany(MateriaContenido::class);
    }

    public function archivos()
    {
        return $this->hasMany(MateriaArchivo::class);
    }

    public function actividades()
    {
        return $this->hasMany(MateriaActividad::class);
    }
    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }
}
