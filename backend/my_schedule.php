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

$start_ts = strtotime($start_date);
$end_ts = strtotime($end_date);

$sql = "
    SELECT
        s.schedule_id,
        s.status,

        a.activity_id,
        a.name AS activity_name,
        a.activity_time,
        a.sales_time,
        a.location,
        a.cache_status,

        c.category_name
    FROM SCHEDULE s
    JOIN ACTIVITY a
        ON s.activity_id = a.activity_id
    LEFT JOIN CATEGORY c
        ON a.category_id = c.category_id
    WHERE s.user_id = ?
      AND (
            (a.activity_time >= ? AND a.activity_time < ?)
         OR (a.sales_time IS NOT NULL AND a.sales_time >= ? AND a.sales_time < ?)
      )
    ORDER BY a.activity_time ASC
";

$stmt = $db->prepare($sql);

if (!$stmt) {
    die('SQL prepare 失敗：' . $db->error);
}

$stmt->bind_param("sssss", $user_id, $start_date, $end_date, $start_date, $end_date);

if (!$stmt->execute()) {
    die('SQL execute 失敗：' . $stmt->error);
}

$result = $stmt->get_result();

$events_by_day = [];

while ($row = $result->fetch_assoc()) {
    /*
    活動日：
    感興趣、已購票都顯示活動日。
    未過期活動日紅色，已過期活動日灰色。
    */
    if (!empty($row['activity_time'])) {
        $activity_ts = strtotime($row['activity_time']);

        if ($activity_ts >= $start_ts && $activity_ts < $end_ts) {
            $activity_day = intval(date('j', $activity_ts));

            if (!isset($events_by_day[$activity_day])) {
                $events_by_day[$activity_day] = [];
            }

            $activity_status = '活動日';

            if ($activity_ts < time()) {
                $activity_status = '已舉行';
            }

            $events_by_day[$activity_day][] = [
                'activity_id' => $row['activity_id'],
                'label' => $row['activity_name'],
                'status' => $activity_status,
                'type' => 'activity'
            ];
        }
    }

    /*
    售票日：
    只有「感興趣」才顯示售票日。
    已購票不顯示售票日。
    售票時間未過：綠色。
    售票時間已過：灰色。
    */
    if ($row['status'] === '感興趣' && !empty($row['sales_time'])) {
        $sales_ts = strtotime($row['sales_time']);

        if ($sales_ts >= $start_ts && $sales_ts < $end_ts) {
            $sales_day = intval(date('j', $sales_ts));

            if (!isset($events_by_day[$sales_day])) {
                $events_by_day[$sales_day] = [];
            }

            $sales_status = '售票日';

            if ($sales_ts < time()) {
                $sales_status = '售票日已過';
            }

            $events_by_day[$sales_day][] = [
                'activity_id' => $row['activity_id'],
                'label' => $row['activity_name'] . '售票日',
                'status' => $sales_status,
                'type' => 'sales'
            ];
        }
    }
}

$stmt->close();

function getEventClass($status) {
    if ($status === '活動日') {
        return 'text-red-600 bg-red-50 border-red-100';
    } else if ($status === '售票日') {
        return 'text-green-700 bg-green-50 border-green-100';
    } else if ($status === '售票日已過') {
        return 'text-gray-500 bg-gray-100 border-gray-200';
    } else if ($status === '已舉行') {
        return 'text-gray-500 bg-gray-100 border-gray-200';
    }

    return 'text-gray-700 bg-gray-50 border-gray-200';
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>行事曆</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-50 text-slate-900">

<header class="bg-white/90 backdrop-blur border-b border-slate-200 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="../pages/activity_list.php" class="text-2xl font-bold tracking-tight">
            活動導購與行程紀錄系統
        </a>

        <nav class="flex items-center gap-6 text-sm font-medium">
            <a href="../pages/activity_list.php" class="hover:text-blue-600">首頁</a>
            <a href="my_schedule.php" class="text-blue-600">行事曆</a>
            <a href="../pages/profile.php" class="hover:text-blue-600">使用者</a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">
    <div class="flex items-end justify-between mb-8">
        <div>
            <p class="text-sm text-slate-500 mb-2">My Calendar</p>
            <h1 class="text-4xl font-bold">行事曆</h1>
        </div>

        <div class="flex items-center gap-3">
            <a href="my_schedule.php?year=<?= $prev_year ?>&month=<?= $prev_month ?>"
               class="px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm hover:bg-slate-100">
                上一月
            </a>

            <div class="px-5 py-2 rounded-full bg-slate-900 text-white font-semibold">
                <?= htmlspecialchars($year) ?> 年 <?= htmlspecialchars($month) ?> 月
            </div>

            <a href="my_schedule.php?year=<?= $next_year ?>&month=<?= $next_month ?>"
               class="px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm hover:bg-slate-100">
                下一月
            </a>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full table-fixed">
            <thead>
                <tr class="bg-slate-100 text-slate-600">
                    <th class="py-3 border border-slate-200">日</th>
                    <th class="py-3 border border-slate-200">一</th>
                    <th class="py-3 border border-slate-200">二</th>
                    <th class="py-3 border border-slate-200">三</th>
                    <th class="py-3 border border-slate-200">四</th>
                    <th class="py-3 border border-slate-200">五</th>
                    <th class="py-3 border border-slate-200">六</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $day = 1;

                for ($week = 0; $week < 6; $week++) {
                    echo "<tr>";

                    for ($weekday = 0; $weekday < 7; $weekday++) {
                        if ($week === 0 && $weekday < $start_weekday) {
                            echo "<td class='h-32 align-top border border-slate-200 bg-slate-50'></td>";
                        } else if ($day <= $days_in_month) {
                            echo "<td class='h-32 align-top border border-slate-200 p-3 bg-white'>";
                            echo "<div class='text-sm font-semibold text-slate-500 mb-2'>" . $day . "</div>";

                            if (isset($events_by_day[$day])) {
                                foreach ($events_by_day[$day] as $event) {
                                    $activity_id = htmlspecialchars($event['activity_id']);
                                    $label = htmlspecialchars($event['label']);
                                    $status = htmlspecialchars($event['status']);
                                    $class = getEventClass($event['status']);

                                    echo "<a class='block text-xs font-semibold px-2 py-1 rounded-lg border mb-1 {$class}' href='../pages/activity_detail.php?id={$activity_id}' title='{$status}'>";
                                    echo $label;
                                    echo "</a>";
                                }
                            }

                            echo "</td>";

                            $day++;
                        } else {
                            echo "<td class='h-32 align-top border border-slate-200 bg-slate-50'></td>";
                        }
                    }

                    echo "</tr>";

                    if ($day > $days_in_month) {
                        break;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center gap-4 mt-6 text-sm">
        <span class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            活動日
        </span>

        <span class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            活動售票日
        </span>

        <span class="inline-flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
            已舉行 / 售票時間已過
        </span>
    </div>
</main>

</body>
</html>