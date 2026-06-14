<?php
session_start();
date_default_timezone_set('Asia/Taipei');
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

        header("Location: profile.php?tab=reminder");
        exit;
    }

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
        a.sales_time,
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

function getReminderText($reminder) {
    if ($reminder["is_sent"]) {
        return $reminder["activity_name"] . " 已完成提醒";
    }

    if ($reminder["display_status"] === "感興趣") {
        if (strtotime($reminder["reminder_time"]) <= time()) {
            return $reminder["activity_name"] . " 售票提醒時間已到";
        }

        return $reminder["activity_name"] . " 售票提醒";
    }

    if ($reminder["display_status"] === "已購票") {
        if (strtotime($reminder["reminder_time"]) <= time()) {
            return $reminder["activity_name"] . " 活動提醒時間已到";
        }

        return $reminder["activity_name"] . " 活動提醒";
    }

    if ($reminder["display_status"] === "已舉行") {
        return $reminder["activity_name"] . " 已舉行";
    }

    return $reminder["activity_name"] . " 提醒";
}

function getScheduleStatusClass($status) {
    if ($status === "感興趣") {
        return "text-blue-700 bg-blue-50";
    } else if ($status === "已購票") {
        return "text-red-700 bg-red-50";
    } else if ($status === "已舉行") {
        return "text-gray-600 bg-gray-100";
    }

    return "text-slate-600 bg-slate-100";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>使用者資料</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">

<header class="bg-white/90 backdrop-blur border-b border-slate-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="activity_list.php" class="text-2xl font-bold">
            活動導購與行程紀錄系統
        </a>

        <nav class="flex items-center gap-6 text-sm font-medium">
            <a href="../backend/my_schedule.php" class="hover:text-blue-600">行事曆</a>
            <a href="profile.php" class="text-blue-600">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                <div class="w-28 h-28 rounded-full bg-gradient-to-br from-blue-100 to-slate-200 mx-auto mb-5 flex items-center justify-center">
                    <span class="text-4xl">👤</span>
                </div>

                <div class="text-center mb-6">
                    <h2 class="text-xl font-bold">
                        <?= htmlspecialchars($user_info_name ?: $user_name) ?>
                    </h2>
                    <p class="text-sm text-slate-500 break-all">
                        <?= htmlspecialchars($user_info_email) ?>
                    </p>
                </div>

                <nav class="space-y-2">
                    <a href="profile.php?tab=reminder"
                       class="block px-4 py-3 rounded-2xl font-semibold <?= $tab === 'reminder' ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' ?>">
                        提醒
                    </a>

                    <a href="profile.php?tab=info"
                       class="block px-4 py-3 rounded-2xl font-semibold <?= $tab === 'info' ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' ?>">
                        個人資料
                    </a>

                    <a href="profile.php?tab=password"
                       class="block px-4 py-3 rounded-2xl font-semibold <?= $tab === 'password' ? 'bg-blue-600 text-white' : 'hover:bg-slate-100' ?>">
                        修改密碼
                    </a>

                    <a href="../backend/logout.php"
                       class="block px-4 py-3 rounded-2xl font-semibold text-red-600 hover:bg-red-50">
                        登出
                    </a>
                </nav>
            </div>
        </aside>

        <section class="lg:col-span-3">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 min-h-[520px]">
                <?php if ($tab === "password"): ?>

                    <div class="max-w-md">
                        <h1 class="text-3xl font-bold mb-6">修改密碼</h1>

                        <form action="../backend/update_password.php" method="post" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">原密碼</label>
                                <input type="password" name="old_password" required
                                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">新密碼</label>
                                <input type="password" name="new_password" required
                                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">確認新密碼</label>
                                <input type="password" name="confirm_password" required
                                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <button type="submit"
                                    class="px-6 py-3 rounded-2xl bg-blue-600 text-white font-bold hover:bg-blue-700">
                                確認修改
                            </button>
                        </form>
                    </div>

                <?php elseif ($tab === "info"): ?>

                    <div class="max-w-md">
                        <h1 class="text-3xl font-bold mb-6">個人資料</h1>

                        <form action="profile.php?tab=info" method="post" class="space-y-4">
                            <input type="hidden" name="action" value="update_profile">

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">使用者名稱</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user_info_name) ?>" required
                                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-2">Email</label>
                                <input type="email" value="<?= htmlspecialchars($user_info_email) ?>" readonly
                                       class="w-full px-4 py-3 rounded-2xl border border-slate-200 bg-slate-100 text-slate-500">
                            </div>

                            <?php if (isset($_GET["updated"])): ?>
                                <div class="text-green-600 font-semibold">
                                    個人資料已更新。
                                </div>
                            <?php endif; ?>

                            <?php if ($message !== ""): ?>
                                <div class="text-red-600 font-semibold">
                                    <?= htmlspecialchars($message) ?>
                                </div>
                            <?php endif; ?>

                            <button type="submit"
                                    class="px-6 py-3 rounded-2xl bg-blue-600 text-white font-bold hover:bg-blue-700">
                                儲存修改
                            </button>
                        </form>
                    </div>

                <?php else: ?>

                    <div class="flex items-end justify-between mb-6">
                        <div>
                            <p class="text-sm text-slate-500 mb-2">Reminder</p>
                            <h1 class="text-3xl font-bold">提醒列表</h1>
                        </div>
                    </div>

                    <?php if (count($reminders) === 0): ?>

                        <div class="p-8 rounded-3xl bg-slate-50 text-slate-500">
                            目前沒有提醒。加入「感興趣」或「已購票」後，系統會自動建立提醒。
                        </div>

                    <?php else: ?>

                        <div class="space-y-4">
                            <?php foreach ($reminders as $reminder): ?>
                                <?php
                                    $is_due = !$reminder["is_sent"] && strtotime($reminder["reminder_time"]) <= time();
                                    $status_class = getScheduleStatusClass($reminder["display_status"]);
                                ?>

                                <div class="relative p-5 rounded-3xl border border-slate-200 bg-slate-50">
                                    <?php if ($is_due): ?>
                                        <div class="absolute -right-1 -top-1 w-5 h-5 rounded-full bg-red-500"></div>
                                    <?php endif; ?>

                                    <a href="activity_detail.php?id=<?= htmlspecialchars($reminder["activity_id"]) ?>"
                                       class="text-xl font-bold hover:text-blue-600">
                                        <?= htmlspecialchars(getReminderText($reminder)) ?>
                                    </a>

                                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-slate-600">
                                        <p>活動時間：<?= date("Y/m/d H:i", strtotime($reminder["activity_time"])) ?></p>

                                        <?php if (!empty($reminder["sales_time"])): ?>
                                            <p>售票時間：<?= date("Y/m/d H:i", strtotime($reminder["sales_time"])) ?></p>
                                        <?php endif; ?>

                                        <p>提醒時間：<?= date("Y/m/d H:i", strtotime($reminder["reminder_time"])) ?></p>
                                        <p>活動地點：<?= htmlspecialchars($reminder["location"]) ?></p>
                                    </div>

                                    <div class="mt-4 flex items-center gap-3">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $status_class ?>">
                                            <?= htmlspecialchars($reminder["display_status"]) ?>
                                        </span>

                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $is_due ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-600' ?>">
                                            <?= $reminder["is_sent"] ? '已提醒' : ($is_due ? '提醒時間已到' : '尚未提醒') ?>
                                        </span>
                                    </div>

                                    <?php if ($is_due): ?>
                                        <form action="profile.php?tab=reminder" method="post" class="mt-4">
                                            <input type="hidden" name="action" value="mark_sent">
                                            <input type="hidden" name="reminder_id" value="<?= htmlspecialchars($reminder["reminder_id"]) ?>">

                                            <button type="submit"
                                                    class="px-4 py-2 rounded-full bg-slate-900 text-white text-sm font-bold hover:bg-slate-700">
                                                標記已提醒
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

</body>
</html>