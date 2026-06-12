<?php
// Evaluaciones, admisiones, asistencias y auditoría del sistema

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 26. EVALUACION
        Schema::create('evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_eva', 100);
            $table->decimal('puntaje_max', 5, 2);
            $table->timestamps();
        });

        // 27. PROGRAMACION_EVALUACION
        Schema::create('programacion_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_evaluacion')->constrained('evaluacion')->onDelete('cascade');
            $table->foreignId('id_gestionacademica')->constrained('gestion_academica')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->date('fecha')->nullable();
            $table->timestamps();
        });

        // 28. NOTA
        Schema::create('nota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_programacion_evaluacion')->constrained('programacion_evaluacion')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->decimal('puntaje_obtenido', 5, 2)->nullable();
            $table->timestamps();
        });

        // 29. ASISTENCIA
        Schema::create('asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_grupo_materia')->constrained('grupo_materia')->onDelete('cascade');
            $table->date('fecha');
            $table->timestamps();
        });

        // 30. DETALLE_ASISTENCIA
        Schema::create('detalle_asistencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asistencia')->constrained('asistencia')->onDelete('cascade');
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->string('estado', 20);
            $table->timestamps();
        });

        // 31. CARGAMASIVA
        Schema::create('cargamasiva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuario')->onDelete('cascade');
            $table->string('nombre_archivo', 150);
            $table->string('tipo_archivo', 50);
            $table->date('fecha_carga');
            $table->integer('cant_registro');
            $table->integer('registro_correcto');
            $table->integer('registro_error');
            $table->timestamps();
        });

        // 32. ADMISION
        Schema::create('admision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_gestionacademica')->constrained('gestion_academica')->onDelete('cascade');
            $table->foreignId('id_carrera')->constrained('carrera')->onDelete('cascade');
            $table->decimal('promedio_fin', 5, 2)->nullable();
            $table->string('estado', 20);
            $table->string('observación', 255)->nullable();
            $table->timestamps();
        });

        // 33. BITACORA
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuario')->onDelete('cascade');
            $table->string('accion', 100);
            $table->string('modulo', 100);
            $table->text('descripcion')->nullable();
            $table->date('fecha')->useCurrent();
            $table->time('hora')->useCurrent();
            $table->string('ip_usuario', 45)->nullable();
            $table->timestamps();
        });

        // 34. REPORTE
        Schema::create('reporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuario')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('fecha');
            $table->string('filtro_aplicado', 255)->nullable();
            $table->string('formato', 20);
            $table->text('contenido')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reporte');
        Schema::dropIfExists('bitacora');
        Schema::dropIfExists('admision');
        Schema::dropIfExists('cargamasiva');
        Schema::dropIfExists('detalle_asistencia');
        Schema::dropIfExists('asistencia');
        Schema::dropIfExists('nota');
        Schema::dropIfExists('programacion_evaluacion');
        Schema::dropIfExists('evaluacion');
    }
};