<?php
include 'fleetriteAPI/vendor/autoload.php';
$app = include 'fleetriteAPI/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$row = DB::table('drives_stops_reports2_api')->where('type', 1)->first();
print_r($row);
