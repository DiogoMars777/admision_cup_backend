<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 40. ASPIRANTE_REQUISITO
        Schema::create('aspirante_requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_postulacion_docente')->constrained('postulacion_docente')->onDelete('cascade');
            $table->foreignId('id_materia_requisito')->constrained('materia_requisito')->onDelete('cascade');
            $table->foreignId('id_administrativo')->nullable()->constrained('administrativo', 'id_persona')->onDelete('set null');
            
            $table->boolean('cumple')->default(false);
            $table->date('fecha_revision')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->string('estado', 20)->default('Pendiente');
            
            $table->timestamps();
            
            $table->unique(['id_postulacion_docente', 'id_materia_requisito'], 'aspirante_req_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aspirante_requisito');
    }
};
