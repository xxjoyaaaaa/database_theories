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

$tab = $_GET["tab"] ?? "reminder";
$message = "";

/*
|--------------------------------------------------------------------------
| 處理 POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | 標記已提醒
    |--------------------------------------------------------------------------
    */

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

        header("Location: profile.php?tab=reminder");
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | 修改使用者名稱
    |--------------------------------------------------------------------------
    */

    if ($action === "update_profile") {
        $new_name = trim($_POST["name"] ?? "");

        if ($new_name === "") {
            $message = "使用者名稱不能為空";
            $tab = "info";
        } else {
            $sql = "
                UPDATE USERS
                SET name = ?
                WHERE user_id = ?
            ";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                die("SQL prepare 失敗：" . $db->error);
            }

            $stmt->bind_param("ss", $new_name, $user_id);

            if (!$stmt->execute()) {
                die("SQL execute 失敗：" . $stmt->error);
            }

            $stmt->close();

            $_SESSION["name"] = $new_name;

            header("Location: profile.php?tab=info&updated=1");
            exit;
        }
    }
}

/*
|--------------------------------------------------------------------------
| 查詢使用者個人資料
|--------------------------------------------------------------------------
*/

$user_info_name = "";
$user_info_email = "";

$sql = "
    SELECT name, email
    FROM USERS
    WHERE user_id = ?
    LIMIT 1
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die("SQL prepare 失敗：" . $db->error);
}

$stmt->bind_param("s", $user_id);

if (!$stmt->execute()) {
    die("SQL execute 失敗：" . $stmt->error);
}

$stmt->bind_result($user_info_name, $user_info_email);
$stmt->fetch();
$stmt->close();

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

/*
|--------------------------------------------------------------------------
| 函式
|--------------------------------------------------------------------------
*/

