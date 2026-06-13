<?php

$host = "127.0.0.1";
$user = "root";
$password = ""; // 你的 MySQL 密碼
$database = "team10_activity_planner";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("資料庫連線失敗: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
