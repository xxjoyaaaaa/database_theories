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
<html>
<head>
    <meta charset="UTF-8">
    <title>修改密碼</title>
</head>
<body>

<h2>修改密碼</h2>

<p><?php echo $message; ?></p>

<form method="POST">

    原密碼：
    <input type="password" name="old_password" required>
    <br><br>

    新密碼：
    <input type="password" name="new_password" required>
    <br><br>

    確認新密碼：
    <input type="password" name="confirm_password" required>
    <br><br>

    <button type="submit">修改密碼</button>

</form>

<br>

<a href="../pages/profile.php">返回使用者頁面</a>

</body>
</html>