<?php
$host = '127.0.0.1';
$user = 'gps_db';
$pass = '7rxyiXdzxhANBpda';
$db   = 'gps_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM drives_stops_reports2_api WHERE type = 1 LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    print_r($result->fetch_assoc());
} else {
    echo "No row found";
}
$conn->close();
?>
