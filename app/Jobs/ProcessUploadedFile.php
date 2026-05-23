<?php

namespace App\Jobs;

use App\Models\MateriaArchivo;
use App\Services\MateriaIAService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ProcessUploadedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $archivoId;

    public function __construct(int $archivoId)
    {
        $this->archivoId = $archivoId;
    }

    public function handle(MateriaIAService $iaService)
    {
        $archivo = MateriaArchivo::find($this->archivoId);
        if (!$archivo) {
            return;
        }

        // Solo procesamos PDFs por ahora
        if ($archivo->tipo !== 'pdf') {
            return;
        }

        $disk = Storage::disk('public');
        $path = $disk->path($archivo->ruta);
        if (!file_exists($path)) {
            return;
        }

        // Crear UploadedFile de prueba para reutilizar extractor
        $uploaded = new UploadedFile($path, $archivo->nombre_original, $archivo->mime_type, null, true);

        try {
            $texto = $iaService->extractPdfText($uploaded);

            // Guardar el texto extraído en el contenido asociado
            if ($archivo->materia_contenido_id) {
                $contenido = $archivo->contenido;
                if ($contenido) {
                    $contenido->contenido_texto = $texto;
                    $contenido->save();
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('ProcessUploadedFile failed: ' . $e->getMessage());
        }
    }
}
