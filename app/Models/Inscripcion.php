<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Inscripcion extends Model
{
    public $timestamps  = false;
    protected $table    = 'inscripciones';
    protected $fillable = ['estudiante_id','curso_id','fecha_inscripcion','estado'];
 
    public function estudiante()     { return $this->belongsTo(Estudiante::class); }
    public function curso()          { return $this->belongsTo(Curso::class); }
    public function calificaciones() { return $this->hasMany(Calificacion::class); }
}
 