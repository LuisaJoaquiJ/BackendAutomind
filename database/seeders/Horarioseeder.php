<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Horario;
use App\Models\Materia;

class HorarioSeeder extends Seeder
{
    public function run(): void
    {
        $materias = Materia::all();

        foreach ($materias as $materia) {

            if (!$materia->horario) continue;

            // Ej: "Lunes 7-9"
            $partes = explode(' ', $materia->horario);

            if (count($partes) < 2) continue;

            $dia = $partes[0]; // Lunes
            $horas = explode('-', $partes[1]); // 7-9

            if (count($horas) < 2) continue;

            // Formato horas
            $horaInicio = str_pad($horas[0], 2, '0', STR_PAD_LEFT) . ':00';
            $horaFin    = str_pad($horas[1], 2, '0', STR_PAD_LEFT) . ':00';

            // 🔥 CREAR O ACTUALIZAR HORARIO
            Horario::updateOrCreate(
                [
                    'materia_id' => $materia->id,
                    'dia' => $dia,
                    'hora_inicio' => $horaInicio,
                ],
                [
                    // 🔥 IMPORTANTE: user_id obligatorio
                    'user_id' => $materia->user_id ?? 1,

                    'hora_fin' => $horaFin,
                    'aula' => $materia->sala ?? 'Sin aula',
                ]
            );
        }

        $this->command->info('Horarios generados desde materias correctamente.');
    }
}