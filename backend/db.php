<?php
$servername = "140.122.184.121";
$username = "team10";
$password = "2\$wyyN@mC9Vu";
$dbname = "root";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8")) {
    die("Error loading character set utf8: " . $conn->error);
}
?>