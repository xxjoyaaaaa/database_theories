<?php
session_start();
require_once 'db.php';

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die('資料庫連線失敗，請檢查 db.php 是否有 $conn 或 $mysqli');
}

$user_id = $_SESSION['user_id'] ?? 'U001';

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));

if ($month < 1) {
    $month = 12;
    $year--;
}

if ($month > 12) {
    $month = 1;
    $year++;
}

$first_day = sprintf('%04d-%02d-01', $year, $month);
$days_in_month = intval(date('t', strtotime($first_day)));
$start_weekday = intval(date('w', strtotime($first_day)));

$prev_year = $year;
$prev_month = $month - 1;

if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_year = $year;
$next_month = $month + 1;

if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);

if ($month == 12) {
    $end_date = sprintf('%04d-01-01 00:00:00', $year + 1);
} else {
    $end_date = sprintf('%04d-%02d-01 00:00:00', $year, $month + 1);
}

$sql = "
    SELECT
        s.schedule_id,

        CASE
            WHEN a.activity_time < NOW() THEN '已舉行'
            ELSE s.status
        END AS status,

        a.activity_id,
        a.name AS activity_name,
        a.activity_time,
        DAY(a.activity_time) AS activity_day,
        a.location,
        a.cache_status,

        c.category_name
    FROM SCHEDULE s
    JOIN ACTIVITY a
        ON s.activity_id = a.activity_id
    LEFT JOIN CATEGORY c
        ON a.category_id = c.category_id
    WHERE s.user_id = ?
      AND a.activity_time >= ?
      AND a.activity_time < ?
    ORDER BY a.activity_time ASC
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die('SQL prepare 失敗：' . $db->error);
}

$stmt->bind_param("sss", $user_id, $start_date, $end_date);

if (!$stmt->execute()) {
    die('SQL execute 失敗：' . $stmt->error);
}

$result = $stmt->get_result();

$events_by_day = [];

while ($row = $result->fetch_assoc()) {
    $day = intval($row['activity_day']);

    if (!isset($events_by_day[$day])) {
        $events_by_day[$day] = [];
    }

    $events_by_day[$day][] = $row;
}

$stmt->close();

function getStatusClass($status) {
    if ($status === '感興趣') {
        return 'bg-blue-50 text-blue-700 border-blue-100';
    } else if ($status === '已購票') {
        return 'bg-red-50 text-red-700 border-red-100';
    } else if ($status === '已舉行') {
        return 'bg-slate-100 text-slate-500 border-slate-200';
    }

    return 'bg-slate-50 text-slate-500 border-slate-100';
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>行事曆｜活動導購與行程紀錄系統</title>
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
        <a href="../pages/activity_list.php" class="text-xl md:text-2xl font-bold tracking-tight">
            活動導購與行程紀錄系統
        </a>

        <nav class="flex items-center gap-3">
            <a href="../pages/activity_list.php" class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">首頁</a>
            <a href="my_schedule.php" class="px-4 py-2 rounded-full bg-indigo-50 text-indigo-700 font-semibold">行事曆</a>
            <a href="../pages/profile.php" class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <section class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-8">
        <div>
            <p class="text-indigo-600 font-semibold">My Schedule</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight">我的行事曆</h1>
            <p class="mt-3 text-slate-500">查看你感興趣、已購票與已舉行的活動。</p>
        </div>

        <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl p-2 shadow-sm">
            <a href="my_schedule.php?year=<?= $prev_year ?>&month=<?= $prev_month ?>"
               class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 transition">
                上一月
            </a>

            <strong class="px-5 py-2 rounded-xl bg-indigo-50 text-indigo-700">
                <?= htmlspecialchars($year) ?> 年 <?= htmlspecialchars($month) ?> 月
            </strong>

            <a href="my_schedule.php?year=<?= $next_year ?>&month=<?= $next_month ?>"
               class="px-4 py-2 rounded-xl text-slate-600 hover:bg-slate-100 transition">
                下一月
            </a>
        </div>
    </section>

    <section class="rounded-[2rem] bg-white border border-slate-200 shadow-sm overflow-hidden">
        <div class="grid grid-cols-7 bg-slate-900 text-white text-center font-semibold">
            <div class="py-4">日</div>
            <div class="py-4">一</div>
            <div class="py-4">二</div>
            <div class="py-4">三</div>
            <div class="py-4">四</div>
            <div class="py-4">五</div>
            <div class="py-4">六</div>
        </div>

        <div class="grid grid-cols-7">
            <?php
            $day = 1;

            for ($week = 0; $week < 6; $week++) {
                for ($weekday = 0; $weekday < 7; $weekday++) {
                    if ($week === 0 && $weekday < $start_weekday) {
                        echo "<div class='min-h-32 border-r border-b border-slate-100 bg-slate-50'></div>";
                    } else if ($day <= $days_in_month) {
                        echo "<div class='min-h-32 border-r border-b border-slate-100 p-3 bg-white hover:bg-slate-50 transition'>";
                        echo "<div class='text-sm font-bold text-slate-700 mb-2'>" . $day . "</div>";

                        if (isset($events_by_day[$day])) {
                            foreach ($events_by_day[$day] as $event) {
                                $activity_id = htmlspecialchars($event['activity_id']);
                                $activity_name = htmlspecialchars($event['activity_name']);
                                $status = htmlspecialchars($event['status']);
                                $class = getStatusClass($event['status']);

                                echo "<a class='block rounded-xl border px-3 py-2 mb-2 text-sm font-semibold {$class} hover:shadow-sm transition' href='../pages/activity_detail.php?id={$activity_id}' title='{$status}'>";
                                echo $activity_name;
                                echo "</a>";
                            }
                        }

                        echo "</div>";
                        $day++;
                    } else {
                        echo "<div class='min-h-32 border-r border-b border-slate-100 bg-slate-50'></div>";
                    }
                }

                if ($day > $days_in_month) {
                    break;
                }
            }
            ?>
        </div>
    </section>

    <div class="mt-6 flex flex-wrap gap-3">
        <span class="inline-flex items-center px-4 py-2 rounded-full bg-blue-50 text-blue-700 font-semibold">感興趣</span>
        <span class="inline-flex items-center px-4 py-2 rounded-full bg-red-50 text-red-700 font-semibold">已購票</span>
        <span class="inline-flex items-center px-4 py-2 rounded-full bg-slate-100 text-slate-500 font-semibold">已舉行</span>
    </div>

</main>

</body>
</html>
