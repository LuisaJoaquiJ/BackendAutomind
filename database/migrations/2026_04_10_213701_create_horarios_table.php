<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('horarios', function (Blueprint $table) {
            $table->id();

            // 👤 Usuario relacionado
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // 📚 Materia relacionada
            $table->foreignId('materia_id')
                ->constrained()
                ->onDelete('cascade');

            // 📅 Datos del horario
            $table->string('dia');          // Lunes, Martes, etc.
            $table->time('hora_inicio');    // 07:00
            $table->time('hora_fin');       // 09:00
            $table->string('aula');         // A201

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios');
    }
};