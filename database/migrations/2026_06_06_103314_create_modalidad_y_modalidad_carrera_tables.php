<?php
// Catálogo de modalidades y relación modalidad-carrera

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        // 38. MODALIDAD
        Schema::create('modalidad', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        // 39. MODALIDAD_CARRERA
        Schema::create('modalidad_carrera', function (Blueprint $table) {
            $table->foreignId('id_modalidad')
                ->constrained('modalidad')
                ->onDelete('cascade');

            $table->foreignId('id_carrera')
                ->constrained('carrera')
                ->onDelete('cascade');

            $table->primary(['id_modalidad', 'id_carrera']);

            $table->timestamps();
        });

        // Modificar POSTULANTE_CARRERA para añadir id_modalidad
        Schema::table('postulante_carrera', function (Blueprint $table) {
            $table->foreignId('id_modalidad')->nullable()->constrained('modalidad')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::table('postulante_carrera', function (Blueprint $table) {
            $table->dropForeign(['id_modalidad']);
            $table->dropColumn('id_modalidad');
        });
        Schema::dropIfExists('modalidad_carrera');
        Schema::dropIfExists('modalidad');
    }
};