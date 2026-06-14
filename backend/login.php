<?php
session_start();
require_once "db.php";

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM USERS WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user["password_hash"])) {
        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        header("Location: ../pages/activity_list.php");
        exit;
    } else {
        $error_message = "Email 或密碼錯誤";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登入｜活動導購與行程紀錄系統</title>
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

<body class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-blue-100 text-slate-900">

    <main class="min-h-screen flex items-center justify-center px-6 py-12">

        <section class="w-full max-w-5xl grid md:grid-cols-2 bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-2xl overflow-hidden border border-white">

            <div class="hidden md:flex flex-col justify-between p-10 bg-slate-900 text-white">
                <div>
                    <a href="../index.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white transition">
                        <span class="text-xl">←</span>
                        回首頁
                    </a>

                    <h1 class="mt-16 text-4xl font-extrabold leading-tight">
                        歡迎回來
                    </h1>

                    <p class="mt-5 text-white/70 leading-8">
                        登入後即可瀏覽活動、加入行事曆，並查看活動前一天中午的自動提醒。
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="h-24 rounded-2xl bg-white/10"></div>
                    <div class="h-24 rounded-2xl bg-white/20"></div>
                    <div class="h-24 rounded-2xl bg-white/10"></div>
                </div>
            </div>

            <div class="p-8 md:p-12">
                <div class="mb-10">
                    <p class="text-sm font-medium text-indigo-600">第 10 組專題系統</p>
                    <h2 class="mt-2 text-3xl font-bold">使用者登入</h2>
                    <p class="mt-3 text-slate-500">請輸入 Email 與密碼進入系統。</p>
                </div>

                <?php if ($error_message !== ""): ?>
                    <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                        <?= htmlspecialchars($error_message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">Email</label>
                        <input type="email" name="email" required
                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                               placeholder="請輸入 Email">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-600 mb-2">密碼</label>
                        <input type="password" name="password" required
                               class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                               placeholder="請輸入密碼">
                    </div>

                    <button type="submit"
                            class="w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-white font-semibold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition">
                        登入
                    </button>
                </form>

                <p class="mt-7 text-center text-slate-500">
                    沒有帳號？
                    <a href="register.php" class="font-semibold text-indigo-600 hover:text-indigo-700">
                        註冊
                    </a>
                </p>
            </div>

        </section>

    </main>

</body>
</html>
