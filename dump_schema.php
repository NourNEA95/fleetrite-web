<?php
$app = require __DIR__ . '/../fleetriteAPI/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$columns = Illuminate\Support\Facades\Schema::getColumnListing('gs_user_objects');
print_r($columns);
$columnsObj = Illuminate\Support\Facades\Schema::getColumnListing('gs_objects');
print_r($columnsObj);
$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
print_r($tables);
