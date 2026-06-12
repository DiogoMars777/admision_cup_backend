<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 41. GESTION_CUP
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

        // 2. Update gestion_academica table
        Schema::table('gestion_academica', function (Blueprint $table) {
            $table->dropColumn('periodo');
            $table->unsignedBigInteger('id_gestion_cup')->nullable();
            
            $table->foreign('id_gestion_cup')
                  ->references('id')
                  ->on('gestion_cup')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gestion_academica', function (Blueprint $table) {
            $table->dropForeign(['id_gestion_cup']);
            $table->dropColumn('id_gestion_cup');
            $table->string('periodo', 50)->nullable();
        });

        Schema::dropIfExists('gestion_cup');
    }
};
