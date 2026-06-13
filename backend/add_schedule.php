<?php
session_start();
require_once 'db.php';

/*
|--------------------------------------------------------------------------
| 取得資料庫連線
|--------------------------------------------------------------------------
*/

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die('資料庫連線失敗，請檢查 db.php 是否有 $conn 或 $mysqli');
}

/*
|--------------------------------------------------------------------------
| 取得目前使用者
|--------------------------------------------------------------------------
| 正式版應該使用 $_SESSION['user_id']
| 如果登入還沒整合完成，先用 U001 測試
*/

$user_id = $_SESSION['user_id'] ?? 'U001';

/*
|--------------------------------------------------------------------------
| 只接受 POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('錯誤：請使用 POST 方法');
}

/*
|--------------------------------------------------------------------------
| 判斷這次要做什麼
|--------------------------------------------------------------------------
| update_status：新增行程 / 更新狀態
| add_reminder：新增提醒
|
| 如果舊表單沒有傳 action，但有傳 status，就預設為 update_status
*/

$action = $_POST['action'] ?? '';

if ($action === '' && isset($_POST['status'])) {
    $action = 'update_status';
}

if ($action === '') {
    die('錯誤：缺少 action');
}

/*
|--------------------------------------------------------------------------
| 功能一：新增行程 / 更新行程狀態
|--------------------------------------------------------------------------
| 使用位置：pages/activity_detail.php
| 使用者按「感興趣 / 預定參加 / 已購票」
|--------------------------------------------------------------------------
*/

if ($action === 'update_status') {
    $activity_id = $_POST['activity_id'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($activity_id === '' || $status === '') {
        die('錯誤：缺少 activity_id 或 status');
    }

    $allowed_status = ['感興趣', '預定參加', '已購票'];

    if (!in_array($status, $allowed_status)) {
        die('錯誤：不合法的行程狀態');
    }

    /*
    產生新的 schedule_id。
    如果同一個 user_id + activity_id 已存在，
    會觸發 UNIQUE(user_id, activity_id)，然後改成更新 status。
    */
    $schedule_id = 'S' . date('YmdHis') . mt_rand(100, 999);

    $sql = "
        INSERT INTO SCHEDULE (
            schedule_id,
            user_id,
            activity_id,
            status,
            created_at
        )
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status)
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        die('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param(
        "ssss",
        $schedule_id,
        $user_id,
        $activity_id,
        $status
    );

    if (!$stmt->execute()) {
        die('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->close();

    header("Location: ../pages/activity_detail.php?id=" . urlencode($activity_id));
    exit;
}

/*
|--------------------------------------------------------------------------
| 功能二：新增提醒
|--------------------------------------------------------------------------
| 使用位置：pages/activity_detail.php
| 使用者必須先把活動加入行事曆，才能設定提醒
|--------------------------------------------------------------------------
*/

if ($action === 'add_reminder') {
    $activity_id = $_POST['activity_id'] ?? '';
    $reminder_time = $_POST['reminder_time'] ?? '';
    $notify_method = $_POST['notify_method'] ?? 'email';

    if ($activity_id === '' || $reminder_time === '') {
        die('錯誤：缺少 activity_id 或 reminder_time');
    }

    $allowed_methods = ['email', 'push'];

    if (!in_array($notify_method, $allowed_methods)) {
        die('錯誤：不合法的提醒方式');
    }

    /*
    datetime-local 傳來通常是 2026-07-19T10:00
    MySQL DATETIME 要改成 2026-07-19 10:00:00
    */
    $reminder_time = str_replace('T', ' ', $reminder_time);

    if (strlen($reminder_time) === 16) {
        $reminder_time .= ':00';
    }

    $reminder_timestamp = strtotime($reminder_time);

    if ($reminder_timestamp === false) {
        die('錯誤：提醒時間格式不正確');
    }

    /*
    先找出這個使用者對這個活動的 schedule_id。
    REMINDER 是連到 SCHEDULE，不是直接連到 ACTIVITY。
    */
    $sql = "
        SELECT
            s.schedule_id,
            a.activity_time
        FROM SCHEDULE s
        JOIN ACTIVITY a
            ON s.activity_id = a.activity_id
        WHERE s.user_id = ?
          AND s.activity_id = ?
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        die('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param("ss", $user_id, $activity_id);

    if (!$stmt->execute()) {
        die('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->bind_result($schedule_id, $activity_time);

    if (!$stmt->fetch()) {
        $stmt->close();
        die('錯誤：請先將此活動加入行事曆，才能設定提醒');
    }

    $stmt->close();

    /*
    提醒時間必須早於活動開始時間
    */
    $activity_timestamp = strtotime($activity_time);

    if ($reminder_timestamp >= $activity_timestamp) {
        die('錯誤：提醒時間必須早於活動開始時間');
    }

    /*
    新增提醒
    */
    $reminder_id = 'R' . date('YmdHis') . mt_rand(100, 999);

    $sql = "
        INSERT INTO REMINDER (
            reminder_id,
            schedule_id,
            reminder_time,
            notify_method,
            is_sent
        )
        VALUES (?, ?, ?, ?, FALSE)
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        die('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param(
        "ssss",
        $reminder_id,
        $schedule_id,
        $reminder_time,
        $notify_method
    );

    if (!$stmt->execute()) {
        die('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->close();

    header("Location: ../pages/activity_detail.php?id=" . urlencode($activity_id));
    exit;
}

/*
|--------------------------------------------------------------------------
| 如果 action 不是上面兩種
|--------------------------------------------------------------------------
*/

die('錯誤：未知的 action');
?>