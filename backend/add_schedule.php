<?php
session_start();
date_default_timezone_set('Asia/Taipei');
require_once 'db.php';

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die('資料庫連線失敗，請檢查 db.php 是否有 $conn 或 $mysqli');
}

$user_id = $_SESSION['user_id'] ?? 'U001';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('錯誤：請使用 POST 方法');
}

$activity_id = $_POST['activity_id'] ?? '';
$status = $_POST['status'] ?? '';

if ($activity_id === '' || $status === '') {
    die('錯誤：缺少 activity_id 或 status');
}

$allowed_status = ['感興趣', '已購票'];

if (!in_array($status, $allowed_status)) {
    die('錯誤：不合法的行程狀態');
}

$db->begin_transaction();

try {
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

    if (strtotime($activity_time) < time()) {
        throw new Exception('活動已舉行，不能再更改狀態');
    }

    /*
    如果目前狀態和按下去的狀態相同，代表取消狀態。
    例如：已經是感興趣，再按一次感興趣。
    */
    if ($current_schedule_id !== null && $current_status === $status) {
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
    沒有行程就新增，有行程但狀態不同就更新。
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

        $stmt->bind_param("sss", $status, $real_schedule_id, $user_id);

        if (!$stmt->execute()) {
            throw new Exception('SQL execute 失敗：' . $stmt->error);
        }

        $stmt->close();
    }

    /*
    狀態改變後，先刪掉舊提醒。
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
    建立新提醒：
    感興趣：售票時間前 12 小時提醒。
    已購票：活動前一天中午 12:00 提醒。
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