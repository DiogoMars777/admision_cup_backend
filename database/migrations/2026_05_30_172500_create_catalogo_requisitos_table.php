<?php
// Tablas faltantes del diagrama: materia_requisito, postulacion_docente y docente_requisito

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 32. MATERIA_REQUISITO
        Schema::create('materia_requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->foreignId('id_requisito')->constrained('requisito')->onDelete('cascade');
            $table->boolean('obligatorio')->default(true);
            $table->string('estado', 20)->default('Activo');
            $table->timestamps();

            $table->unique(['id_materia', 'id_requisito']);
        });

        // 33. POSTULACION_DOCENTE
        Schema::create('postulacion_docente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_aspirante_docente')->constrained('aspirante_docente', 'id_persona')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->date('fecha_postulacion');
            $table->string('estado', 20)->default('Pendiente');
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
        });

        // 34. DOCENTE_REQUISITO
        Schema::create('docente_requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_docente')->constrained('docente', 'id_persona')->onDelete('cascade');
            $table->foreignId('id_administrativo')->nullable()->constrained('administrativo', 'id_persona')->onDelete('set null');
            $table->foreignId('id_requisito_materia')->constrained('materia_requisito')->onDelete('cascade');
            $table->boolean('cumple')->default(false);
            $table->date('fecha_revision')->nullable();
            $table->string('observacion', 255)->nullable();
            $table->string('estado', 20)->default('Pendiente');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('docente_requisito');
        Schema::dropIfExists('postulacion_docente');
        Schema::dropIfExists('materia_requisito');
    }
};