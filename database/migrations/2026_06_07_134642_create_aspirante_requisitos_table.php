<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aspirante_requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_aspirante')->constrained('aspirante_docente', 'id_persona')->onDelete('cascade');
            $table->foreignId('id_materia_requisito')->constrained('materia_requisito')->onDelete('cascade');
            $table->boolean('cumplido')->default(false);
            $table->string('estado', 20)->default('Pendiente');
            $table->string('documento_url', 255)->nullable();
            $table->timestamps();
            
            $table->unique(['id_aspirante', 'id_materia_requisito']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspirante_requisito');
    }
};
