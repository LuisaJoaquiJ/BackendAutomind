<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Estudiante extends Model
{
    protected $table    = 'estudiantes';
    protected $fillable = ['user_id','codigo','carrera','semestre','cedula','telefono','fecha_ingreso','estado'];
 
    public function user()          { return $this->belongsTo(User::class); }
    public function inscripciones() { return $this->hasMany(Inscripcion::class); }
}