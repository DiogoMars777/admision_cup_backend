<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Faker\Factory as Faker;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        // Verificar o Crear Roles
        $rolPostulanteId = DB::table('rol')->where('nombre', 'Postulante')->value('id');
        if (!$rolPostulanteId) {
            $rolPostulanteId = DB::table('rol')->insertGetId([
                'nombre' => 'Postulante',
                'descripcion' => 'Postulante al CUP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rolDocenteId = DB::table('rol')->where('nombre', 'Docente')->value('id');
        if (!$rolDocenteId) {
            $rolDocenteId = DB::table('rol')->insertGetId([
                'nombre' => 'Docente',
                'descripcion' => 'Docente del CUP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear 10 Postulantes
        for ($i = 0; $i < 10; $i++) {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $faker->unique()->randomNumber(8, true),
                'nombre' => $faker->name,
                'sexo' => $faker->randomElement(['M', 'F']),
                'telefono' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('postulante')->insert([
                'id_persona' => $personaId,
                'fecha_nac' => $faker->dateTimeBetween('-25 years', '-17 years')->format('Y-m-d'),
                'direccion' => $faker->address,
                'colegio' => 'Colegio ' . $faker->company,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('usuario')->insert([
                'id_persona' => $personaId,
                'id_rol' => $rolPostulanteId,
                'email' => "postulante{$i}@cup.edu.bo",
                'password' => Hash::make('password123'),
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear 10 Docentes
        for ($i = 0; $i < 10; $i++) {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => $faker->unique()->randomNumber(8, true),
                'nombre' => $faker->name,
                'sexo' => $faker->randomElement(['M', 'F']),
                'telefono' => $faker->phoneNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('docente')->insert([
                'id_persona' => $personaId,
                'grado_academico' => $faker->randomElement(['Licenciatura', 'Maestría', 'Doctorado']),
                'experiencia_docente' => $faker->numberBetween(1, 20),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('usuario')->insert([
                'id_persona' => $personaId,
                'id_rol' => $rolDocenteId,
                'email' => "docente{$i}@cup.edu.bo",
                'password' => Hash::make('password123'),
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear 10 Materias
        $materias = ['Matemáticas', 'Física', 'Química', 'Lenguaje', 'Historia', 'Biología', 'Geografía', 'Cívica', 'Filosofía', 'Computación'];
        foreach ($materias as $materia) {
            DB::table('materia')->updateOrInsert(['nombre' => $materia], [
                'nombre' => $materia,
                'descripcion' => 'Materia de ' . $materia,
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear 10 Aulas
        for ($i = 1; $i <= 10; $i++) {
            $nroAula = 'A-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            DB::table('aula')->updateOrInsert(['aula_nro' => $nroAula], [
                'aula_nro' => $nroAula,
                'capacidad' => $faker->numberBetween(30, 60),
                'tipo_aula' => $faker->randomElement(['Teórica', 'Laboratorio']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Verificar o Crear 1 Gestion Academica
        $gestionId = DB::table('gestion_academica')->where('nombre', 'Gestión 2026')->value('id');
        if (!$gestionId) {
            $gestionId = DB::table('gestion_academica')->insertGetId([
                'nombre' => 'Gestión 2026',
                'año' => 2026,
                'periodo' => '1',
                'fecha_ini' => '2026-02-01',
                'fecha_fin' => '2026-06-30',
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Crear 10 Grupos
        for ($i = 1; $i <= 10; $i++) {
            $nombreGrupo = 'Grupo ' . str_pad($i, 2, '0', STR_PAD_LEFT);
            DB::table('grupo')->updateOrInsert(['nombre' => $nombreGrupo], [
                'id_gestionacademica' => $gestionId,
                'nombre' => $nombreGrupo,
                'cupo_max' => 50,
                'cant_estudiante' => $faker->numberBetween(10, 45),
                'modalidad' => $faker->randomElement(['Presencial', 'Virtual']),
                'turno' => $faker->randomElement(['Mañana', 'Tarde', 'Noche']),
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        // Crear Catálogo de 5 Requisitos base
        $reqsBase = [
            ['nombre' => 'Fotocopia de CI', 'desc' => 'Documento de identidad legible'],
            ['nombre' => 'Título Bachiller', 'desc' => 'Título legalizado'],
            ['nombre' => 'Certificado de nacimiento', 'desc' => 'Original y actualizado'],
            ['nombre' => 'Fotografía actualizada', 'desc' => 'Fondo rojo 4x4'],
            ['nombre' => 'Formulario de inscripción', 'desc' => 'Firmado por el postulante'],
        ];

        $reqIds = [];
        foreach ($reqsBase as $req) {
            $reqId = DB::table('requisito')->insertGetId([
                'id_abministrador' => 1, // Asumimos admin ID 1
                'nombre' => $req['nombre'],
                'descripcion' => $req['desc'],
                'estado' => 'Activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $reqIds[] = $reqId;
        }

        // Asignar requisitos a los postulantes
        $postulantes = DB::table('postulante')->get();
        
        foreach ($postulantes as $index => $postulante) {
            // Simulamos estados distintos para la demo
            foreach ($reqIds as $rIndex => $reqId) {
                $estado = 'Pendiente';
                $observacion = '';

                // El primer postulante tiene todo validado (Completo)
                if ($index === 0) {
                    $estado = 'Entregado';
                } 
                // El segundo tiene 3 validados (Parcial)
                else if ($index === 1) {
                    if ($rIndex < 3) $estado = 'Entregado';
                    else if ($rIndex == 3) { $estado = 'Pendiente'; $observacion = 'Falta firmar'; }
                }
                // Los demás al azar
                else {
                    $rand = rand(0, 2);
                    if ($rand == 0) $estado = 'Entregado';
                    else if ($rand == 1) { $estado = 'Pendiente'; $observacion = 'Documento ilegible'; }
                }

                DB::table('postulante_requisito')->insert([
                    'id_postulante' => $postulante->id_persona,
                    'id_requisito' => $reqId,
                    'fecha_asignacion' => now()->format('Y-m-d'),
                    'estado' => $estado,
                    'observacion' => $observacion,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        $this->command->info('Seeder DemoDataSeeder ejecutado: 10 registros creados por módulo y requisitos asignados.');
    }
}
