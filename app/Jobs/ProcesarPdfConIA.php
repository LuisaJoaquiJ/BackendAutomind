<?php

namespace App\Jobs;

use App\Models\Materia;
use App\Models\MateriaContenido;
use App\Models\MateriaActividad;
use App\Services\MateriaIAService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcesarPdfConIA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $materiaId;
    protected $contenidoId;

    /**
     * Create a new job instance.
     */
    public function __construct($materiaId, $contenidoId = null)
    {
        $this->materiaId = $materiaId;
        $this->contenidoId = $contenidoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Obtener la materia
            $materia = Materia::find($this->materiaId);
            if (!$materia) {
                Log::error("Materia no encontrada: {$this->materiaId}");
                return;
            }

            // Obtener el archivo PDF si existe
            $pdfText = null;
            $archivosPdf = $materia->archivos()->where('tipo', 'pdf')->first();
            
            if ($archivosPdf) {
                // Intenta extraer texto del PDF
                $iaService = app(MateriaIAService::class);
                // Nota: Aquí necesitarías pasar el archivo real o el texto previamente extraído
                // Por ahora usamos una descripción simple
                $pdfText = $archivosPdf->nombre_original ?? '';
            }

            // Procesar para cada nivel
            $niveles = ['basico', 'intermedio', 'avanzado'];
            $iaService = app(MateriaIAService::class);

            foreach ($niveles as $nivel) {
                // Construir el pack de aprendizaje
                $pack = $iaService->buildLearningPack($materia, $pdfText, $nivel);

                // Guardar contenido generado
                $contenidoGenerado = MateriaContenido::create([
                    'materia_id' => $this->materiaId,
                    'titulo' => $pack['titulo'],
                    'tipo' => 'generado',
                    'nivel' => $nivel,
                    'descripcion' => $pack['resumen'],
                    'contenido' => json_encode([
                        'introduccion' => $pack['introduccion'] ?? null,
                        'contenidos' => $pack['contenidos'] ?? [],
                        'ejercicios_interactivos' => $pack['ejercicios_interactivos'] ?? [],
                        'actividades' => $pack['actividades'] ?? [],
                        'retos' => $pack['retos'] ?? [],
                        'preguntas_frecuentes' => $pack['preguntas_frecuentes'] ?? [],
                        'aprendizaje_progresivo' => $pack['aprendizaje_progresivo'] ?? [],
                    ]),
                ]);

                // Guardar actividades asociadas (ejercicios interactivos)
                if (isset($pack['ejercicios_interactivos']) && is_array($pack['ejercicios_interactivos'])) {
                    foreach ($pack['ejercicios_interactivos'] as $ejercicioData) {
                        MateriaActividad::create([
                            'materia_id' => $this->materiaId,
                            'materia_contenido_id' => $contenidoGenerado->id,
                            'titulo' => $ejercicioData['titulo'] ?? 'Ejercicio interactivo',
                            'tipo' => $ejercicioData['tipo'] ?? 'quiz',
                            'nivel' => $nivel,
                            'descripcion' => $ejercicioData['descripcion'] ?? '',
                            'estado' => 'activa',
                        ]);
                    }
                }

                // Guardar actividades generales
                if (isset($pack['actividades']) && is_array($pack['actividades'])) {
                    foreach ($pack['actividades'] as $actividadData) {
                        MateriaActividad::create([
                            'materia_id' => $this->materiaId,
                            'materia_contenido_id' => $contenidoGenerado->id,
                            'titulo' => $actividadData['titulo'] ?? 'Actividad sin título',
                            'tipo' => $actividadData['tipo'] ?? 'practica',
                            'nivel' => $nivel,
                            'descripcion' => $actividadData['descripcion'] ?? '',
                            'estado' => 'activa',
                        ]);
                    }
                }

                // Guardar retos/desafíos
                if (isset($pack['retos']) && is_array($pack['retos'])) {
                    foreach ($pack['retos'] as $retoData) {
                        MateriaActividad::create([
                            'materia_id' => $this->materiaId,
                            'materia_contenido_id' => $contenidoGenerado->id,
                            'titulo' => $retoData['titulo'] ?? 'Reto sin título',
                            'tipo' => 'proyecto',
                            'nivel' => $nivel,
                            'descripcion' => $retoData['descripcion'] ?? '',
                            'estado' => 'activa',
                        ]);
                    }
                }
            }

            Log::info("Procesamiento de PDF completado para materia: {$this->materiaId}");
        } catch (\Exception $e) {
            Log::error("Error procesando PDF con IA: " . $e->getMessage(), [
                'materia_id' => $this->materiaId,
                'contenido_id' => $this->contenidoId,
                'exception' => $e
            ]);
        }
    }
}
