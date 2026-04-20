<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('materia');
            $table->decimal('corte1', 4, 2)->nullable(); // 30%
            $table->decimal('corte2', 4, 2)->nullable(); // 30%
            $table->decimal('corte3', 4, 2)->nullable(); // 40%
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('calificaciones');
    }
};