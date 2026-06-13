<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: backend/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動導購與行程紀錄系統</title>
    <style>
        /* 基本重置與全域設定 */
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

        /* 導覽列 */
        .navbar {
            background-color: #e0e0e0;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #ccc;
        }
        .navbar h1 {
            font-size: 24px;
            font-weight: normal;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            font-size: 18px;
        }
        .nav-links a {
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }

        /* 主要內容區 */
        .main-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* 標題與搜尋按鈕區塊 */
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

        /* 搜尋下拉選單容器 */
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
        
        /* 表單下拉選單樣式升級 */
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

        /* 活動卡片列表 */
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
        <h1>活動導購與行程紀錄系統</h1>
        <div class="nav-links">
            <a href="#">行事曆</a>
            <a href="pages/profile.php">使用者</a>
            <a href="backend/logout.php">登出</a>
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
                
                <form class="dropdown-menu" id="dropdownMenu" action="index.php" method="GET">
                    
                    <div class="filter-group">
                        <label for="date">日期</label>
                        <input type="date" id="date" name="date" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label for="location">地點</label>
                        <select id="location" name="location" class="filter-input">
                            <option value="">全部地區</option>
                            <option value="台北">台北</option>
                            <option value="高雄">高雄</option>
                            <option value="屏東">屏東</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="category">類別</label>
                        <select id="category" name="category" class="filter-input">
                            <option value="">所有類別</option>
                            <option value="concert">演唱會</option>
                            <option value="exhibition">展覽</option>
                            <option value="school">校園活動</option>
                        </select>
                    </div>

                    <button type="submit" class="submit-filter-btn">套用篩選</button>
                </form>
            </div>
        </div>

        <div class="activity-list">
            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">跑跑高屏人 烤箱大逃亡</span>
                    <span class="activity-date">活動日期：2026/05/04</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">米津玄師 2026 巡迴演唱會</span>
                    <span class="activity-date">活動日期：2026/04/29</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">咒術迴戰主題快閃店</span>
                    <span class="activity-date">活動日期：2026/04/27</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">鬼滅之刃全集中展</span>
                    <span class="activity-date">活動日期：2026/04/12</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>
        </div>

    </main>

    <script>
        const searchBtn = document.getElementById('searchBtn');
        const dropdownMenu = document.getElementById('dropdownMenu');
        const arrowIcon = document.getElementById('arrowIcon');

        // 點擊按鈕切換選單
        searchBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            dropdownMenu.classList.toggle('show');
            arrowIcon.textContent = dropdownMenu.classList.contains('show') ? '▲' : '▼';
        });

        // 防止點擊表單內部時關閉選單
        dropdownMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // 點擊畫面其他地方時關閉選單
        document.addEventListener('click', () => {
            if (dropdownMenu.classList.contains('show')) {
                dropdownMenu.classList.remove('show');
                arrowIcon.textContent = '▼';
            }
        });
    </script>
</body>
</html>
