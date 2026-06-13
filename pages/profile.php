<?php
session_start();
require_once '../backend/db.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../backend/login.php");
    exit;
}

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die("資料庫連線失敗，請檢查 db.php");
}

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["name"] ?? "使用者";

/*
|--------------------------------------------------------------------------
| 處理標記已提醒
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "mark_sent") {
        $reminder_id = $_POST["reminder_id"] ?? "";

        if ($reminder_id !== "") {
            $sql = "
                UPDATE REMINDER r
                JOIN SCHEDULE s
                    ON r.schedule_id = s.schedule_id
                SET r.is_sent = TRUE
                WHERE r.reminder_id = ?
                  AND s.user_id = ?
            ";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                die("SQL prepare 失敗：" . $db->error);
            }

            $stmt->bind_param("ss", $reminder_id, $user_id);

            if (!$stmt->execute()) {
                die("SQL execute 失敗：" . $stmt->error);
            }

            $stmt->close();
        }

        header("Location: profile.php");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 查詢提醒列表
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        r.reminder_id,
        r.reminder_time,
        r.notify_method,
        r.is_sent,

        s.schedule_id,

        CASE
            WHEN a.activity_time < NOW() THEN '已舉行'
            ELSE s.status
        END AS display_status,

        a.activity_id,
        a.name AS activity_name,
        a.activity_time,
        a.location
    FROM REMINDER r
    JOIN SCHEDULE s
        ON r.schedule_id = s.schedule_id
    JOIN ACTIVITY a
        ON s.activity_id = a.activity_id
    WHERE s.user_id = ?
    ORDER BY r.reminder_time ASC
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die("SQL prepare 失敗：" . $db->error);
}

$stmt->bind_param("s", $user_id);

if (!$stmt->execute()) {
    die("SQL execute 失敗：" . $stmt->error);
}

$result = $stmt->get_result();

$reminders = [];

while ($row = $result->fetch_assoc()) {
    $reminders[] = $row;
}

$stmt->close();

function getReminderStatusText($reminder_time, $is_sent) {
    if ($is_sent) {
        return "已提醒";
    }

    if (strtotime($reminder_time) <= time()) {
        return "提醒時間已到";
    }

    return "尚未到提醒時間";
}

function getReminderStatusClass($reminder_time, $is_sent) {
    if ($is_sent) {
        return "sent";
    }

    if (strtotime($reminder_time) <= time()) {
        return "due";
    }

    return "pending";
}

function getScheduleStatusClass($status) {
    if ($status === "感興趣") {
        return "status-interested";
    } else if ($status === "已購票") {
        return "status-paid";
    } else if ($status === "已舉行") {
        return "status-finished";
    }

    return "";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>使用者資料</title>
    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background-color: #f5f5f5;
            padding: 30px;
        }

        .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            margin-bottom: 20px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }

        .menu a {
            text-decoration: none;
            color: black;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .menu a:hover {
            background-color: #eee;
        }

        .section-title {
            margin-top: 25px;
            margin-bottom: 15px;
            font-size: 22px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 8px;
        }

        .empty-message {
            background-color: #f8f8f8;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 18px;
            color: #666;
        }

        .reminder-card {
            border: 1px solid #bbb;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            background-color: #fafafa;
        }

        .reminder-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .reminder-title a {
            color: #222;
            text-decoration: none;
        }

        .reminder-title a:hover {
            text-decoration: underline;
        }

        .reminder-card p {
            margin: 6px 0;
            font-size: 15px;
        }

        .status-interested {
            color: blue;
            font-weight: bold;
        }

        .status-paid {
            color: red;
            font-weight: bold;
        }

        .status-finished {
            color: gray;
            font-weight: bold;
        }

        .reminder-state {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        .pending {
            background-color: #eeeeee;
            color: #555;
        }

        .due {
            background-color: #ffe0e0;
            color: #b00000;
            font-weight: bold;
        }

        .sent {
            background-color: #e0e0e0;
            color: #777;
        }

        .mark-btn {
            margin-top: 10px;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            background-color: #999;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }

        .mark-btn:hover {
            background-color: #777;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>使用者資料</h2>

    <p>
        歡迎，
        <strong><?php echo htmlspecialchars($user_name); ?></strong>
    </p>

    <br>

    <div class="menu">
        <a href="#reminder-section">提醒</a>
        <a href="../backend/update_password.php">修改密碼</a>
        <a href="../backend/logout.php">登出</a>
    </div>

    <h3 id="reminder-section" class="section-title">提醒列表</h3>

    <?php if (count($reminders) === 0): ?>

        <div class="empty-message">
            目前沒有提醒。加入「感興趣」或「已購票」後，系統會自動建立活動前一天中午 12:00 的提醒。
        </div>

    <?php else: ?>

        <?php foreach ($reminders as $reminder): ?>
            <?php
                $reminder_status_text = getReminderStatusText(
                    $reminder["reminder_time"],
                    $reminder["is_sent"]
                );

                $reminder_status_class = getReminderStatusClass(
                    $reminder["reminder_time"],
                    $reminder["is_sent"]
                );

                $schedule_status_class = getScheduleStatusClass($reminder["display_status"]);
            ?>

            <div class="reminder-card">
                <div class="reminder-title">
                    <a href="activity_detail.php?id=<?php echo htmlspecialchars($reminder["activity_id"]); ?>">
                        <?php echo htmlspecialchars($reminder["activity_name"]); ?>
                    </a>
                </div>

                <p>
                    活動時間：
                    <?php echo date("Y/m/d H:i", strtotime($reminder["activity_time"])); ?>
                </p>

                <p>
                    活動地點：
                    <?php echo htmlspecialchars($reminder["location"]); ?>
                </p>

                <p>
                    行程狀態：
                    <span class="<?php echo $schedule_status_class; ?>">
                        <?php echo htmlspecialchars($reminder["display_status"]); ?>
                    </span>
                </p>

                <p>
                    提醒時間：
                    <?php echo date("Y/m/d H:i", strtotime($reminder["reminder_time"])); ?>
                </p>

                <p>
                    提醒方式：
                    <?php echo htmlspecialchars($reminder["notify_method"]); ?>
                </p>

                <p>
                    提醒狀態：
                    <span class="reminder-state <?php echo $reminder_status_class; ?>">
                        <?php echo $reminder_status_text; ?>
                    </span>
                </p>

                <?php if (!$reminder["is_sent"] && strtotime($reminder["reminder_time"]) <= time()): ?>
                    <form action="profile.php" method="post">
                        <input type="hidden" name="action" value="mark_sent">
                        <input type="hidden" name="reminder_id" value="<?php echo htmlspecialchars($reminder["reminder_id"]); ?>">

                        <button type="submit" class="mark-btn">
                            標記已提醒
                        </button>
                    </form>
                <?php endif; ?>
            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>