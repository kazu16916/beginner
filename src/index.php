<?php
// src/index.php
session_start();
require_once 'config.php';

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 脆弱性のあるクエリ（SQLインジェクション可能）
    $sql = "SELECT * FROM Users WHERE username = '$username' AND password = '" . hashPassword($password) . "'";
    
    // さらに脆弱性を追加（パスワードチェックをバイパス可能）
    if (strpos($password, "' OR '1'='1") !== false) {
        $sql = "SELECT * FROM Users WHERE username = '$username' AND (password = '" . hashPassword($password) . "' OR '1'='1')";
    }
    
    try {
        $result = $pdo->query($sql);
        $user = $result->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // セッション再生成（セキュリティ強化）
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['login_time'] = time();
            
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "ユーザ名またはパスワードが間違っています。";
        }
    } catch(PDOException $e) {
        $error = "ログインエラー: " . $e->getMessage();
    }
}

// セッション有効性チェック
if (validateSession()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>セキュリティ演習 - 商品検索サイト</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background-color: #0056b3; }
        .error { color: red; margin-top: 10px; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    
    <h1>ログイン</h1>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">ユーザ名:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">ログイン</button>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </form>
    
    <div style="margin-top: 30px; padding: 15px; background-color: #f8f9fa; border-radius: 4px;">
        <h3>テストユーザ情報:</h3>
        <p><strong>一般ユーザ:</strong> 
        username：test / password：test</p>
    </div>
</body>
</html>