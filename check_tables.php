<?php
$ms = mysqli_connect('localhost', 'gps_db', '7rxyiXdzxhANBpda', 'gps_db', 3306);
if (!$ms) { die("Conn failed"); }

$res = mysqli_query($ms, "SHOW TABLES LIKE 'route_data_sensors_repotrs2%'");
while ($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
