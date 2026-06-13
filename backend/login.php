<?php
session_start();
require_once "db.php";

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

        header("Location: ../index.php");
        exit;
    } else {
        echo "Email 或密碼錯誤";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>登入</title>
</head>
<body>

<h2>使用者登入</h2>

<form method="POST">
    Email：
    <input type="email" name="email" required>
    <br><br>

    密碼：
    <input type="password" name="password" required>
    <br><br>

    <button type="submit">登入</button>
</form>

<br>
<a href="register.php">沒有帳號？註冊</a>

</body>
</html>