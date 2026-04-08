<?php
require 'fleetriteAPI/vendor/autoload.php';
$app = require_once 'fleetriteAPI/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $schema = DB::select("DESCRIBE `route_data_sensors_repotrs2_api` ");
    echo json_encode($schema, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
