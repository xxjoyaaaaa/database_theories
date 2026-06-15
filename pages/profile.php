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

    if ($action === "change_password") {

        $old_password = $_POST["old_password"] ?? "";
        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        $sql = "SELECT password_hash FROM USERS WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!password_verify($old_password, $user["password_hash"])) {

            $message = "原密碼錯誤";
            $tab = "password";

        } elseif ($new_password !== $confirm_password) {

            $message = "兩次新密碼輸入不一致";
            $tab = "password";

        } else {

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $update_sql = "
                UPDATE USERS
                SET password_hash = ?
                WHERE user_id = ?
            ";

            $update_stmt = $db->prepare($update_sql);
            $update_stmt->bind_param("ss", $new_hash, $user_id);

            if ($update_stmt->execute()) {

                header("Location: profile.php?tab=password&updated=1");
                exit;

            } else {

                $message = "修改失敗";
                $tab = "password";
            }
        }
    }

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

        s.status AS schedule_status,

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

    if ($reminder["schedule_status"] === "感興趣") {
        if (strtotime($reminder["reminder_time"]) <= time()) {
            return $reminder["activity_name"] . " 售票提醒時間已到";
        }

        return $reminder["activity_name"] . " 售票提醒";
    }

    if ($reminder["schedule_status"] === "已購票") {
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

function getStatusBadgeClass($status) {
    if ($status === "感興趣") {
        return "bg-blue-50 text-blue-700";
    } else if ($status === "已購票") {
        return "bg-red-50 text-red-700";
    } else if ($status === "已舉行") {
        return "bg-slate-100 text-slate-500";
    }

    return "bg-slate-100 text-slate-500";
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>使用者中心｜活動導購與行程紀錄系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Microsoft JhengHei', 'ui-sans-serif', 'system-ui']
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">

<header class="sticky top-0 z-30 bg-white/80 backdrop-blur border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="activity_list.php" class="text-xl md:text-2xl font-bold tracking-tight">
            活動導購與行程紀錄系統
        </a>

        <nav class="flex items-center gap-3">
            <a href="activity_list.php" class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">首頁</a>
            <a href="../backend/my_schedule.php" class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">行事曆</a>
            <a href="profile.php" class="px-4 py-2 rounded-full bg-indigo-50 text-indigo-700 font-semibold">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <section class="grid lg:grid-cols-4 gap-8">

        <aside class="lg:col-span-1">
            <div class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-6">
                <div class="flex flex-col items-center text-center">
                    <div class="w-28 h-28 rounded-full bg-gradient-to-br from-indigo-200 to-blue-200 flex items-center justify-center text-5xl shadow-inner">
                        👤
                    </div>

                    <h2 class="mt-5 text-2xl font-bold"><?= htmlspecialchars($user_info_name ?: $user_name) ?></h2>
                    <p class="mt-1 text-sm text-slate-500 break-all"><?= htmlspecialchars($user_info_email) ?></p>
                </div>

                <nav class="mt-8 space-y-2">
                    <a href="profile.php?tab=reminder"
                       class="block rounded-2xl px-5 py-3 font-semibold transition <?= $tab === 'reminder' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-100' ?>">
                        提醒
                    </a>

                    <a href="profile.php?tab=info"
                       class="block rounded-2xl px-5 py-3 font-semibold transition <?= $tab === 'info' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-100' ?>">
                        個人資料
                    </a>

                    <a href="profile.php?tab=password"
                       class="block rounded-2xl px-5 py-3 font-semibold transition <?= $tab === 'password' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-600 hover:bg-slate-100' ?>">
                        修改密碼
                    </a>

                    <a href="../backend/logout.php"
                       class="block rounded-2xl px-5 py-3 font-semibold text-red-600 hover:bg-red-50 transition">
                        登出
                    </a>
                </nav>
            </div>
        </aside>

        <section class="lg:col-span-3">

            <?php if ($tab === "password"): ?>

                <div class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-8">
                    <h1 class="text-3xl font-extrabold mb-2">修改密碼</h1>
                    <p class="text-slate-500 mb-8">請輸入原密碼與新密碼。</p>
                    <?php if (isset($_GET["updated"])): ?>
                        <div class="mb-5 rounded-2xl bg-green-50 border border-green-100 px-4 py-3 text-green-700">
                            密碼修改成功
                        </div>
                    <?php endif; ?>
                    <?php if ($message !== ""): ?>
                        <div class="mb-5 rounded-2xl px-4 py-3
                            <?= ($message === "密碼修改成功")
                                ? "bg-green-50 border border-green-100 text-green-700"
                                : "bg-red-50 border border-red-100 text-red-700" ?>">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form class="max-w-xl space-y-5" action="profile.php?tab=password" method="post">
                        <input type="hidden" name="action" value="change_password">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-2">原密碼</label>
                            <input type="password" name="old_password" required
                                   class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-2">新密碼</label>
                            <input type="password" name="new_password" required
                                   class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-2">確認新密碼</label>
                            <input type="password" name="confirm_password" required
                                   class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <button type="submit"
                                class="rounded-2xl bg-indigo-600 text-white px-7 py-3 font-semibold hover:bg-indigo-700 transition">
                            確認修改
                        </button>
                    </form>
                </div>

            <?php elseif ($tab === "info"): ?>

                <div class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-8">
                    <h1 class="text-3xl font-extrabold mb-2">個人資料</h1>
                    <p class="text-slate-500 mb-8">可修改使用者名稱，Email 僅供顯示。</p>

                    <?php if (isset($_GET["updated"])): ?>
                        <div class="mb-5 rounded-2xl bg-green-50 border border-green-100 px-4 py-3 text-green-700">
                            個人資料已更新
                        </div>
                    <?php endif; ?>

                    <?php if ($message !== ""): ?>
                        <div class="mb-5 rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-red-700">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <form class="max-w-xl space-y-5" action="profile.php?tab=info" method="post">
                        <input type="hidden" name="action" value="update_profile">

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-2">使用者名稱</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($user_info_name) ?>" required
                                   class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-2">Email</label>
                            <input type="email" value="<?= htmlspecialchars($user_info_email) ?>" readonly
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-100 text-slate-500 px-4 py-3 outline-none">
                        </div>

                        <button type="submit"
                                class="rounded-2xl bg-indigo-600 text-white px-7 py-3 font-semibold hover:bg-indigo-700 transition">
                            儲存修改
                        </button>
                    </form>
                </div>

            <?php else: ?>

                <div class="flex items-end justify-between gap-6 mb-6">
                    <div>
                        <p class="text-indigo-600 font-semibold">Reminder</p>
                        <h1 class="mt-2 text-4xl font-extrabold tracking-tight">提醒列表</h1>
                        <p class="mt-3 text-slate-500">感興趣會建立售票時間前 12 小時提醒；已購票會建立活動前一天中午 12:00 的提醒。</p>
                    </div>
                </div>

                <?php if (count($reminders) === 0): ?>

                    <div class="rounded-[2rem] bg-white border border-slate-200 shadow-sm p-12 text-center text-slate-500">
                        目前沒有提醒。
                    </div>

                <?php else: ?>

                    <div class="space-y-4">
                        <?php foreach ($reminders as $reminder): ?>
                            <?php
                                $is_due = !$reminder["is_sent"] && strtotime($reminder["reminder_time"]) <= time();
                                $status_class = getStatusBadgeClass($reminder["display_status"]);
                            ?>

                            <div class="relative rounded-[1.5rem] bg-white border border-slate-200 shadow-sm hover:shadow-lg transition p-6">
                                <?php if ($is_due): ?>
                                    <div class="absolute -right-2 -top-2 w-5 h-5 rounded-full bg-red-500 ring-4 ring-red-100"></div>
                                <?php endif; ?>

                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-5">
                                    <div>
                                        <a href="activity_detail.php?id=<?= htmlspecialchars($reminder["activity_id"]) ?>"
                                           class="text-2xl font-bold hover:text-indigo-600 transition">
                                            <?= htmlspecialchars(getReminderText($reminder)) ?>
                                        </a>

                                        <div class="mt-4 flex flex-wrap gap-3 text-sm">
                                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600">
                                                活動：<?= date("Y/m/d H:i", strtotime($reminder["activity_time"])) ?>
                                            </span>

                                            <?php if (!empty($reminder["sales_time"])): ?>
                                                <span class="px-3 py-1 rounded-full bg-green-50 text-green-700">
                                                    售票：<?= date("Y/m/d H:i", strtotime($reminder["sales_time"])) ?>
                                                </span>
                                            <?php endif; ?>

                                            <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700">
                                                提醒：<?= date("Y/m/d H:i", strtotime($reminder["reminder_time"])) ?>
                                            </span>

                                            <span class="px-3 py-1 rounded-full <?= $status_class ?>">
                                                <?= htmlspecialchars($reminder["display_status"]) ?>
                                            </span>
                                        </div>

                                        <?php if (!empty($reminder["location"])): ?>
                                            <p class="mt-4 text-slate-500">📍 <?= htmlspecialchars($reminder["location"]) ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($is_due): ?>
                                        <form action="profile.php?tab=reminder" method="post">
                                            <input type="hidden" name="action" value="mark_sent">
                                            <input type="hidden" name="reminder_id" value="<?= htmlspecialchars($reminder["reminder_id"]) ?>">

                                            <button type="submit"
                                                    class="rounded-2xl bg-slate-900 text-white px-5 py-3 font-semibold hover:bg-slate-700 transition">
                                                標記已提醒
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        </section>

    </section>

</main>

</body>
</html>