<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $role = "user";

    // 先檢查 Email 是否已經存在
    $check_sql = "SELECT email FROM USERS WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "註冊失敗，此 Email 已被使用";
    } else {
        // 產生新的 user_id，例如 U001、U002、U003
        $id_sql = "SELECT user_id FROM USERS 
           WHERE user_id REGEXP '^U[0-9]{3}$' 
           ORDER BY user_id DESC 
           LIMIT 1";
        $id_result = $conn->query($id_sql);

        if ($id_result && $id_result->num_rows > 0) {
            $row = $id_result->fetch_assoc();
            $last_id = $row["user_id"];
            $num = intval(substr($last_id, 1)) + 1;
        } else {
            $num = 1;
        }

        $user_id = "U" . str_pad($num, 3, "0", STR_PAD_LEFT);

        $sql = "INSERT INTO USERS (user_id, name, email, password_hash, role)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $user_id, $name, $email, $password_hash, $role);

        if ($stmt->execute()) {
            $message = "註冊成功！請前往登入";
        } else {
            $message = "註冊失敗";
        }
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