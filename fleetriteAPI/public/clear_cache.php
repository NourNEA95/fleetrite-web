<?php
use Illuminate\Support\Facades\Artisan;

// We need to bootstrap Laravel to use Artisan facade
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

header('Content-Type: text/plain');
echo "Clearing Laravel Cache via Artisan Facade...\n";

try {
    Artisan::call('config:clear');
    echo "Config Clear: " . Artisan::output() . "\n";

    Artisan::call('route:clear');
    echo "Route Clear: " . Artisan::output() . "\n";

    Artisan::call('optimize:clear');
    echo "Optimize Clear: " . Artisan::output() . "\n";
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
