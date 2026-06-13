<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../backend/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>使用者資料</title>
    <style>
        body {
            font-family: "Microsoft JhengHei", sans-serif;
            background-color: #f5f5f5;
            padding: 30px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        h2 {
            margin-bottom: 20px;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .menu a {
            text-decoration: none;
            color: black;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .menu a:hover {
            background-color: #eee;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>使用者資料</h2>

    <p>
        歡迎，
        <strong><?php echo $_SESSION["name"]; ?></strong>
    </p>

    <br>

    <div class="menu">
        <a href="#">提醒</a>
        <a href="../backend/update_password.php">修改密碼</a>
        <a href="../backend/logout.php">登出</a>
    </div>

</div>

</body>
</html>