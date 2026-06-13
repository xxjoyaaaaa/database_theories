<?php
require_once '../backend/db.php';

$activity_id = isset($_GET['id']) ? $_GET['id'] : '';

if ($activity_id == '') {
    header("Location: activity_list.php");
    exit;
}

$sql = "SELECT * FROM ACTIVITY WHERE activity_id = '" . $conn->real_escape_string($activity_id) . "'";
$result = $conn->query($sql);
$activity = $result->fetch_assoc();

if (!$activity) {
    echo "<script>alert('找不到此活動！'); window.location.href='activity_list.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activity['name']) ?> - 活動詳細</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Microsoft JhengHei", sans-serif; }
        body { background-color: #ffffff; color: #333; }

        .navbar {
            background-color: #dcdcdc;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 22px; font-weight: normal; }
        .nav-links { display: flex; gap: 20px; font-size: 16px; }
        .nav-links a { text-decoration: none; color: #333; cursor: pointer; }

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
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .heart-icon {
            font-size: 28px;
            color: #999;
            cursor: pointer;
            transition: color 0.2s;
        }
        .heart-icon:hover { color: #ff5e5e; }

        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            background-color: #a0a0a0;
            color: #222;
            transition: background-color 0.2s;
        }
        .btn:hover { background-color: #888; }
        
        .btn-schedule { background-color: #b5b5b5; }
        .btn-link { background-color: #9e9e9e; }
    </style>
</head>
<body>

    <nav class="navbar">
        <h1>活動導購與行程紀錄系統</h1>
        <div class="nav-links">
            <a>行事曆</a>
            <a>使用者</a>
        </div>
    </nav>

    <main class="main-container">
        
        <div class="image-placeholder">
            活動照片
        </div>

        <div class="content-section">
            
            <div class="info-left">
                <h2><?= htmlspecialchars($activity['name']) ?></h2>
                <p>活動日期：<?= date('Y/m/d', strtotime($activity['activity_time'])) ?></p>
                <p>活動地點：<?= htmlspecialchars($activity['location']) ?></p>
                <p>活動簡述：目前資料庫暫無此欄位，先用這段假字撐撐場面！之後可以補上詳細的活動介紹。</p>
            </div>

            <div class="action-right">
                <span class="heart-icon">♥</span>
                
                <button class="btn btn-schedule">加入已購買</button>
                
                <?php if (!empty($activity['external_url'])): ?>
                    <a href="<?= htmlspecialchars($activity['external_url']) ?>" target="_blank" class="btn btn-link">導購連結</a>
                <?php else: ?>
                    <button class="btn" style="background-color: #eee; color: #aaa; cursor: not-allowed;" disabled>無導購連結</button>
                <?php endif; ?>
            </div>

        </div>

    </main>

</body>
</html>
