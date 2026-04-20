<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Horario;

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
}