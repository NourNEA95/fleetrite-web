<?php
require 'fleetriteAPI/vendor/autoload.php';
$app = require_once 'fleetriteAPI/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$schema = DB::select("DESCRIBE `general_information_reports2_api` ");
echo json_encode($schema, JSON_PRETTY_PRINT);
