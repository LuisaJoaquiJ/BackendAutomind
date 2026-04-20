<?php

namespace Tests\Feature;

use App\Models\Materia;
use App\Models\MateriaActividad;
use App\Models\MateriaArchivo;
use App\Models\MateriaContenido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MateriaDinamicaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pantalla_dinamica_devuelve_contenido_por_nivel(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $materia = Materia::create([
            'user_id' => $user->id,
            'nombre' => 'Programacion',
            'codigo' => 'PRG-101',
            'creditos' => 3,
            'docente' => 'Ada Lovelace',
            'horario' => 'Lunes 8:00',
            'sala' => 'A-101',
        ]);

        $contenidoBasico = MateriaContenido::create([
            'materia_id' => $materia->id,
            'creado_por' => $user->id,
            'titulo' => 'Variables y tipos de datos',
            'nivel' => 'basico',
            'contenido_texto' => 'Contenido inicial',
            'orden' => 1,
        ]);

        MateriaContenido::create([
            'materia_id' => $materia->id,
            'creado_por' => $user->id,
            'titulo' => 'Estructuras avanzadas',
            'nivel' => 'avanzado',
            'contenido_texto' => 'Contenido avanzado',
            'orden' => 2,
        ]);

        MateriaArchivo::create([
            'materia_id' => $materia->id,
            'materia_contenido_id' => $contenidoBasico->id,
            'subido_por' => $user->id,
            'titulo' => 'Guia 1',
            'tipo' => 'pdf',
            'ruta' => 'materias/1/pdfs/guia-1.pdf',
            'nombre_original' => 'guia-1.pdf',
            'mime_type' => 'application/pdf',
            'tamano' => 2048,
            'descargable' => true,
        ]);

        MateriaActividad::create([
            'materia_id' => $materia->id,
            'creado_por' => $user->id,
            'titulo' => 'Compara dos numeros',
            'tipo' => 'programacion',
            'nivel' => 'basico',
            'enunciado' => 'Crea un programa que compare dos numeros.',
            'pistas' => ['Usa una condicion if.'],
            'criterios' => ['comparar', 'mayor'],
            'orden' => 1,
        ]);

        $response = $this->getJson("/api/materias/{$materia->id}/pantalla-dinamica?nivel=basico");

        $response->assertOk()
            ->assertJsonPath('data.nivel_seleccionado', 'basico')
            ->assertJsonCount(1, 'data.contenidos')
            ->assertJsonCount(1, 'data.actividades')
            ->assertJsonPath('data.contenidos.0.titulo', 'Variables y tipos de datos')
            ->assertJsonPath('data.contenidos.0.archivos.0.tipo', 'pdf')
            ->assertJsonPath('data.aprendizaje_progresivo.siguiente_nivel', 'intermedio');
    }

    public function test_respuesta_de_actividad_genera_feedback_constructivo(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $materia = Materia::create([
            'user_id' => $user->id,
            'nombre' => 'Programacion',
            'codigo' => 'PRG-102',
            'creditos' => 4,
            'docente' => 'Grace Hopper',
            'horario' => 'Martes 10:00',
            'sala' => 'B-204',
        ]);

        $actividad = MateriaActividad::create([
            'materia_id' => $materia->id,
            'creado_por' => $user->id,
            'titulo' => 'Condicionales',
            'tipo' => 'programacion',
            'nivel' => 'basico',
            'enunciado' => 'Resuelve el ejercicio con una condicion.',
            'pistas' => ['Recuerda evaluar el caso de igualdad.'],
            'criterios' => ['if', 'igual'],
            'orden' => 1,
        ]);

        $response = $this->postJson("/api/materias/{$materia->id}/actividades/{$actividad->id}/respuestas", [
            'codigo' => 'if ($a > $b) { echo $a; }',
            'lenguaje' => 'php',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'actividad_id',
                    'resultado' => ['estado', 'puntaje', 'feedback'],
                    'siguiente_paso',
                ],
            ]);
    }
}
