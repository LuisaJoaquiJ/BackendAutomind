<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->renameColumn('curso_id', 'materia_id');
        });
    }

    public function down(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->renameColumn('materia_id', 'curso_id');
        });
    }
};