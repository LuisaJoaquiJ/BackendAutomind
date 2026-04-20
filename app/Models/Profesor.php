<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Profesor extends Model
{
    protected $table    = 'profesores';
    protected $fillable = ['user_id','cedula','especialidad','titulo','telefono'];
 
    public function user()   { return $this->belongsTo(User::class); }
    public function cursos() { return $this->hasMany(Curso::class); }
}