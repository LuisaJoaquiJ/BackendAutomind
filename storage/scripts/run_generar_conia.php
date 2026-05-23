<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Materia;
use Illuminate\Http\Request;
use App\Http\Controllers\MateriaContenidoController;
use Illuminate\Support\Facades\Log;

$user = User::find(7);
$materia = Materia::find(4);
if (!$user) {
    echo "NO_USER\n";
    exit(1);
}
if (!$materia) {
    echo "NO_MATERIA\n";
    exit(1);
}

$request = Request::create('/docente/materias/' . $materia->id . '/contenido/generar-ia', 'POST', [
    'tema' => 'Recursión en algoritmos',
    'tipo' => 'ejercicios',
    'n'    => 3,
    'nivel'=> 'basico',
]);

// resolver usuario
$request->setUserResolver(function() use ($user) { return $user; });

$controller = new MateriaContenidoController();
try {
    $resp = $controller->generarConIA($request, $materia);
    if ($resp instanceof \Illuminate\Http\JsonResponse) {
        echo $resp->getContent() . "\n";
        exit(0);
    }
    // de otra forma, volcar
    var_dump($resp);
} catch (\Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    Log::error('run_generar_conia exception: ' . $e->getMessage());
    exit(1);
}
