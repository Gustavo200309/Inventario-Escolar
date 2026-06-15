<?php

namespace Database\Seeders;

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
        foreach ([
            'admin@prueba.com' => [
                'name' => 'Administrador',
                'password' => Hash::make('Admin1234'),
                'role' => 'admin',
            ],
            'visualizador@prueba.com' => [
                'name' => 'Visualizador',
                'password' => Hash::make('Viewer1234'),
                'role' => 'visualizador',
            ],
        ] as $email => $attributes) {
            $exists = DB::table('users')->where('email', $email)->exists();
            $values = array_merge($attributes, [
                'email' => $email,
                'updated_at' => now(),
            ]);

            if ($exists) {
                DB::table('users')->where('email', $email)->update($values);
                continue;
            }

            DB::table('users')->insert(array_merge($values, [
                'created_at' => now(),
            ]));
        }
    }
}
