<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_actividad_id')->constrained('materia_actividades')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('respuesta')->nullable();
            $table->longText('codigo')->nullable();
            $table->string('lenguaje', 50)->nullable();
            $table->string('estado', 30)->default('pendiente');
            $table->unsignedInteger('puntaje')->default(0);
            $table->longText('feedback')->nullable();
            $table->json('pistas_generadas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_respuestas');
    }
};
