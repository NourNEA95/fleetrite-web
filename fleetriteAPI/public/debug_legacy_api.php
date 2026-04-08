<?php
// Debug script to see what Legacy API returns
include_once "fleetrite_nv_latest_version/func/fn_common.php";

$apiUrl = "https://nv.esoft-eg.com/func/process_api.php";

$postData = [
    'form_user_id' => $_GET['user_id'] ?? 1,
    'user_id' => $_GET['user_id'] ?? 1,
    'dialog_report_object_list' => $_GET['imei'] ?? '',
    'dialog_report_date_from' => $_GET['date_from'] ?? '2026-03-31',
    'dialog_report_hour_from' => '00',
    'dialog_report_minute_from' => '00',
    'dialog_report_date_to' => $_GET['date_to'] ?? '2026-03-31',
    'dialog_report_hour_to' => '23',
    'dialog_report_minute_to' => '59',
    'dialog_report_format' => 'html',
    'dialog_report_ignore_empty_reports' => 'false',
    'dialog_report_show_coordinates' => 'true',
    'dialog_report_show_addresses' => 'false',
    'dialog_report_markers_addresses' => 'false',
    'dialog_report_zones_addresses' => 'false',
    'dialog_report_stop_duration' => 5,
    'dialog_report_speed_limit' => 80,
    'dialog_report_filter' => 2,
    'dialog_report_type' => 'drives_stops_sensors',
    'dialog_report_data_item_list' => 'status,start,end,duration,move_duration,stop_duration,distance_travelled,top_speed,avg_speed,fuel_consumption,avg_fuel_consumption,fuel_cost,engine_idle,driver,trailer',
    'pass_key' => 'AAA21A609BFD46C1437E01867D22913B'
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

header('Content-Type: text/plain');
echo "HTTP CODE: " . $httpCode . "\n";
if ($error) echo "CURL ERROR: " . $error . "\n";
echo "RESPONSE BODY:\n";
echo $response;
?>
