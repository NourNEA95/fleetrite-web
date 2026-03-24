<?php
require 'fleetriteAPI/vendor/autoload.php';
$app = require_once 'fleetriteAPI/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$key = '17743491969478724';
$rows = DB::table('general_information_reports2_api')->where('table_key', $key)->get();
echo json_encode($rows, JSON_PRETTY_PRINT);
