<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuarios en tabla users
        DB::table('users')->insert([
            [
                'name' => 'Administrador',
                'email' => 'admin@prueba.com',
                'password' => Hash::make('Admin1234'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Visualizador',
                'email' => 'visualizador@prueba.com',
                'password' => Hash::make('Viewer1234'),
                'role' => 'visualizador',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
