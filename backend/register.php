<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = uniqid("U");
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = "user";

    $check_sql = "SELECT email FROM USERS WHERE email = ?"; //先查email
    $sql = "INSERT INTO USERS (user_id, name, email, password_hash, role)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $user_id, $name, $email, $password_hash, $role);

    if ($stmt->execute()) {
        $message = "註冊成功！請前往登入";
    } else {
        $message = "註冊失敗，Email 可能已被使用";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>註冊</title>
</head>
<body>

<h2>使用者註冊</h2>
<p><?php echo $message; ?></p>

<form method="POST">
    姓名：
    <input type="text" name="name" required>
    <br><br>

    Email：
    <input type="email" name="email" required>
    <br><br>

    密碼：
    <input type="password" name="password" required>
    <br><br>

    <button type="submit">註冊</button>
</form>

<br>
<a href="login.php">已有帳號？登入</a>

</body>
</html>