<?php
session_start();
date_default_timezone_set('Asia/Taipei');
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

$status_class = 'text-slate-500 bg-slate-100';

if ($display_status === '感興趣') {
    $status_class = 'text-blue-700 bg-blue-50';
} else if ($display_status === '已購票') {
    $status_class = 'text-red-700 bg-red-50';
} else if ($display_status === '已舉行') {
    $status_class = 'text-gray-600 bg-gray-100';
}

$auto_reminder_text = '';

if ($current_status === '感興趣') {
    if (!empty($activity['sales_time'])) {
        $sales_datetime = new DateTime($activity['sales_time']);
        $sales_datetime->modify('-12 hours');
        $auto_reminder_text = '系統會在售票時間前 12 小時提醒你：' . $sales_datetime->format('Y/m/d H:i');
    } else {
        $auto_reminder_text = '此活動目前尚未設定售票時間，因此不會建立售票提醒。';
    }
} else if ($current_status === '已購票') {
    $activity_datetime = new DateTime($activity['activity_time']);
    $activity_datetime->modify('-1 day');
    $activity_datetime->setTime(12, 0, 0);
    $auto_reminder_text = '系統會在活動前一天中午提醒你：' . $activity_datetime->format('Y/m/d H:i');
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($activity['name']) ?> - 活動詳細</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">

<header class="bg-white/90 backdrop-blur border-b border-slate-200 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="activity_list.php" class="text-2xl font-bold">
            活動導購與行程紀錄系統
        </a>

        <nav class="flex items-center gap-6 text-sm font-medium">
            <a href="activity_list.php" class="hover:text-blue-600">首頁</a>
            <a href="../backend/my_schedule.php" class="hover:text-blue-600">行事曆</a>
            <a href="profile.php" class="hover:text-blue-600">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-6xl mx-auto px-6 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <section class="lg:col-span-2">
            <div class="h-80 rounded-3xl bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center shadow-sm border border-slate-200 mb-8">
                <span class="text-4xl font-bold text-slate-500">活動照片</span>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <p class="text-sm text-slate-500 mb-2">Activity Detail</p>
                        <h1 class="text-4xl font-bold">
                            <?= htmlspecialchars($activity['name']) ?>
                        </h1>
                    </div>

                    <?php if ($display_status): ?>
                        <span class="px-4 py-2 rounded-full text-sm font-bold <?= $status_class ?>">
                            <?= htmlspecialchars($display_status) ?>
                        </span>
                    <?php else: ?>
                        <span class="px-4 py-2 rounded-full text-sm font-bold text-slate-600 bg-slate-100">
                            尚未加入
                        </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-slate-700">
                    <div class="p-4 rounded-2xl bg-slate-50">
                        <p class="text-sm text-slate-500 mb-1">活動時間</p>
                        <p class="font-semibold">
                            <?= date('Y/m/d H:i', strtotime($activity['activity_time'])) ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50">
                        <p class="text-sm text-slate-500 mb-1">售票時間</p>
                        <p class="font-semibold">
                            <?php if (!empty($activity['sales_time'])): ?>
                                <?= date('Y/m/d H:i', strtotime($activity['sales_time'])) ?>
                            <?php else: ?>
                                尚未公布
                            <?php endif; ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50">
                        <p class="text-sm text-slate-500 mb-1">活動地點</p>
                        <p class="font-semibold">
                            <?= htmlspecialchars($activity['location']) ?>
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50">
                        <p class="text-sm text-slate-500 mb-1">售票狀態</p>
                        <p class="font-semibold">
                            <?= htmlspecialchars($activity['cache_status'] ?? '尚無資料') ?>
                        </p>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-xl font-bold mb-3">活動簡述</h2>
                    <p class="text-slate-600 leading-8">
                        目前資料庫暫無此欄位，先用這段文字作為活動介紹。之後可以新增 description 欄位，放入更完整的活動內容。
                    </p>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-xl font-bold mb-4">行程狀態</h2>

                <?php if (!$is_finished): ?>
                    <form action="../backend/add_schedule.php" method="post" class="mb-3">
                        <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                        <input type="hidden" name="status" value="感興趣">

                        <button type="submit"
                                class="w-full px-5 py-3 rounded-2xl font-bold transition
                                <?= ($current_status === '感興趣')
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-blue-50 text-blue-700 hover:bg-blue-100' ?>">
                            <?= ($current_status === '感興趣') ? '取消感興趣' : '感興趣' ?>
                        </button>
                    </form>

                    <form action="../backend/add_schedule.php" method="post" class="mb-3">
                        <input type="hidden" name="activity_id" value="<?= htmlspecialchars($activity_id) ?>">
                        <input type="hidden" name="status" value="已購票">

                        <button type="submit"
                                class="w-full px-5 py-3 rounded-2xl font-bold transition
                                <?= ($current_status === '已購票')
                                    ? 'bg-red-600 text-white hover:bg-red-700'
                                    : 'bg-red-50 text-red-700 hover:bg-red-100' ?>">
                            <?= ($current_status === '已購票') ? '取消已購票' : '已購票' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="p-4 rounded-2xl bg-gray-100 text-gray-600 font-semibold">
                        此活動已舉行，無法再修改狀態。
                    </div>
                <?php endif; ?>

                <?php if (!empty($activity['external_url']) && !$is_finished): ?>
                    <a href="<?= htmlspecialchars($activity['external_url']) ?>"
                       target="_blank"
                       class="block w-full text-center px-5 py-3 rounded-2xl bg-slate-900 text-white font-bold hover:bg-slate-700 transition">
                        前往購票
                    </a>
                <?php else: ?>
                    <button disabled
                            class="w-full px-5 py-3 rounded-2xl bg-slate-100 text-slate-400 font-bold cursor-not-allowed">
                        <?= $is_finished ? '活動已結束' : '無購票連結' ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h2 class="text-xl font-bold mb-3">提醒設定</h2>

                <?php if ($is_finished): ?>
                    <p class="text-slate-500 leading-7">
                        此活動已舉行，不再建立提醒。
                    </p>
                <?php else if ($current_status): ?>
                    <p class="text-slate-600 leading-7">
                        <?= htmlspecialchars($auto_reminder_text) ?>
                    </p>
                <?php else: ?>
                    <p class="text-slate-500 leading-7">
                        加入「感興趣」後，系統會建立售票時間前 12 小時提醒；加入「已購票」後，系統會建立活動前一天中午提醒。
                    </p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</main>

</body>
</html>