function getReminderText($reminder) {
    if ($reminder["is_sent"]) {
        return $reminder["activity_name"] . " 已完成提醒";
    }

    if (strtotime($reminder["reminder_time"]) <= time()) {
        return $reminder["activity_name"] . " 提醒時間已到";
    }

    return $reminder["activity_name"] . " 將於 " . date("m/d H:i", strtotime($reminder["reminder_time"])) . " 提醒";
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
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #111;
        }

        .top-bar {
            width: 100%;
            height: 72px;
            background-color: #d9d9d9;
            border-top: 2px solid #555;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 28px;
        }

        .system-title {
            font-size: 30px;
            font-weight: 400;
        }

        .top-nav {
            display: flex;
            gap: 28px;
        }

        .top-nav a {
            text-decoration: none;
            color: black;
            font-size: 24px;
            font-weight: 500;
        }

        .top-nav a:hover {
            text-decoration: underline;
        }

        .page {
            width: 94%;
            height: calc(100vh - 72px);
            margin: 0 auto;
            display: flex;
            gap: 42px;
            padding-top: 38px;
            background-color: #ffffff;
        }

        .sidebar {
            width: 270px;
            height: 430px;
            background-color: #d9d9d9;
            border-radius: 10px 10px 0 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 45px;
        }

        .avatar {
            width: 170px;
            height: 170px;
            border-radius: 50%;
            background-color: #aaa;
            position: relative;
            overflow: hidden;
            margin-bottom: 34px;
        }

        .avatar::before {
            content: "";
            position: absolute;
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background-color: #d9d9d9;
            top: 34px;
            left: 54px;
        }

        .avatar::after {
            content: "";
            position: absolute;
            width: 140px;
            height: 90px;
            border-radius: 50% 50% 0 0;
            background-color: #d9d9d9;
            bottom: -15px;
            left: 15px;
        }

        .side-menu {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
        }

        .side-menu a {
            text-decoration: none;
            color: black;
            font-size: 26px;
            font-weight: 400;
        }

        .side-menu a.active {
            font-weight: bold;
        }

        .content {
            flex: 1;
            height: 430px;
            background-color: #d9d9d9;
            border-radius: 10px 10px 0 0;
            padding: 26px 38px;
            overflow-y: auto;
            position: relative;
        }

        .content::-webkit-scrollbar {
            width: 8px;
        }

        .content::-webkit-scrollbar-thumb {
            background-color: #666;
            border-radius: 10px;
        }

        .reminder-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .reminder-card {
            position: relative;
            background-color: #eeeeee;
            border-radius: 10px;
            padding: 22px 38px;
            min-height: 86px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .reminder-card a {
            text-decoration: none;
            color: black;
            font-size: 28px;
            line-height: 1.4;
        }

        .reminder-detail {
            margin-top: 8px;
            font-size: 15px;
            color: #555;
        }

        .red-dot {
            position: absolute;
            width: 24px;
            height: 24px;
            background-color: #ff3333;
            border-radius: 50%;
            right: -6px;
            top: -6px;
        }

        .small-info {
            margin-top: 8px;
            font-size: 14px;
            color: #555;
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

        .mark-btn {
            margin-top: 10px;
            width: 120px;
            padding: 7px 12px;
            border: none;
            border-radius: 20px;
            background-color: #999;
            color: white;
            cursor: pointer;
        }

        .mark-btn:hover {
            background-color: #777;
        }

        .empty-message {
            font-size: 24px;
            color: #555;
            padding: 30px;
        }

        .password-area,
        .profile-area {
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .password-form,
        .profile-form {
            display: grid;
            grid-template-columns: 140px 260px;
            row-gap: 16px;
            column-gap: 22px;
            align-items: center;
        }

        .password-form label,
        .profile-form label {
            text-align: right;
            font-size: 24px;
        }

        .password-form input,
        .profile-form input {
            width: 260px;
            height: 38px;
            border: none;
            border-radius: 18px;
            padding: 0 15px;
            font-size: 18px;
            background-color: white;
        }

        .profile-form input[readonly] {
            background-color: #eeeeee;
            color: #666;
        }

        .password-form button,
        .profile-form button {
            grid-column: 2;
            margin-top: 12px;
            width: 130px;
            padding: 8px 0;
            border: none;
            border-radius: 18px;
            background-color: #999;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        .password-form button:hover,
        .profile-form button:hover {
            background-color: #777;
        }

        .success-message {
            grid-column: 2;
            color: green;
            font-size: 15px;
        }

        .error-message {
            grid-column: 2;
            color: red;
            font-size: 15px;
        }
    </style>
</head>

<body>

<header class="top-bar">
    <div class="system-title">活動導購與行程紀錄系統</div>

    <nav class="top-nav">
        <a href="../backend/my_schedule.php">行事曆</a>
        <a href="profile.php">使用者</a>
    </nav>
</header>

<div class="page">

    <aside class="sidebar">
        <div class="avatar"></div>

        <nav class="side-menu">
            <a href="profile.php?tab=reminder" class="<?= $tab === 'reminder' ? 'active' : '' ?>">提醒</a>
            <a href="profile.php?tab=info" class="<?= $tab === 'info' ? 'active' : '' ?>">個人資料</a>
            <a href="profile.php?tab=password" class="<?= $tab === 'password' ? 'active' : '' ?>">修改密碼</a>
            <a href="../backend/logout.php">登出</a>
        </nav>
    </aside>

    <main class="content">

        <?php if ($tab === "password"): ?>

            <div class="password-area">
                <form class="password-form" action="../backend/update_password.php" method="post">
                    <label>原密碼</label>
                    <input type="password" name="old_password" required>

                    <label>新密碼</label>
                    <input type="password" name="new_password" required>

                    <label>確認新密碼</label>
                    <input type="password" name="confirm_password" required>

                    <button type="submit">確認修改</button>
                </form>
            </div>

        <?php elseif ($tab === "info"): ?>

            <div class="profile-area">
                <form class="profile-form" action="profile.php?tab=info" method="post">
                    <input type="hidden" name="action" value="update_profile">

                    <label>使用者名稱</label>
                    <input
                        type="text"
                        name="name"
                        value="<?= htmlspecialchars($user_info_name) ?>"
                        required
                    >

                    <label>Email</label>
                    <input
                        type="email"
                        value="<?= htmlspecialchars($user_info_email) ?>"
                        readonly
                    >

                    <?php if (isset($_GET["updated"])): ?>
                        <div class="success-message">個人資料已更新</div>
                    <?php endif; ?>

                    <?php if ($message !== ""): ?>
                        <div class="error-message">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit">儲存修改</button>
                </form>
            </div>

        <?php else: ?>

            <?php if (count($reminders) === 0): ?>

                <div class="empty-message">
                    目前沒有提醒。
                </div>

            <?php else: ?>

                <div class="reminder-list">
                    <?php foreach ($reminders as $reminder): ?>
                        <?php
                            $is_due = !$reminder["is_sent"] && strtotime($reminder["reminder_time"]) <= time();
                            $status_class = getScheduleStatusClass($reminder["display_status"]);
                        ?>

                        <div class="reminder-card">
                            <?php if ($is_due): ?>
                                <div class="red-dot"></div>
                            <?php endif; ?>

                            <a href="activity_detail.php?id=<?= htmlspecialchars($reminder["activity_id"]) ?>">
                                <?= htmlspecialchars(getReminderText($reminder)) ?>
                            </a>

                            <div class="reminder-detail">
                                活動時間：
                                <?= date("Y/m/d H:i", strtotime($reminder["activity_time"])) ?>
                                ｜
                                行程狀態：
                                <span class="<?= $status_class ?>">
                                    <?= htmlspecialchars($reminder["display_status"]) ?>
                                </span>
                            </div>

                            <div class="small-info">
                                提醒時間：
                                <?= date("Y/m/d H:i", strtotime($reminder["reminder_time"])) ?>
                            </div>

                            <?php if ($is_due): ?>
                                <form action="profile.php?tab=reminder" method="post">
                                    <input type="hidden" name="action" value="mark_sent">
                                    <input type="hidden" name="reminder_id" value="<?= htmlspecialchars($reminder["reminder_id"]) ?>">

                                    <button type="submit" class="mark-btn">
                                        標記已提醒
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </main>

</div>

</body>
</html>