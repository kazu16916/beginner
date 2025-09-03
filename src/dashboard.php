<?php
// src/dashboard.php
session_start();
require_once 'config.php';

// セッション有効性チェック
if (!validateSession()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ダッシュボード - 商品検索サイト</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid #ddd; padding-bottom: 15px; }
        .search-box { margin-bottom: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 4px; }
        .search-box input[type="text"] { width: 70%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .search-box button { padding: 10px 20px; margin-left: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .admin-section { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .results { margin-top: 20px; }
        .user-info { background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
        .logout-btn { background-color: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .warning { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .sql-result { margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>商品検索サイト</h1>
        <div>
            <span>ようこそ、<?php echo htmlspecialchars($_SESSION['username']); ?>さん</span>
            <a href="logout.php" class="logout-btn">ログアウト</a>
        </div>
    </div>
    
    <div class="user-info">
        <strong>ユーザ情報:</strong> 
        <?php echo htmlspecialchars($_SESSION['username']); ?>
        <?php if (isAdmin()): ?>
            <span style="color: red; font-weight: bold;">[管理者権限]</span>
        <?php else: ?>
            <span>[一般ユーザ]</span>
        <?php endif; ?>
    </div>
    
    <div class="search-box">
        <h2>商品検索</h2>
        <form method="GET">
            <input type="text" name="search" placeholder="商品名を入力してください" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">検索</button>
        </form>
    </div>
    
    <?php
    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
        $search = $_GET['search'];
        
        echo "<div class='results'>";
        echo "<h3>検索結果:</h3>";
        
        try {
            // ユーザーの入力をセミコロンで分割して、複数のクエリとして扱う（脆弱な実装）
            $queries = explode(';', $search);
            
            // 最初のクエリ（本来の商品検索）を組み立てて実行
            $first_query_part = array_shift($queries);
            $product_sql = "SELECT * FROM products WHERE name LIKE '%" . $first_query_part . "%'";
            
            try {
                $result = $pdo->query($product_sql);
                if ($result && $result->rowCount() > 0) {
                    echo "<div class='sql-result'>";
                    echo "<h4>商品検索結果:</h4>";
                    echo "<table>";
                    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                    echo "<tr>";
                    foreach(array_keys($rows[0]) as $header) echo "<th>".htmlspecialchars($header)."</th>";
                    echo "</tr>";
                    foreach($rows as $row) {
                        echo "<tr>";
                        foreach($row as $value) echo "<td>".htmlspecialchars($value)."</td>";
                        echo "</tr>";
                    }
                    echo "</table></div>";
                } else {
                     if (count($queries) == 0) { // インジェクションがない場合のみメッセージを表示
                        echo "<p>商品が見つかりませんでした。</p>";
                    }
                }
            } catch (PDOException $e) {
                echo "<div class='warning'>商品検索クエリエラー: " . $e->getMessage() . "</div>";
            }


            // インジェクションされたクエリ（2つ目以降）を実行
            foreach ($queries as $injected_query) {
                $injected_query = trim($injected_query);
                if (empty($injected_query) || strpos($injected_query, '--') === 0) {
                    continue;
                }

                try {
                    $injected_result = $pdo->query($injected_query);
                    if ($injected_result && $injected_result->rowCount() > 0) {
                        echo "<div class='sql-result'>";
                        echo "<h4>インジェクションクエリの結果 (" . htmlspecialchars($injected_query) . "):</h4>";
                        echo "<table>";
                        
                        $rows = $injected_result->fetchAll(PDO::FETCH_ASSOC);
                        // ヘッダー
                        echo "<tr>";
                        foreach (array_keys($rows[0]) as $header) {
                            echo "<th>" . htmlspecialchars($header) . "</th>";
                        }
                        echo "</tr>";
                        
                        // データ
                        foreach ($rows as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table></div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='warning'>インジェクションSQLエラー: " . $e->getMessage() . "</div>";
                }
            }
            
        } catch(Exception $e) {
            echo "<div class='warning'>エラー: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
    }
    ?>
    
    <!-- 管理者専用機能 -->
    <?php if (isAdmin()): ?>
    <div class="admin-section">
        <h2>🔧 管理者専用機能</h2>
        
        <!-- システム監視機能 -->
        <div style="margin-bottom: 30px;">
            <h3>📊 システム監視ダッシュボード</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background-color: #e7f3ff; padding: 15px; border-radius: 4px; text-align: center;">
                    <h4>サーバー稼働時間</h4>
                    <?php
                    $uptime = shell_exec('uptime -p 2>/dev/null || echo "取得できません"');
                    echo "<strong>" . htmlspecialchars(trim($uptime)) . "</strong>";
                    ?>
                </div>
                <div style="background-color: #e8f5e8; padding: 15px; border-radius: 4px; text-align: center;">
                    <h4>ディスク使用量</h4>
                    <?php
                    $disk = shell_exec("df -h / | tail -1 | awk '{print $5}' 2>/dev/null || echo '不明'");
                    echo "<strong>" . htmlspecialchars(trim($disk)) . "</strong>";
                    ?>
                </div>
                <div style="background-color: #fff3e0; padding: 15px; border-radius: 4px; text-align: center;">
                    <h4>メモリ使用量</h4>
                    <?php
                    $memory = shell_exec("free -h | head -2 | tail -1 | awk '{print $3\"/\"$2}' 2>/dev/null || echo '不明'");
                    echo "<strong>" . htmlspecialchars(trim($memory)) . "</strong>";
                    ?>
                </div>
            </div>
        </div>

        <!-- ログファイル管理 -->
        <div style="margin-bottom: 30px;">
            <h3>📋 ログファイル管理</h3>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <h4>システムログ</h4>
                <form method="POST" style="margin-bottom: 10px;">
                    <label>表示行数: </label>
                    <select name="log_lines" style="padding: 5px; margin-right: 10px;">
                        <option value="10">10行</option>
                        <option value="50">50行</option>
                        <option value="100">100行</option>
                    </select>
                    <button type="submit" name="view_logs" style="padding: 5px 15px;">ログ表示</button>
                </form>
                
                <?php
                if (isset($_POST['view_logs'])) {
                    $lines = intval($_POST['log_lines']);
                    // 安全なログ表示
                    $logFiles = ['/var/log/apache2/access.log', '/var/log/apache2/error.log', '/tmp/app.log'];
                    
                    echo "<div style='background-color: #000; color: #0f0; padding: 10px; border-radius: 4px; font-family: monospace; max-height: 300px; overflow-y: auto;'>";
                    
                    foreach ($logFiles as $logFile) {
                        if (file_exists($logFile)) {
                            echo "<h5 style='color: #fff;'>📄 " . basename($logFile) . "</h5>";
                            $output = shell_exec("tail -n $lines $logFile 2>/dev/null");
                            if ($output) {
                                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                            } else {
                                echo "<div style='color: #888;'>ログが空または読み取りできません</div>";
                            }
                            echo "<hr style='border-color: #333;'>";
                        }
                    }
                    
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- プロセス管理 -->
        <div style="margin-bottom: 30px;">
            <h3>⚙️ プロセス管理</h3>
            <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap;">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="show_processes" style="padding: 8px 15px; background-color: #17a2b8; color: white; border: none; border-radius: 4px;">
                        📋 実行中プロセス
                    </button>
                </form>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="show_connections" style="padding: 8px 15px; background-color: #6f42c1; color: white; border: none; border-radius: 4px;">
                        🌐 ネットワーク接続
                    </button>
                </form>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="show_services" style="padding: 8px 15px; background-color: #20c997; color: white; border: none; border-radius: 4px;">
                        🔧 サービス一覧
                    </button>
                </form>
            </div>
            
            <?php
            if (isset($_POST['show_processes'])) {
                echo "<div style='background-color: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace;'>";
                echo "<h4 style='color: #fff;'>実行中プロセス一覧</h4>";
                $output = shell_exec('ps aux | head -20');
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                echo "</div>";
            }
            
            if (isset($_POST['show_connections'])) {
                echo "<div style='background-color: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace;'>";
                echo "<h4 style='color: #fff;'>ネットワーク接続</h4>";
                $output = shell_exec('netstat -tuln 2>/dev/null || ss -tuln');
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                echo "</div>";
            }
            
            if (isset($_POST['show_services'])) {
                echo "<div style='background-color: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace;'>";
                echo "<h4 style='color: #fff;'>システムサービス</h4>";
                $output = shell_exec('systemctl list-units --type=service --state=running | head -15 2>/dev/null || service --status-all | head -15');
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- ファイルシステム管理 -->
        <div style="margin-bottom: 30px;">
            <h3>📁 ファイルシステム管理</h3>
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 4px;">
                <form method="POST" style="margin-bottom: 15px;">
                    <label>ディレクトリパス: </label>
                    <input type="text" name="directory_path" value="<?php echo htmlspecialchars($_POST['directory_path'] ?? '/var/www/html'); ?>" 
                           style="width: 300px; padding: 5px; margin-right: 10px;" placeholder="/var/www/html">
                    <button type="submit" name="list_directory" style="padding: 5px 15px;">📂 ディレクトリ一覧</button>
                </form>
                
                <?php
                if (isset($_POST['list_directory'])) {
                    $dirPath = $_POST['directory_path'];
                    echo "<div style='background-color: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace;'>";
                    echo "<h4 style='color: #fff;'>📁 " . htmlspecialchars($dirPath) . "</h4>";
                    
                    // 基本的な安全性チェック
                    if (strpos($dirPath, '..') !== false || strpos($dirPath, ';') !== false) {
                        echo "<div style='color: #ff6b6b;'>⚠️ 不正なパスが検出されました</div>";
                    } else {
                        $output = shell_exec("ls -la " . escapeshellarg($dirPath) . " 2>&1");
                        echo "<pre>" . htmlspecialchars($output) . "</pre>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- システムコマンド実行（脆弱性のある機能） -->
        <div style="margin-bottom: 30px; border: 2px solid #dc3545; border-radius: 4px;">
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px 4px 0 0;">
                <strong>🚨 高度なシステム管理</strong>
            </div>
            
            <div style="padding: 20px;">
                <h3>💻 カスタムコマンド実行</h3>
                <form method="POST">
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">コマンド:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="text" name="command" 
                                   placeholder="システムコマンドを入力してください" 
                                   style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"
                                   value="<?php echo isset($_POST['command']) ? htmlspecialchars($_POST['command']) : ''; ?>">
                            <button type="submit" name="execute" 
                                    style="padding: 10px 20px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                実行
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php
                if (isset($_POST['execute']) && !empty($_POST['command'])) {
                    $command = $_POST['command'];
                    
                    echo "<div style='margin-top: 15px;'>";
                    echo "<h4>コマンド実行結果:</h4>";
                    echo "<div style='background-color: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: monospace;'>";
                    echo "<div style='color: #fff; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 10px;'>";
                    echo "root@webapp:~# " . htmlspecialchars($command);
                    echo "</div>";
                    
                    // ⚠️ OSインジェクションの脆弱性 - ユーザー入力を直接実行
                    $output = shell_exec($command . " 2>&1");
                    
                    if ($output) {
                        echo "<pre style='margin: 0; white-space: pre-wrap;'>" . htmlspecialchars($output) . "</pre>";
                    } else {
                        echo "<div style='color: #ff6b6b;'>コマンドの実行に失敗したか、出力がありません。</div>";
                    }
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- システム情報 -->
        <div style="margin-bottom: 30px;">
            <h3>ℹ️ システム情報</h3>
            <div style="background-color: #e9ecef; padding: 15px; border-radius: 4px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                    <div>
                        <strong>OS:</strong>
                        <?php echo htmlspecialchars(trim(shell_exec('uname -a 2>/dev/null || echo "不明"'))); ?>
                    </div>
                    <div>
                        <strong>PHP Version:</strong>
                        <?php echo PHP_VERSION; ?>
                    </div>
                    <div>
                        <strong>Web Server:</strong>
                        <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '不明'); ?>
                    </div>
                    <div>
                        <strong>Document Root:</strong>
                        <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '不明'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>