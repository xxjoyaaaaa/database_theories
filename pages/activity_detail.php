<?php
session_start();
require_once '../backend/db.php';

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

$is_finished = strtotime($activity['activity_time']) < time();

$display_status = $current_status;

if ($is_finished) {
    $display_status = '已舉行';
}

$status_badge_class = 'bg-slate-100 text-slate-600';

if ($display_status === "感興趣") {
    $status_badge_class = "bg-blue-50 text-blue-700";
} else if ($display_status === "已購票") {
    $status_badge_class = "bg-red-50 text-red-700";
} else if ($display_status === "已舉行") {
    $status_badge_class = "bg-slate-100 text-slate-500";
}

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
    <title><?= htmlspecialchars($activity['name']) ?>｜活動詳細</title>
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
            <a href="profile.php" class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <a href="activity_list.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-indigo-600 transition mb-6">
        <span>←</span>
        回活動列表
    </a>

    <section class="grid lg:grid-cols-3 gap-8">

        <div class="lg:col-span-2">
            <div class="h-72 md:h-96 rounded-[2rem] bg-gradient-to-br from-indigo-100 via-blue-100 to-slate-200 flex items-center justify-center shadow-sm border border-white">
                <div class="text-center">
                    <div class="text-6xl mb-4">🎫</div>
                    <p class="text-slate-500 font-medium">活動照片</p>
                </div>
            </div>

            <div class="mt-8 rounded-[2rem] bg-white border border-slate-200 shadow-sm p-7 md:p-8">
                <div class="flex flex-wrap items-center gap-3 mb-5">
                    <span class="inline-flex px-4 py-1.5 rounded-full text-sm font-semibold <?= $status_badge_class ?>">
                        <?= $display_status ? htmlspecialchars($display_status) : '尚未加入行事曆' ?>
                    </span>

                    <?php if (!empty($activity['cache_status'])): ?>
                        <span class="inline-flex px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm font-semibold">
                            <?= htmlspecialchars($activity['cache_status']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">
                    <?= htmlspecialchars($activity['name']) ?>
                </h1>

                <div class="mt-7 grid md:grid-cols-2 gap-4">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                        <p class="text-sm text-slate-500">活動日期</p>
                        <p class="mt-1 text-lg font-semibold"><?= date('Y/m/d H:i', strtotime($activity['activity_time'])) ?></p>
                    </div>

                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                        <p class="text-sm text-slate-500">活動地點</p>
                        <p class="mt-1 text-lg font-semibold"><?= htmlspecialchars($activity['location']) ?></p>
                    </div>

                    <?php if (!empty($activity['source_platform'])): ?>
                        <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                            <p class="text-sm text-slate-500">來源平台</p>
                            <p class="mt-1 text-lg font-semibold"><?= htmlspecialchars($activity['source_platform']) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-5">
                        <p class="text-sm text-slate-500">提醒設定</p>
                        <p class="mt-1 text-lg font-semibold">
                            <?php if ($is_finished): ?>
                                活動已舉行，不再提醒
                            <?php elseif ($current_status): ?>
                                <?= htmlspecialchars($auto_reminder_time) ?>
                            <?php else: ?>
                                加入行事曆後自動建立
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-3">活動簡述</h2>
                    <p class="text-slate-600 leading-8">
                        目前資料庫暫無活動介紹欄位，這裡先顯示活動基本資訊。之後若資料表增加 description 欄位，可以在此呈現詳細的活動內容、注意事項與購票說明。
                    </p>
                </div>
            </div>
        </div>

        <aside class="lg:col-span-1">
            <div class="sticky top-28 rounded-[2rem] bg-white border border-slate-200 shadow-sm p-6">
                <h2 class="text-2xl font-bold">行程操作</h2>

                <div class="mt-5 space-y-3">

                    <?php if (!$is_finished): ?>

                        <form action="../backend/add_schedule.php" method="post">
                            <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                            <input type="hidden" name="status" value="感興趣">

                            <button type="submit"
                                    class="w-full rounded-2xl px-5 py-3.5 font-semibold border transition
                                    <?= ($current_status === '感興趣') ? 'bg-blue-600 text-white border-blue-600 hover:bg-blue-700' : 'bg-blue-50 text-blue-700 border-blue-100 hover:bg-blue-100' ?>">
                                <?= ($current_status === '感興趣') ? '♥ 取消感興趣' : '♡ 加入感興趣' ?>
                            </button>
                        </form>

                        <form action="../backend/add_schedule.php" method="post">
                            <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                            <input type="hidden" name="status" value="已購票">

                            <button type="submit"
                                    class="w-full rounded-2xl px-5 py-3.5 font-semibold border transition
                                    <?= ($current_status === '已購票') ? 'bg-red-600 text-white border-red-600 hover:bg-red-700' : 'bg-red-50 text-red-700 border-red-100 hover:bg-red-100' ?>">
                                <?= ($current_status === '已購票') ? '取消已購買' : '加入已購買' ?>
                            </button>
                        </form>

                    <?php else: ?>

                        <div class="rounded-2xl bg-slate-100 text-slate-500 px-5 py-4 font-semibold">
                            此活動已舉行，無法再修改狀態。
                        </div>

                    <?php endif; ?>

                    <?php if (!empty($activity['external_url']) && !$is_finished): ?>
                        <a href="<?= htmlspecialchars($activity['external_url']) ?>"
                           target="_blank"
                           class="block w-full text-center rounded-2xl bg-slate-900 text-white px-5 py-3.5 font-semibold hover:bg-slate-700 transition">
                            前往導購連結
                        </a>
                    <?php else: ?>
                        <button disabled
                                class="w-full rounded-2xl bg-slate-100 text-slate-400 px-5 py-3.5 font-semibold cursor-not-allowed">
                            <?= $is_finished ? '活動已結束' : '無導購連結' ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="mt-6 rounded-2xl bg-indigo-50 border border-indigo-100 p-5">
                    <p class="text-sm font-semibold text-indigo-700">提醒規則</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        加入「感興趣」或「已購票」後，系統會自動在活動前一天中午 12:00 建立提醒。
                    </p>
                </div>
            </div>
        </aside>

    </section>

</main>

</body>
</html>
