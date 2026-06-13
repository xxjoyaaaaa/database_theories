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
| 接收資料
|--------------------------------------------------------------------------
*/

$activity_id = $_POST['activity_id'] ?? '';
$status = $_POST['status'] ?? '';

if ($activity_id === '' || $status === '') {
    die('錯誤：缺少 activity_id 或 status');
}

/*
|--------------------------------------------------------------------------
| 只允許兩種狀態
|--------------------------------------------------------------------------
*/

$allowed_status = ['感興趣', '已購票'];

if (!in_array($status, $allowed_status)) {
    die('錯誤：不合法的行程狀態');
}

/*
|--------------------------------------------------------------------------
| 開始處理
|--------------------------------------------------------------------------
*/

$db->begin_transaction();

try {
    /*
    |--------------------------------------------------------------------------
    | 1. 新增或更新 SCHEDULE
    |--------------------------------------------------------------------------
    | 如果同一個 user_id + activity_id 已經存在，
    | 就只更新 status。
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
        throw new Exception('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param(
        "ssss",
        $schedule_id,
        $user_id,
        $activity_id,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | 2. 查出真正的 schedule_id 和活動時間
    |--------------------------------------------------------------------------
    | 因為如果是 ON DUPLICATE KEY UPDATE，
    | 剛剛產生的新 schedule_id 不一定有被使用。
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
        throw new Exception('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param("ss", $user_id, $activity_id);

    if (!$stmt->execute()) {
        throw new Exception('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->bind_result($real_schedule_id, $activity_time);

    if (!$stmt->fetch()) {
        throw new Exception('找不到對應的行程資料');
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | 3. 自動計算提醒時間：活動前一天中午 12:00
    |--------------------------------------------------------------------------
    */

    $activity_datetime = new DateTime($activity_time);
    $activity_datetime->modify('-1 day');
    $activity_datetime->setTime(12, 0, 0);

    $reminder_time = $activity_datetime->format('Y-m-d H:i:s');

    /*
    |--------------------------------------------------------------------------
    | 4. 避免重複提醒：先刪掉這筆行程原本的提醒
    |--------------------------------------------------------------------------
    */

    $sql = "
        DELETE FROM REMINDER
        WHERE schedule_id = ?
    ";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        throw new Exception('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param("s", $real_schedule_id);

    if (!$stmt->execute()) {
        throw new Exception('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | 5. 新增自動提醒
    |--------------------------------------------------------------------------
    */

    $reminder_id = 'R' . date('YmdHis') . mt_rand(100, 999);
    $notify_method = 'email';

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
        throw new Exception('SQL prepare 失敗：' . $db->error);
    }

    $stmt->bind_param(
        "ssss",
        $reminder_id,
        $real_schedule_id,
        $reminder_time,
        $notify_method
    );

    if (!$stmt->execute()) {
        throw new Exception('SQL execute 失敗：' . $stmt->error);
    }

    $stmt->close();

    $db->commit();

    header("Location: ../pages/activity_detail.php?id=" . urlencode($activity_id));
    exit;

} catch (Exception $e) {
    $db->rollback();
    die('錯誤：' . $e->getMessage());
}
?>