<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();

            // 👤 estudiante
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // 📄 tipo de solicitud
            $table->enum('tipo', [
                'paz_y_salvo',
                'certificado',
                'constancia'
            ]);

            // 📌 estado
            $table->enum('estado', [
                'pendiente',
                'aprobado',
                'rechazado',
                'entregado'
            ])->default('pendiente');

            // 📝 motivo opcional
            $table->text('descripcion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};