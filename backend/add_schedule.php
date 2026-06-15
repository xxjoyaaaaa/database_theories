<?php
session_start();
date_default_timezone_set('Asia/Taipei');
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
| 只允許使用者手動設定兩種狀態
| 已舉行是系統自動判斷，不讓使用者手動送出
|--------------------------------------------------------------------------
*/

$allowed_status = ['感興趣', '已購票'];

if (!in_array($status, $allowed_status)) {
    die('錯誤：不合法的行程狀態');
}

/*
|--------------------------------------------------------------------------
| 開始交易
|--------------------------------------------------------------------------
*/

$db->begin_transaction();

try {
    /*
    |--------------------------------------------------------------------------
    | 1. 查詢活動時間，以及使用者目前是否已有這筆行程
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            s.schedule_id,
            s.status,
            a.activity_time,
            a.sales_time
        FROM ACTIVITY a
        LEFT JOIN SCHEDULE s
            ON a.activity_id = s.activity_id
           AND s.user_id = ?
        WHERE a.activity_id = ?
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

    $stmt->bind_result($current_schedule_id, $current_status, $activity_time, $sales_time);

    if (!$stmt->fetch()) {
        throw new Exception('找不到此活動');
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | 2. 如果活動時間已過，不能再新增、取消、修改狀態
    |--------------------------------------------------------------------------
    */

    if (strtotime($activity_time) < time()) {
        throw new Exception('活動已舉行，不能再更改狀態');
    }

    /*
    |--------------------------------------------------------------------------
    | 3. 如果目前狀態和按下的狀態一樣，代表使用者要取消
    |    例如：已經是感興趣，又按一次感興趣
    |--------------------------------------------------------------------------
    */

    if ($current_schedule_id !== null && $current_status === $status) {
        /*
        先刪除提醒
        */
        $sql = "
            DELETE FROM REMINDER
            WHERE schedule_id = ?
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception('SQL prepare 失敗：' . $db->error);
        }

        $stmt->bind_param("s", $current_schedule_id);

        if (!$stmt->execute()) {
            throw new Exception('SQL execute 失敗：' . $stmt->error);
        }

        $stmt->close();

        /*
        再刪除行程
        */
        $sql = "
            DELETE FROM SCHEDULE
            WHERE schedule_id = ?
              AND user_id = ?
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception('SQL prepare 失敗：' . $db->error);
        }

        $stmt->bind_param("ss", $current_schedule_id, $user_id);

        if (!$stmt->execute()) {
            throw new Exception('SQL execute 失敗：' . $stmt->error);
        }

        $stmt->close();

        $db->commit();

        header("Location: ../pages/activity_detail.php?id=" . urlencode($activity_id));
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | 4. 如果沒有行程就新增；如果已有行程但狀態不同就更新
    |--------------------------------------------------------------------------
    */

    if ($current_schedule_id === null) {
        $real_schedule_id = 'S' . date('YmdHis') . mt_rand(100, 999);

        $sql = "
            INSERT INTO SCHEDULE (
                schedule_id,
                user_id,
                activity_id,
                status,
                created_at
            )
            VALUES (?, ?, ?, ?, NOW())
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception('SQL prepare 失敗：' . $db->error);
        }

        $stmt->bind_param(
            "ssss",
            $real_schedule_id,
            $user_id,
            $activity_id,
            $status
        );

        if (!$stmt->execute()) {
            throw new Exception('SQL execute 失敗：' . $stmt->error);
        }

        $stmt->close();

    } else {
        $real_schedule_id = $current_schedule_id;

        $sql = "
            UPDATE SCHEDULE
            SET status = ?
            WHERE schedule_id = ?
              AND user_id = ?
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            throw new Exception('SQL prepare 失敗：' . $db->error);
        }

        $stmt->bind_param(
            "sss",
            $status,
            $real_schedule_id,
            $user_id
        );

        if (!$stmt->execute()) {
            throw new Exception('SQL execute 失敗：' . $stmt->error);
        }

        $stmt->close();
    }

    /*
    |--------------------------------------------------------------------------
    | 5. 自動設定提醒時間
    |--------------------------------------------------------------------------
    | 感興趣：售票時間前 12 小時提醒
    | 已購票：活動前一天中午 12:00 提醒
    */

    $reminder_time = null;

    if ($status === '感興趣') {
        if (!empty($sales_time)) {
            $sales_datetime = new DateTime($sales_time);
            $sales_datetime->modify('-12 hours');
            $reminder_time = $sales_datetime->format('Y-m-d H:i:s');
        }
    } else if ($status === '已購票') {
        $activity_datetime = new DateTime($activity_time);
        $activity_datetime->modify('-1 day');
        $activity_datetime->setTime(12, 0, 0);
        $reminder_time = $activity_datetime->format('Y-m-d H:i:s');
    }

    /*
    |--------------------------------------------------------------------------
    | 6. 避免重複提醒，先刪除原本提醒
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
    | 7. 新增自動提醒
    |--------------------------------------------------------------------------
    | 若活動沒有設定 sales_time，感興趣狀態就不建立售票提醒。
    */

    if ($reminder_time !== null) {
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
    }

    $db->commit();

    header("Location: ../pages/activity_detail.php?id=" . urlencode($activity_id));
    exit;

} catch (Exception $e) {
    $db->rollback();
    die('錯誤：' . $e->getMessage());
}
?>