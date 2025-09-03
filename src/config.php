<?php
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'security_demo';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? 'rootpass';

// データベース接続を試行（リトライ機能付き）
$maxRetries = 30;
$retryDelay = 1; // 1秒
$pdo = null;

for ($i = 0; $i < $maxRetries; $i++) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // 接続成功
    } catch(PDOException $e) {
        if ($i === $maxRetries - 1) {
            // 最後の試行で失敗した場合
            die("データベース接続に失敗しました。しばらく待ってから再度お試しください。<br>エラー: " . $e->getMessage());
        }
        // 少し待ってから再試行
        sleep($retryDelay);
    }
}

function hashPassword($password) {
    return hash('sha256', $password);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// セッションの有効性を確認する関数
function validateSession() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    // データベースでユーザーが存在するかチェック
    try {
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if (!$stmt->fetch()) {
            // ユーザーが存在しない場合、セッションを破棄
            session_destroy();
            return false;
        }
        
        return true;
    } catch(PDOException $e) {
        // データベースエラーの場合もセッション無効
        return false;
    }
}
?>