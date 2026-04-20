<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->string('tipo', 30)->default('pregunta');
            $table->string('nivel')->default('basico');
            $table->text('descripcion')->nullable();
            $table->longText('enunciado');
            $table->longText('solucion_referencia')->nullable();
            $table->json('pistas')->nullable();
            $table->json('criterios')->nullable();
            $table->unsignedInteger('orden')->default(1);
            $table->unsignedInteger('puntaje_maximo')->default(100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_actividades');
    }
};
