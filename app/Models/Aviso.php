<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    protected $fillable = [
        'titulo',
        'contenido',
        'autor_id',
        'materia_id',
        'prioridad',
        'leido'
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}