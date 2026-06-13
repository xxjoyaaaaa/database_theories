<?php
session_start();
require_once 'db.php';

/*
|--------------------------------------------------------------------------
| 取得資料庫連線
|--------------------------------------------------------------------------
| 這裡支援 db.php 裡面連線變數叫 $conn 或 $mysqli
*/

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die('資料庫連線失敗，請檢查 db.php 是否有 $conn 或 $mysqli');
}

/*
|--------------------------------------------------------------------------
| 取得使用者
|--------------------------------------------------------------------------
| 正式登入完成後，應該使用 $_SESSION['user_id']
| 目前如果登入還沒完全整合，先用 U001 測試
*/

$user_id = $_SESSION['user_id'] ?? 'U001';

/*
|--------------------------------------------------------------------------
| 取得年份與月份
|--------------------------------------------------------------------------
| 網址範例：
| my_schedule.php?year=2026&month=7
*/

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

/*
|--------------------------------------------------------------------------
| 計算月曆需要的資訊
|--------------------------------------------------------------------------
*/

$first_day = sprintf('%04d-%02d-01', $year, $month);
$days_in_month = intval(date('t', strtotime($first_day)));
$start_weekday = intval(date('w', strtotime($first_day))); 
// 0 = 星期日, 1 = 星期一, ..., 6 = 星期六

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

/*
|--------------------------------------------------------------------------
| 查詢這個使用者當月的行程
|--------------------------------------------------------------------------
| 注意：
| 行事曆只查詢與顯示資料
| 不在這裡刪除
| 不在這裡更新狀態
| 狀態更新留在 activity_detail.php
*/

$start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);

if ($month == 12) {
    $end_date = sprintf('%04d-01-01 00:00:00', $year + 1);
} else {
    $end_date = sprintf('%04d-%02d-01 00:00:00', $year, $month + 1);
}

$sql = "
    SELECT
        s.schedule_id,
        s.status,

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

/*
|--------------------------------------------------------------------------
| 狀態顏色 class
|--------------------------------------------------------------------------
*/

function getStatusClass($status) {
    if ($status === '感興趣') {
        return 'status-interested';
    } else if ($status === '已購票') {
        return 'status-paid';
    } else if ($status === '預定參加') {
        return 'status-planned';
    } else if ($status === '已舉行') {
        return 'status-finished';
    }

    return '';
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>行事曆</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, "Microsoft JhengHei", sans-serif;
            background-color: #ffffff;
        }

        .url-bar {
            width: 32%;
            height: 24px;
            border: 2px solid #bfbfbf;
            border-radius: 20px;
            margin: 18px auto 0 auto;
            text-align: center;
            line-height: 24px;
            font-size: 14px;
            color: #333;
        }

        .top-bar {
            width: 90%;
            margin: 8px auto 0 auto;
            background-color: #d9d9d9;
            border-top: 2px solid #999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            box-sizing: border-box;
        }

        .system-title {
            font-size: 18px;
            font-weight: bold;
        }

        .top-nav a {
            color: black;
            text-decoration: none;
            margin-left: 20px;
            font-size: 15px;
        }

        .top-nav a:hover {
            text-decoration: underline;
        }

        .calendar-wrapper {
            width: 82%;
            margin: 25px auto;
        }

        .calendar-wrapper h2 {
            margin: 0 0 15px 0;
            font-size: 22px;
        }

        .month-control {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 35px;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .month-control a {
            color: black;
            text-decoration: none;
            border: 1px solid #999;
            padding: 5px 12px;
            border-radius: 5px;
            background-color: #f2f2f2;
        }

        .month-control a:hover {
            background-color: #e0e0e0;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .calendar-table th {
            border: 2px solid #333;
            height: 35px;
            background-color: #eeeeee;
            font-weight: normal;
        }

        .calendar-table td {
            border: 2px solid #333;
            height: 95px;
            vertical-align: top;
            padding: 5px;
            box-sizing: border-box;
        }

        .date-number {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .event-link {
            display: block;
            font-size: 13px;
            text-decoration: none;
            margin-top: 2px;
            word-break: break-all;
        }

        .event-link:hover {
            text-decoration: underline;
        }

        .status-interested {
            color: blue;
        }

        .status-paid {
            color: red;
        }

        .status-planned {
            color: orange;
        }

        .status-finished {
            color: gray;
        }

        .legend {
            margin-top: 15px;
            font-size: 14px;
        }

        .legend span {
            margin-right: 20px;
        }

        .empty-cell {
            background-color: #fafafa;
        }
    </style>
</head>

<body>

<div class="url-bar">網址</div>

<header class="top-bar">
    <div class="system-title">活動導購與行程紀錄系統</div>

    <nav class="top-nav">
        <a href="../index.php">首頁</a>
        <a href="my_schedule.php">行事曆</a>
        <a href="../pages/profile.php">使用者</a>
    </nav>
</header>

<main class="calendar-wrapper">
    <h2>行事曆</h2>

    <div class="month-control">
        <a href="my_schedule.php?year=<?= $prev_year ?>&month=<?= $prev_month ?>">上一月</a>

        <strong><?= htmlspecialchars($year) ?> 年 <?= htmlspecialchars($month) ?> 月</strong>

        <a href="my_schedule.php?year=<?= $next_year ?>&month=<?= $next_month ?>">下一月</a>
    </div>

    <table class="calendar-table">
        <thead>
            <tr>
                <th>日</th>
                <th>一</th>
                <th>二</th>
                <th>三</th>
                <th>四</th>
                <th>五</th>
                <th>六</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $day = 1;

            for ($week = 0; $week < 6; $week++) {
                echo "<tr>";

                for ($weekday = 0; $weekday < 7; $weekday++) {
                    if ($week === 0 && $weekday < $start_weekday) {
                        echo "<td class='empty-cell'></td>";
                    } else if ($day <= $days_in_month) {
                        echo "<td>";
                        echo "<div class='date-number'>" . $day . "</div>";

                        if (isset($events_by_day[$day])) {
                            foreach ($events_by_day[$day] as $event) {
                                $activity_id = htmlspecialchars($event['activity_id']);
                                $activity_name = htmlspecialchars($event['activity_name']);
                                $status = htmlspecialchars($event['status']);
                                $class = getStatusClass($event['status']);

                                echo "<a class='event-link {$class}' href='../pages/activity_detail.php?id={$activity_id}' title='{$status}'>";
                                echo $activity_name;
                                echo "</a>";
                            }
                        }

                        echo "</td>";

                        $day++;
                    } else {
                        echo "<td class='empty-cell'></td>";
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

    <div class="legend">
        <span class="status-interested">感興趣</span>
        <span class="status-paid">已購票</span>
        <span class="status-planned">預定參加</span>
        <span class="status-finished">已舉行</span>
    </div>
</main>

</body>
</html>