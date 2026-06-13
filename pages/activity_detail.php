<?php
session_start();
require_once '../backend/db.php';

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

$user_id = $_SESSION['user_id'] ?? 'U001';

$activity_id = isset($_GET['id']) ? $_GET['id'] : '';

if ($activity_id == '') {
    header("Location: activity_list.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| 查詢活動資料
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM ACTIVITY
    WHERE activity_id = ?
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die('SQL prepare 失敗：' . $db->error);
}

$stmt->bind_param("s", $activity_id);

if (!$stmt->execute()) {
    die('SQL execute 失敗：' . $stmt->error);
}

$result = $stmt->get_result();
$activity = $result->fetch_assoc();
$stmt->close();

if (!$activity) {
    echo "<script>alert('找不到此活動！'); window.location.href='activity_list.php';</script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| 查詢目前使用者對這個活動的行程狀態
|--------------------------------------------------------------------------
*/

$current_status = null;

$sql = "
    SELECT status
    FROM SCHEDULE
    WHERE user_id = ?
      AND activity_id = ?
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

$stmt->bind_result($current_status);
$stmt->fetch();
$stmt->close();

/*
|--------------------------------------------------------------------------
| 自動判斷是否已舉行
|--------------------------------------------------------------------------
*/

$is_finished = strtotime($activity['activity_time']) < time();

$display_status = $current_status;

if ($is_finished) {
    $display_status = '已舉行';
}

/*
|--------------------------------------------------------------------------
| 狀態顏色
|--------------------------------------------------------------------------
*/

$status_color = "#555";

if ($display_status === "感興趣") {
    $status_color = "blue";
} else if ($display_status === "已購票") {
    $status_color = "red";
} else if ($display_status === "已舉行") {
    $status_color = "gray";
}

/*
|--------------------------------------------------------------------------
| 自動提醒時間：活動前一天中午 12:00
|--------------------------------------------------------------------------
*/

$reminder_datetime = new DateTime($activity['activity_time']);
$reminder_datetime->modify('-1 day');
$reminder_datetime->setTime(12, 0, 0);
$auto_reminder_time = $reminder_datetime->format('Y/m/d H:i');
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activity['name']) ?> - 活動詳細</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #333;
        }

        .navbar {
            background-color: #dcdcdc;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 22px;
            font-weight: normal;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            font-size: 16px;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }

        .main-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .image-placeholder {
            width: 100%;
            height: 350px;
            background-color: #dcdcdc;
            border-radius: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 48px;
            color: #111;
            margin-bottom: 30px;
        }

        .content-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
        }

        .info-left {
            flex: 1;
        }

        .info-left h2 {
            font-size: 28px;
            margin-bottom: 15px;
            font-weight: normal;
        }

        .info-left p {
            font-size: 16px;
            margin-bottom: 10px;
            color: #222;
        }

        .action-right {
            width: 260px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
        }

        .status-text {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .heart-btn {
            border: none;
            background: none;
            font-size: 22px;
            color: #999;
            cursor: pointer;
            text-align: left;
            padding: 8px 0;
        }

        .heart-btn.active {
            color: #ff5e5e;
        }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            background-color: #a0a0a0;
            color: #222;
            transition: background-color 0.2s;
            width: 100%;
        }

        .btn:hover {
            background-color: #888;
        }

        .btn-link {
            background-color: #9e9e9e;
        }

        .finished-notice {
            color: gray;
            font-weight: bold;
            font-size: 16px;
            line-height: 1.6;
        }

        .reminder-box {
            margin-top: 25px;
            padding: 18px;
            border: 1px solid #aaa;
            border-radius: 10px;
            background-color: #f8f8f8;
        }

        .reminder-box h3 {
            margin-bottom: 12px;
            font-size: 20px;
        }

        .notice {
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>

<body>

<nav class="navbar">
    <h1>活動導購與行程紀錄系統</h1>

    <div class="nav-links">
        <a href="activity_list.php">首頁</a>
        <a href="../backend/my_schedule.php">行事曆</a>
        <a href="profile.php">使用者</a>
    </div>
</nav>

<main class="main-container">

    <div class="image-placeholder">
        活動照片
    </div>

    <div class="content-section">

        <div class="info-left">
            <h2><?= htmlspecialchars($activity['name']) ?></h2>

            <p>活動日期：<?= date('Y/m/d H:i', strtotime($activity['activity_time'])) ?></p>
            <p>活動地點：<?= htmlspecialchars($activity['location']) ?></p>

            <?php if (!empty($activity['cache_status'])): ?>
                <p>售票狀態：<?= htmlspecialchars($activity['cache_status']) ?></p>
            <?php endif; ?>

            <p>活動簡述：目前資料庫暫無此欄位，先用這段假字撐撐場面！之後可以補上詳細的活動介紹。</p>

            <p class="status-text">
                目前行程狀態：
                <?php if ($display_status): ?>
                    <strong style="color: <?= $status_color ?>;">
                        <?= htmlspecialchars($display_status) ?>
                    </strong>
                <?php else: ?>
                    <strong>尚未加入行事曆</strong>
                <?php endif; ?>
            </p>
        </div>

        <div class="action-right">

            <?php if (!$is_finished): ?>

                <!-- 感興趣：再按一次可以取消 -->
                <form action="../backend/add_schedule.php" method="post">
                    <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                    <input type="hidden" name="status" value="感興趣">

                    <button type="submit"
                            class="heart-btn <?= ($current_status === '感興趣') ? 'active' : '' ?>">
                        <?= ($current_status === '感興趣') ? '♥ 取消感興趣' : '♡ 感興趣' ?>
                    </button>
                </form>

                <!-- 已購票：再按一次可以取消 -->
                <form action="../backend/add_schedule.php" method="post">
                    <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                    <input type="hidden" name="status" value="已購票">

                    <button type="submit" class="btn">
                        <?= ($current_status === '已購票') ? '取消已購買' : '加入已購買' ?>
                    </button>
                </form>

            <?php else: ?>

                <p class="finished-notice">
                    此活動已舉行，無法再修改狀態。
                </p>

            <?php endif; ?>

            <!-- 導購連結 -->
            <?php if (!empty($activity['external_url']) && !$is_finished): ?>
                <a href="<?= htmlspecialchars($activity['external_url']) ?>"
                   target="_blank"
                   class="btn btn-link">
                    導購連結
                </a>
            <?php else: ?>
                <button class="btn" style="background-color: #eee; color: #aaa; cursor: not-allowed;" disabled>
                    <?= $is_finished ? '活動已結束' : '無導購連結' ?>
                </button>
            <?php endif; ?>

        </div>

    </div>

    <div class="reminder-box">
        <h3>提醒設定</h3>

        <?php if ($is_finished): ?>
            <p class="notice">
                此活動已舉行，不再建立提醒。
            </p>
        <?php else if ($current_status): ?>
            <p class="notice">
                系統已自動設定提醒時間：<?= htmlspecialchars($auto_reminder_time) ?>。
            </p>
        <?php else: ?>
            <p class="notice">
                加入「感興趣」或「已購票」後，系統會自動在活動前一天中午 12:00 建立提醒。
            </p>
        <?php endif; ?>
    </div>

</main>

</body>
</html>