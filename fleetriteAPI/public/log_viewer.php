<?php
$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = file($logPath);
    echo "<pre>";
    echo implode("", array_slice($lines, -50));
    echo "</pre>";
} else {
    echo "Log file not found at: " . $logPath;
}
