<?php
// Mapea planificaciones de periodos y relaciones de asignación

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 13.5 GESTION_CUP
        Schema::create('gestion_cup', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->timestamps();
        });

        // Insert default values CUP 1 and CUP 2
        DB::table('gestion_cup')->insert([
            ['nombre' => 'CUP 1', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'CUP 2', 'created_at' => now(), 'updated_at' => now()]
        ]);

        // 14. GESTION_ACADEMICA
        Schema::create('gestion_academica', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->integer('año');
            $table->foreignId('id_gestion_cup')->nullable()->constrained('gestion_cup')->onDelete('set null');
            $table->date('fecha_ini')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->string('estado', 20)->default('Activo');
            $table->timestamps();
        });

        // 15. CUPO_CARRERA
        Schema::create('cupo_carrera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_carrera')->constrained('carrera')->onDelete('cascade');
            $table->foreignId('id_gestionacademica')->constrained('gestion_academica')->onDelete('cascade');
            $table->integer('cupo_max');
            $table->integer('cupo_disp');
            $table->timestamps();
        });

        // 16. POSTULANTE_CARRERA
        Schema::create('postulante_carrera', function (Blueprint $table) {
            $table->foreignId('id_postulante')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_carrera')->constrained('carrera')->onDelete('cascade');
            $table->integer('prioridad');
            $table->primary(['id_postulante', 'id_carrera']);
            $table->timestamps();
        });

        // 17. DOCENTE_MATERIA
        Schema::create('docente_materia', function (Blueprint $table) {
            $table->foreignId('id_docente')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_materia')->constrained('materia')->onDelete('cascade');
            $table->primary(['id_docente', 'id_materia']);
            $table->timestamps();
        });

        // 18. DOCENTE_ESPECIALIDAD
        Schema::create('docente_especialidad', function (Blueprint $table) {
            $table->foreignId('id_docente')->constrained('persona')->onDelete('cascade');
            $table->foreignId('id_especialidad')->constrained('especialidad')->onDelete('cascade');
            $table->primary(['id_docente', 'id_especialidad']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('docente_especialidad');
        Schema::dropIfExists('docente_materia');
        Schema::dropIfExists('postulante_carrera');
        Schema::dropIfExists('cupo_carrera');
        Schema::dropIfExists('gestion_academica');
        Schema::dropIfExists('gestion_cup');
    }
};