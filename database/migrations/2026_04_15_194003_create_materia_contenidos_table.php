<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_contenidos', function (Blueprint $table) {
            $table->id();

            // Relación con materia
            $table->foreignId('materia_id')
                  ->constrained('materias')
                  ->cascadeOnDelete();

            // Campos principales
            $table->string('creado_por')->nullable();
            $table->string('titulo');
            $table->string('nivel'); // basico, intermedio, avanzado

            // Contenido
            $table->text('resumen')->nullable();
            $table->longText('contenido_texto')->nullable();

            // Orden y estado
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_contenidos');
    }
};