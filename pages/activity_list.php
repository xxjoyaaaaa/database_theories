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
    <title>活動列表｜活動導購與行程紀錄系統</title>
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
            <a href="../backend/my_schedule.php"
               class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">
                行事曆
            </a>
            <a href="profile.php"
               class="px-4 py-2 rounded-full text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition">
                使用者
            </a>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-10">

    <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-indigo-600 to-blue-600 text-white p-8 md:p-10 shadow-xl shadow-indigo-100 mb-8">
        <div class="absolute -right-16 -top-16 w-52 h-52 rounded-full bg-white/10"></div>
        <div class="absolute right-16 bottom-6 w-24 h-24 rounded-full bg-white/10"></div>

        <div class="relative flex flex-col md:flex-row md:items-end md:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 text-white/90 mb-5">
                    <span>★</span>
                    <span>最新活動</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold">探索你感興趣的活動</h1>
                <p class="mt-4 text-white/80 text-lg leading-8">
                    篩選活動、查看售票狀態，並一鍵加入個人行事曆。
                </p>
            </div>

            <button id="searchBtn"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white text-indigo-700 px-6 py-3 font-semibold shadow-lg hover:bg-indigo-50 transition">
                搜尋與篩選
                <span id="arrowIcon">▼</span>
            </button>
        </div>
    </section>

    <form id="dropdownMenu"
          action="activity_list.php"
          method="GET"
          class="hidden mb-8 rounded-3xl bg-white border border-slate-200 shadow-lg p-6 grid md:grid-cols-4 gap-5">

        <div>
            <label for="date" class="block text-sm font-semibold text-slate-600 mb-2">日期</label>
            <input type="date"
                   id="date"
                   name="date"
                   class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100"
                   value="<?= htmlspecialchars($filter_date) ?>">
        </div>

        <div>
            <label for="location" class="block text-sm font-semibold text-slate-600 mb-2">地點</label>
            <select id="location" name="location"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                    <option value="">全部地區</option>
                    <select id="location" name="location" class="filter-input">
                        <option value="">全部地區</option>

                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location) ?>"
                                <?= $filter_location == $location ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </select>
        </div>

        <div>
            <label for="category" class="block text-sm font-semibold text-slate-600 mb-2">類別</label>
            <select id="category" name="category"
                    class="w-full rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100">
                <option value="">所有類別</option>
                <option value="C001" <?= $filter_category == 'C001' ? 'selected' : '' ?>>演唱會</option>
                <option value="C002" <?= $filter_category == 'C002' ? 'selected' : '' ?>>展覽</option>
                <option value="C003" <?= $filter_category == 'C003' ? 'selected' : '' ?>>講座</option>
                <option value="C004" <?= $filter_category == 'C004' ? 'selected' : '' ?>>運動賽事</option>
            </select>
        </div>

        <div class="flex items-end">
            <button type="submit"
                    class="w-full rounded-2xl bg-slate-900 text-white px-5 py-3 font-semibold hover:bg-slate-700 transition">
                套用篩選
            </button>
        </div>
    </form>

    <?php if (!empty($activities)): ?>
        <section class="grid md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($activities as $row): ?>
                <?php
                    $activity_date = date('Y/m/d H:i', strtotime($row["activity_time"]));
                    $activity_id = htmlspecialchars($row["activity_id"]);
                    $is_finished = strtotime($row["activity_time"]) < time();
                ?>

                <a href="activity_detail.php?id=<?= $activity_id ?>"
                   class="group rounded-[1.5rem] bg-white border border-slate-200 shadow-sm hover:shadow-xl hover:-translate-y-1 transition overflow-hidden">

                    <?php if (!empty($row['image_url'])): ?>
                       <img src="../<?= htmlspecialchars($row['image_url']) ?>"
                         alt="<?= htmlspecialchars($row['name']) ?>"
                         class="h-36 w-full object-cover">
                    <?php else: ?>
                        <div class="h-36 bg-gradient-to-br from-slate-200 to-indigo-100 flex items-center justify-center">
                            <span class="text-4xl">🎫</span>
                        </div>
                    <?php endif; ?>

                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $is_finished ? 'bg-slate-100 text-slate-500' : 'bg-indigo-50 text-indigo-600' ?>">
                                <?= $is_finished ? '已舉行' : '開放查看' ?>
                            </span>

                            <?php if (!empty($row["cache_status"])): ?>
                                <span class="text-xs text-slate-500">
                                    <?= htmlspecialchars($row["cache_status"]) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h2 class="text-2xl font-bold group-hover:text-indigo-600 transition">
                            <?= htmlspecialchars($row["name"]) ?>
                        </h2>

                        <div class="mt-5 space-y-3 text-slate-500">
                            <p class="flex items-start gap-2">
                                <span>📅</span>
                                <span><?= $activity_date ?></span>
                            </p>

                            <?php if (!empty($row["location"])): ?>
                                <p class="flex items-start gap-2">
                                    <span>📍</span>
                                    <span><?= htmlspecialchars($row["location"]) ?></span>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="mt-6 inline-flex items-center text-indigo-600 font-semibold">
                            查看詳細資訊
                            <span class="ml-2 group-hover:translate-x-1 transition">→</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <div class="rounded-[1.5rem] bg-white border border-slate-200 p-12 text-center text-slate-500">
            目前沒有符合條件的活動。
        </div>
    <?php endif; ?>

</main>

<script>
    const searchBtn = document.getElementById('searchBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const arrowIcon = document.getElementById('arrowIcon');

    searchBtn.addEventListener('click', () => {
        dropdownMenu.classList.toggle('hidden');
        arrowIcon.textContent = dropdownMenu.classList.contains('hidden') ? '▼' : '▲';
    });
</script>

</body>
</html>
