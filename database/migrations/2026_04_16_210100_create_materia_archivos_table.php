<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materia_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('materia_contenido_id')->nullable()->constrained('materia_contenidos')->nullOnDelete();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->string('tipo', 20);
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->boolean('descargable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materia_archivos');
    }
};
