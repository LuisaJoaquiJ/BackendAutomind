<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Nota;
use App\Models\User;
use App\Models\Materia;

class NotaSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $materias = Materia::all();

        if ($users->isEmpty() || $materias->isEmpty()) {
            $this->command->info('No hay usuarios o materias para crear notas.');
            return;
        }

        foreach ($users as $user) {
            foreach ($materias as $materia) {

                // 🎯 evitar duplicados
                $exists = Nota::where('user_id', $user->id)
                    ->where('materia_id', $materia->id)
                    ->exists();

                if ($exists) continue;

                // 🎯 notas aleatorias (simulación universidad)
                $c1 = rand(10, 50) / 10; // 1.0 - 5.0
                $c2 = rand(10, 50) / 10;
                $c3 = rand(10, 50) / 10;

                $promedio = ($c1 * 0.3) + ($c2 * 0.3) + ($c3 * 0.4);

                Nota::create([
                    'user_id' => $user->id,
                    'materia_id' => $materia->id,
                    'corte1' => $c1,
                    'corte2' => $c2,
                    'corte3' => $c3,
                    'promedio' => round($promedio, 2)
                ]);
            }
        }

        $this->command->info('✔ Notas generadas correctamente.');
    }
}