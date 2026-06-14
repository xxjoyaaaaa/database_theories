<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動導購與行程紀錄系統</title>
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

<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 text-slate-900">

    <div class="min-h-screen flex flex-col">

        <header class="bg-white/70 backdrop-blur border-b border-white/60 sticky top-0 z-10">
            <div class="max-w-6xl mx-auto px-6 py-5 flex items-center justify-between">
                <h1 class="text-xl md:text-2xl font-bold tracking-tight">
                    活動導購與行程紀錄系統
                </h1>

                <a href="backend/login.php"
                   class="hidden sm:inline-flex px-5 py-2.5 rounded-full bg-slate-900 text-white font-medium hover:bg-slate-700 transition">
                    登入
                </a>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center px-6 py-16">
            <section class="w-full max-w-5xl grid md:grid-cols-2 gap-10 items-center">

                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/80 shadow-sm border border-white mb-6 text-sm text-slate-600">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        第 10 組資料庫理論專題
                    </div>

                    <h2 class="text-4xl md:text-6xl font-extrabold leading-tight tracking-tight">
                        活動導購與<br>
                        <span class="text-indigo-600">行程紀錄系統</span>
                    </h2>

                    <p class="mt-6 text-lg md:text-xl leading-8 text-slate-600">
                        大家好，我們是第 10 組。<br>
                        歡迎使用我們的活動導購與行程紀錄系統。
                    </p>

                    <div class="mt-9 flex flex-wrap gap-4">
                        <a href="backend/login.php"
                           class="inline-flex items-center justify-center px-8 py-3.5 rounded-2xl bg-indigo-600 text-white text-lg font-semibold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition">
                            登入
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="absolute -top-8 -left-8 w-28 h-28 bg-indigo-300/40 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-8 -right-8 w-36 h-36 bg-blue-300/40 rounded-full blur-2xl"></div>

                    <div class="relative bg-white/80 backdrop-blur-xl border border-white rounded-[2rem] shadow-2xl p-7">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <p class="text-sm text-slate-500">今日推薦</p>
                                <h3 class="text-2xl font-bold">精選活動</h3>
                            </div>
                            <div class="w-12 h-12 rounded-2xl bg-indigo-100 flex items-center justify-center text-2xl">
                                ✨
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100">
                                <p class="text-sm text-slate-500">演唱會</p>
                                <p class="mt-1 text-xl font-bold">快速查看活動資訊</p>
                                <p class="mt-2 text-slate-500">整合活動、導購連結與售票狀態。</p>
                            </div>

                            <div class="p-5 rounded-2xl bg-indigo-50 border border-indigo-100">
                                <p class="text-sm text-indigo-500">行事曆</p>
                                <p class="mt-1 text-xl font-bold">加入感興趣或已購票</p>
                                <p class="mt-2 text-slate-500">活動會依日期顯示在個人行事曆。</p>
                            </div>

                            <div class="p-5 rounded-2xl bg-blue-50 border border-blue-100">
                                <p class="text-sm text-blue-500">提醒</p>
                                <p class="mt-1 text-xl font-bold">活動前一天中午提醒</p>
                                <p class="mt-2 text-slate-500">不用手動設定，系統自動建立提醒。</p>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </main>

    </div>

</body>
</html>
