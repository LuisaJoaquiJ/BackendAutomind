<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Curso extends Model
{
    protected $table    = 'cursos';
    protected $fillable = ['codigo','nombre','descripcion','creditos','semestre','carrera','profesor_id','cupo_max','estado'];
 
    public function profesor()      { return $this->belongsTo(Profesor::class); }
    public function horarios()      { return $this->hasMany(Horario::class); }
    public function inscripciones() { return $this->hasMany(Inscripcion::class); }
}