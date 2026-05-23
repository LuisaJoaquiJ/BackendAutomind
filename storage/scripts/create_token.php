<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::find(7);
if (!$u) {
    echo "NO_USER\n";
    exit(1);
}
$token = $u->createToken('test-upload')->plainTextToken;
echo $token . "\n";
