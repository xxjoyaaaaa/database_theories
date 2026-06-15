<?php
session_start();
require_once "db.php";

if (isset($conn)) {
    $db = $conn;
} else if (isset($mysqli)) {
    $db = $mysqli;
} else {
    die("資料庫連線失敗，請檢查 db.php");
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $error_message = "請完整填寫所有欄位";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email 格式不正確";
    } else if (strlen($password) < 6) {
        $error_message = "密碼至少需要 6 個字元";
    } else if ($password !== $confirm_password) {
        $error_message = "兩次輸入的密碼不一致";
    } else {
        /*
        |--------------------------------------------------------------------------
        | 檢查 Email 是否已存在
        |--------------------------------------------------------------------------
        */

        $check_sql = "
            SELECT user_id
            FROM USERS
            WHERE email = ?
            LIMIT 1
        ";

        $check_stmt = $db->prepare($check_sql);

        if (!$check_stmt) {
            die("SQL prepare 失敗：" . $db->error);
        }

        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error_message = "註冊失敗，此 Email 已被使用";
            $check_stmt->close();
        } else {
            $check_stmt->close();

            /*
            |--------------------------------------------------------------------------
            | 產生新的 user_id，例如 U001、U002、U003
            |--------------------------------------------------------------------------
            */

            $id_sql = "
                SELECT user_id
                FROM USERS
                WHERE user_id REGEXP '^U[0-9]{3}$'
                ORDER BY user_id DESC
                LIMIT 1
            ";

            $id_result = $db->query($id_sql);

            if ($id_result && $id_result->num_rows > 0) {
                $row = $id_result->fetch_assoc();
                $last_id = $row["user_id"];
                $num = intval(substr($last_id, 1)) + 1;
            } else {
                $num = 1;
            }

            $user_id = "U" . str_pad($num, 3, "0", STR_PAD_LEFT);

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";

            /*
            |--------------------------------------------------------------------------
            | 新增使用者
            |--------------------------------------------------------------------------
            */

            $sql = "
                INSERT INTO USERS (
                    user_id,
                    name,
                    email,
                    password_hash,
                    role
                )
                VALUES (?, ?, ?, ?, ?)
            ";

            $stmt = $db->prepare($sql);

            if (!$stmt) {
                die("SQL prepare 失敗：" . $db->error);
            }

            $stmt->bind_param(
                "sssss",
                $user_id,
                $name,
                $email,
                $password_hash,
                $role
            );

            if ($stmt->execute()) {
                $success_message = "註冊成功！請前往登入。";
            } else {
                $error_message = "註冊失敗：" . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>註冊｜活動導購與行程紀錄系統</title>

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

<main class="min-h-screen flex items-center justify-center px-6 py-12">

    <section class="w-full max-w-5xl grid md:grid-cols-2 bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-2xl overflow-hidden border border-white">

        <!-- 左側介紹區 -->
        <div class="hidden md:flex flex-col justify-between p-10 bg-indigo-700 text-white">
            <div>
                <a href="../index.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white transition">
                    <span class="text-xl">←</span>
                    回首頁
                </a>

                <h1 class="mt-16 text-4xl font-extrabold leading-tight">
                    建立你的帳號
                </h1>

                <p class="mt-5 text-white/80 leading-8">
                    註冊後即可瀏覽活動資訊、將活動加入行事曆，並使用自動提醒功能。
                </p>
            </div>

            <div class="space-y-4">
                <div class="p-5 rounded-2xl bg-white/10">
                    <p class="font-bold">活動收藏</p>
                    <p class="text-sm text-white/70 mt-1">將感興趣的活動加入個人清單。</p>
                </div>

                <div class="p-5 rounded-2xl bg-white/10">
                    <p class="font-bold">行事曆紀錄</p>
                    <p class="text-sm text-white/70 mt-1">清楚查看活動日與售票日。</p>
                </div>

                <div class="p-5 rounded-2xl bg-white/10">
                    <p class="font-bold">自動提醒</p>
                    <p class="text-sm text-white/70 mt-1">系統會依照狀態自動建立提醒。</p>
                </div>
            </div>
        </div>

        <!-- 右側註冊表單 -->
        <div class="p-8 md:p-12">
            <div class="mb-8">
                <p class="text-sm font-medium text-indigo-600">第 10 組專題系統</p>
                <h2 class="mt-2 text-3xl font-bold">使用者註冊</h2>
                <p class="mt-3 text-slate-500">
                    請填寫以下資料建立帳號。
                </p>
            </div>

            <?php if ($error_message !== ""): ?>
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message !== ""): ?>
                <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        使用者名稱
                    </label>
                    <input
                        type="text"
                        name="name"
                        required
                        value="<?= htmlspecialchars($_POST["name"] ?? "") ?>"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                        placeholder="請輸入使用者名稱"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        required
                        value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                        placeholder="請輸入 Email"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        密碼
                    </label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                        placeholder="請輸入密碼，至少 6 個字元"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-2">
                        確認密碼
                    </label>
                    <input
                        type="password"
                        name="confirm_password"
                        required
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 outline-none focus:ring-4 focus:ring-indigo-100 focus:border-indigo-400 transition"
                        placeholder="請再次輸入密碼"
                    >
                </div>

                <button
                    type="submit"
                    class="w-full rounded-2xl bg-indigo-600 px-5 py-3.5 text-white font-semibold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition"
                >
                    註冊
                </button>
            </form>

            <p class="mt-7 text-center text-slate-500">
                已經有帳號？
                <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-700">
                    前往登入
                </a>
            </p>
        </div>

    </section>

</main>

</body>
</html>