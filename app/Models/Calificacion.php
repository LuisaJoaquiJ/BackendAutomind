<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Calificacion extends Model
{
    public $timestamps  = false;
    protected $table    = 'calificaciones';
    protected $fillable = ['inscripcion_id','tipo','nota','porcentaje','observacion','fecha'];
 
    public function inscripcion() { return $this->belongsTo(Inscripcion::class); }
}