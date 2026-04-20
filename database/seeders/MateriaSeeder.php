<?php

namespace Database\Seeders;

use App\Models\Materia;
use App\Models\User;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) return;

        $materias = [
            [
                'nombre' => 'Base de Datos',
                'codigo' => 'BD101',
                'creditos' => 3,
                'docente' => 'Ing. Pérez',
                'horario' => 'Lunes 7-9',
                'sala' => 'A101'
            ],
            [
                'nombre' => 'Programación',
                'codigo' => 'PR202',
                'creditos' => 4,
                'docente' => 'Ing. López',
                'horario' => 'Martes 9-11',
                'sala' => 'Lab 1'
            ]
        ];

        foreach ($materias as $m) {
            Materia::create(array_merge($m, ['user_id' => $user->id]));
        }
    }
}