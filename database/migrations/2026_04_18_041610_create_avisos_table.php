<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('avisos', function (Blueprint $table) {
        $table->id();
        $table->string('titulo');
        $table->text('contenido');
        $table->foreignId('autor_id')->constrained('users')->onDelete('cascade');
        $table->integer('curso_id')->nullable();
        $table->enum('prioridad', ['baja', 'media', 'alta'])->default('media');
        $table->boolean('leido')->default(false);
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('avisos');
}
};
