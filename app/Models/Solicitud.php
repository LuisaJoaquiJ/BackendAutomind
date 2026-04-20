<?php

namespace App\Models;
use App\Models\Solicitud;

use Illuminate\Database\Eloquent\Model;


class Solicitud extends Model

{
    protected $table = 'solicitudes'; 
    protected $fillable = [
        'user_id',
        'tipo',
        'estado',
        'descripcion'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}