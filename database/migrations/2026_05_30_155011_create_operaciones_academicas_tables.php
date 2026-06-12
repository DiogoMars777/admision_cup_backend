<?php
// Mapea la conformación de grupos, horarios, requisitos y pagos

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 19. GRUPO
        Schema::create('grupo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_gestionacademica')->constrained('gestion_academica')->onDelete('cascade');
            $table->string('nombre', 50);
            $table->integer('cupo_max');
            $table->integer('cant_estudiante')->default(0);
            $table->string('modalidad', 50)->nullable();
            $table->string('turno', 50)->nullable();
            $table->string('estado', 20)->default('Activo');
            $table->timestamps();
        });

        // 20. POSTULANTE_GRUPO
        Schema::create('postulante_grupo', function (Blueprint $table) {
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_grupo')->constrained('grupo')->onDelete('cascade');
            $table->date('fecha_asignacion');
            $table->primary(['id_postulante', 'id_grupo']);
            $table->timestamps();
        });

        // 21. GRUPO MATERIA
        Schema::create('grupo_materia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_grupo')->constrained('grupo')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->foreignId('id_docente')->constrained('persona')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['id_grupo', 'id_materia']);
        });

        // 22. HORARIO
        Schema::create('horario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_grupo_materia')->constrained('grupo_materia')->onDelete('cascade');
            $table->foreignId('id_aula')->constrained('aula')->onDelete('cascade');
            $table->string('dia', 20);
            $table->time('hora_ini');
            $table->time('hora_fin');
            $table->string('modalidad', 50)->nullable();
            $table->timestamps();
        });

        // 23. REQUISITO
        Schema::create('requisito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_abministrador')->constrained('persona');
            $table->string('nombre', 100);
            $table->string('descripcion', 255)->nullable();
            $table->string('tipo_requisito', 50)->nullable();
            $table->string('estado', 20)->default('Pendiente');
            $table->timestamps();
        });

        // 24. POSTULANTE_REQUISITO
        Schema::create('postulante_requisito', function (Blueprint $table) {
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_requisito')->constrained('requisito')->onDelete('cascade');
            $table->date('fecha_asignacion');
            $table->string('estado', 20)->default('Pendiente');
            $table->string('observacion', 255)->nullable();
            $table->primary(['id_postulante', 'id_requisito']);
            $table->timestamps();
        });

        // 25. PAGO
        Schema::create('pago', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_comprobante')->constrained('comprobante');
            $table->decimal('monto', 10, 2);
            $table->string('modalidad_pago', 50)->nullable();
            $table->string('codigo_transaccion', 100)->nullable();
            $table->string('estado', 20)->default('Procesado');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pago');
        Schema::dropIfExists('postulante_requisito');
        Schema::dropIfExists('requisito');
        Schema::dropIfExists('horario');
        Schema::dropIfExists('grupo_materia');
        Schema::dropIfExists('postulante_grupo');
        Schema::dropIfExists('grupo');
    }
};