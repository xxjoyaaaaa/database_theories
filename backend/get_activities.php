<?php
require_once 'db.php';

$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_location = isset($_GET['location']) ? $_GET['location'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';

$sql = "SELECT * FROM ACTIVITY WHERE 1=1";

if ($filter_date != '') {
    $sql .= " AND DATE(activity_time) = '" . $conn->real_escape_string($filter_date) . "'";
}
if ($filter_location != '') {
    $sql .= " AND location = '" . $conn->real_escape_string($filter_location) . "'";
}
if ($filter_category != '') {
    $sql .= " AND category_id = '" . $conn->real_escape_string($filter_category) . "'";
}

$sql .= " ORDER BY activity_time DESC";

$result = $conn->query($sql);

$activities = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
}
?>
