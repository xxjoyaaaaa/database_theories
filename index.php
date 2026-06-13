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
            color: #fbd34d; /* 星星的黃色 */
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
        
        /* 下拉選單樣式 */
        .dropdown-menu {
            display: none; /* 預設隱藏 */
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 5px;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 120px;
            z-index: 10;
        }
        .dropdown-menu.show {
            display: block; /* JS 切換用 */
        }
        .dropdown-item {
            padding: 10px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .dropdown-item:last-child {
            border-bottom: none;
        }
        .dropdown-item:hover {
            background-color: #f5f5f5;
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
            <a>行事曆</a>
            <a>使用者</a>
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
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-item">日期</div>
                    <div class="dropdown-item">地點</div>
                    <div class="dropdown-item">類別</div>
                </div>
            </div>
        </div>

        <div class="activity-list">
            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">活動名稱 1</span>
                    <span class="activity-date">活動日期：2026/05/04</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">活動名稱 2</span>
                    <span class="activity-date">活動日期：2026/04/29</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">活動名稱 3</span>
                    <span class="activity-date">活動日期：2026/04/27</span>
                </div>
                <div class="card-right">點選閱讀詳細資訊...</div>
            </div>

            <div class="activity-card">
                <div class="card-left">
                    <span class="activity-name">活動名稱 4</span>
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
            e.stopPropagation(); // 防止點擊事件往上傳遞
            dropdownMenu.classList.toggle('show');
            
            // 切換箭頭方向
            if (dropdownMenu.classList.contains('show')) {
                arrowIcon.textContent = '▲';
            } else {
                arrowIcon.textContent = '▼';
            }
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