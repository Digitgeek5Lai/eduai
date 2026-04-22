<?php
// init_database.php
echo "正在初始化 AI 學習平台資料庫...\n";

// 資料庫配置
$host = 'localhost';
$dbname = 'stud';
$username = 'stud'; // 請修改為您的資料庫用戶名
$password = '12345678'; // 請修改為您的資料庫密碼

try {
    // 連接 MySQL（不指定資料庫，用來創建資料庫）
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    // 創建資料庫
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");
    
    echo "✓ 資料庫創建成功\n";
    
    // 創建學科分類表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 創建知識點表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS knowledge_points (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            difficulty ENUM('basic', 'intermediate', 'advanced') DEFAULT 'basic',
            prerequisites TEXT,
            examples TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 創建學習問題表
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS learning_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject_id INT,
            question_text TEXT NOT NULL,
            student_grade VARCHAR(50),
            question_type ENUM('concept', 'calculation', 'proof', 'application') DEFAULT 'concept',
            ai_response TEXT,
            knowledge_points_covered TEXT,
            follow_up_questions TEXT,
            confidence_score FLOAT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (subject_id) REFERENCES subjects(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ 資料表創建成功\n";
    
    // 清空現有數據（避免重複插入）
    $pdo->exec("DELETE FROM knowledge_points");
    $pdo->exec("DELETE FROM subjects");
    
    // 插入基礎學科數據 - 使用明確的 UTF-8 編碼
    $subjects = [
        ['數學', '包括代數、幾何、微積分等數學分支'],
        ['物理', '力學、電磁學、光學、熱學等物理領域'],
        ['化學', '無機化學、有機化學、物理化學等'],
        ['生物', '細胞生物學、遺傳學、生態學等'],
        ['計算機科學', '程式設計、算法、數據結構等'],
        ['語文', '國文、英文等語言學習'],
        ['歷史', '中國歷史、世界歷史等'],
        ['地理', '自然地理、人文地理等']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
    foreach ($subjects as $subject) {
        $stmt->execute($subject);
    }
    
    echo "✓ 學科數據插入成功\n";
    
    // 插入範例知識點
    $knowledge_points = [
        [1, '一元二次方程式', '形如 ax² + bx + c = 0 的方程式，解為 x = [-b ± √(b²-4ac)] / 2a', 'basic', '代數基礎,平方根概念', 'x² - 5x + 6 = 0 的解為 x=2, x=3'],
        [1, '勾股定理', '直角三角形斜邊平方等於兩直角邊平方和：c² = a² + b²', 'basic', '三角形性質,平方概念', '直角邊3和4，斜邊為5'],
        [2, '牛頓第二定律', '物體加速度與作用力成正比，與質量成反比：F = ma', 'basic', '速度,加速度概念', '質量2kg的物體受10N力，加速度為5m/s²'],
        [3, '化學反應式平衡', '確保化學反應前後原子種類和數量相等', 'intermediate', '化學元素,原子概念', '2H₂ + O₂ → 2H₂O'],
        [4, '光合作用', '植物利用光能將二氧化碳和水轉化為葡萄糖和氧氣', 'intermediate', '細胞結構,能量轉換', '6CO₂ + 6H₂O → C₆H₁₂O₆ + 6O₂'],
        [5, '二元搜尋算法', '在有序陣列中快速查找元素的算法', 'intermediate', '程式設計基礎,數據結構', '時間複雜度為 O(log n)']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO knowledge_points (subject_id, title, content, difficulty, prerequisites, examples) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($knowledge_points as $point) {
        $stmt->execute($point);
    }
    
    echo "✓ 知識點數據插入成功\n";
    echo "🎉 資料庫初始化完成！\n";
    echo "請訪問 learning_platform.php 開始使用系統。\n";
    
    // 測試數據顯示
    echo "\n測試數據：\n";
    $test = $pdo->query("SELECT name, description FROM subjects LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($test as $row) {
        echo "- " . $row['name'] . ": " . $row['description'] . "\n";
    }
    
} catch (PDOException $e) {
    die("資料庫錯誤: " . $e->getMessage());
}
?>