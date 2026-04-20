<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
 
class Aviso extends Model
{
    public $timestamps  = false;
    protected $table    = 'avisos';
    protected $fillable = ['titulo','contenido','tipo','dirigido_a','carrera','publicado_por','activo','fecha_inicio','fecha_fin'];
}
 