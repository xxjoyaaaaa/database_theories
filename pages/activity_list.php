<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../backend/get_activities.php';
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動導購與行程紀錄系統</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: "Microsoft JhengHei", sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #333;
        }

        .navbar {
            background-color: #e0e0e0;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ccc;

            position: sticky; 
            top: 0;
            z-index: 100;
        }

        .navbar h1 {
            font-size: 24px;
            font-weight: normal;
        }

        .navbar h1 a {
            text-decoration: none;
            color: #333;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            font-size: 18px;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .main-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .title-area {
            display: flex;
            align-items: center;
            font-size: 24px;
            gap: 10px;
        }

        .star-icon {
            color: #fbd34d;
            font-size: 30px;
        }

        .search-container {
            position: relative;
        }

        .search-btn {
            background-color: #b0b0b0;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 5px;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            width: 250px;
            padding: 15px;
            border-radius: 8px;
            z-index: 10;
        }

        .dropdown-menu.show {
            display: block;
        }

        .filter-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }

        .filter-input {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .submit-filter-btn {
            width: 100%;
            background-color: #333;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .submit-filter-btn:hover {
            background-color: #555;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-card {
            background-color: #dcdcdc;
            border-radius: 10px;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .activity-card:hover {
            background-color: #d0d0d0;
        }

        .card-left {
            display: flex;
            align-items: baseline;
            gap: 30px;
        }

        .activity-name {
            font-size: 22px;
            font-weight: 500;
        }

        .activity-date {
            font-size: 16px;
        }

        .card-right {
            font-size: 12px;
            color: #555;
        }
    </style>
</head>

<body>

<nav class="navbar">
    <h1>
        <a href="activity_list.php">
            活動導購與行程紀錄系統
        </a>
    </h1>

    <div class="nav-links">
        <a href="../backend/my_schedule.php">行事曆</a>
        <a href="profile.php">使用者</a>
    </div>
</nav>

<main class="main-container">

    <div class="header-section">
        <div class="title-area">
            <span class="star-icon">★</span>
            <span>最新活動</span>
        </div>

        <div class="search-container">
            <button class="search-btn" id="searchBtn">
                搜尋 <span id="arrowIcon">▼</span>
            </button>

            <form class="dropdown-menu" id="dropdownMenu" action="activity_list.php" method="GET">

                <div class="filter-group">
                    <label for="date">日期</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="filter-input"
                        value="<?= htmlspecialchars($filter_date) ?>"
                    >
                </div>

                <div class="filter-group">
                    <label for="location">地點</label>
                    <select id="location" name="location" class="filter-input">
                        <option value="">全部地區</option>
                        <option value="台北小巨蛋" <?= $filter_location == '台北小巨蛋' ? 'selected' : '' ?>>台北小巨蛋</option>
                        <option value="台北世貿" <?= $filter_location == '台北世貿' ? 'selected' : '' ?>>台北世貿</option>
                        <option value="台灣師範大學" <?= $filter_location == '台灣師範大學' ? 'selected' : '' ?>>台灣師範大學</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="category">類別</label>
                    <select id="category" name="category" class="filter-input">
                        <option value="">所有類別</option>
                        <option value="C001" <?= $filter_category == 'C001' ? 'selected' : '' ?>>演唱會</option>
                        <option value="C002" <?= $filter_category == 'C002' ? 'selected' : '' ?>>展覽</option>
                        <option value="C003" <?= $filter_category == 'C003' ? 'selected' : '' ?>>講座</option>
                        <option value="C004" <?= $filter_category == 'C004' ? 'selected' : '' ?>>運動賽事</option>
                    </select>
                </div>

                <button type="submit" class="submit-filter-btn">套用篩選</button>
            </form>
        </div>
    </div>

    <div class="activity-list">
        <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $row): ?>
                <?php
                    $activity_date = date('Y/m/d', strtotime($row["activity_time"]));
                    $activity_id = htmlspecialchars($row["activity_id"]);
                ?>

                <div class="activity-card" onclick="location.href='activity_detail.php?id=<?= $activity_id ?>'">
                    <div class="card-left">
                        <span class="activity-name">
                            <?= htmlspecialchars($row["name"]) ?>
                        </span>

                        <span class="activity-date">
                            活動日期：<?= $activity_date ?>
                        </span>
                    </div>

                    <div class="card-right">
                        點選閱讀詳細資訊...
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; margin-top:20px; color:#666;">
                目前沒有符合條件的活動。
            </p>
        <?php endif; ?>
    </div>

</main>

<script>
    const searchBtn = document.getElementById('searchBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const arrowIcon = document.getElementById('arrowIcon');

    searchBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownMenu.classList.toggle('show');
        arrowIcon.textContent = dropdownMenu.classList.contains('show') ? '▲' : '▼';
    });

    dropdownMenu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    document.addEventListener('click', () => {
        if (dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
            arrowIcon.textContent = '▼';
        }
    });
</script>

</body>
</html>