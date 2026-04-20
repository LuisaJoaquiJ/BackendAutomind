<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('notas', function (Blueprint $table) {
            $table->id();

            // 👤 estudiante
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // 📚 materia
            $table->foreignId('materia_id')
                ->constrained()
                ->onDelete('cascade');

            // 🧾 cortes
            $table->decimal('corte1', 4, 2)->nullable();
            $table->decimal('corte2', 4, 2)->nullable();
            $table->decimal('corte3', 4, 2)->nullable();

            // 📊 promedio (puede calcularse o guardarse)
            $table->decimal('promedio', 4, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas');
    }
};