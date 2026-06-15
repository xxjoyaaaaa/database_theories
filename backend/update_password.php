<?php
session_start();
require_once "../backend/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../backend/login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $old_password = $_POST["old_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    $user_id = $_SESSION["user_id"];

    $sql = "SELECT password_hash FROM USERS WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($old_password, $user["password_hash"])) {

        $message = "原密碼錯誤";

    } elseif ($new_password != $confirm_password) {

        $message = "兩次新密碼輸入不一致";

    } else {

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $update_sql = "UPDATE USERS
                       SET password_hash = ?
                       WHERE user_id = ?";

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $new_hash, $user_id);

        if ($update_stmt->execute()) {
            echo "<script>
                    alert('密碼修改成功');
                    window.location.href = '../pages/profile.php';
                </script>";
            exit;
        } else {
            $message = "修改失敗";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>修改密碼</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center">

<div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">

    <h2 class="text-2xl font-bold text-center mb-6">修改密碼</h2>

    <?php if (!empty($message)): ?>
        <div class="mb-4 rounded-lg bg-red-100 text-red-700 px-4 py-2 text-center">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block mb-1 font-medium">原密碼</label>
            <input type="password" name="old_password" required
                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>

        <div>
            <label class="block mb-1 font-medium">新密碼</label>
            <input type="password" name="new_password" required
                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>

        <div>
            <label class="block mb-1 font-medium">確認新密碼</label>
            <input type="password" name="confirm_password" required
                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>

        <button type="submit"
                class="w-full rounded-lg bg-gray-800 text-white py-2 hover:bg-gray-700">
            修改密碼
        </button>

    </form>

    <p class="text-center mt-4">
        <a href="../pages/profile.php" class="text-gray-600 hover:underline">
            返回使用者頁面
        </a>
    </p>

</div>

</body>
</html>