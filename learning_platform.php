<?php
session_start();
require_once 'includes/functions.php';

// 在這裡填入您的 Google AI Studio API Key
$GOOGLE_AI_API_KEY = "AIpsd90992ll-909d-gmklkldLkkoos0-h67i"; // 請替換為您的實際 API Key

$messageHandler = new MessageHandler($GOOGLE_AI_API_KEY);

// 獲取學科列表
$subjects = $messageHandler->getSubjects();

// 處理學習問題提交
if ($_POST['action'] == 'ask_learning_question' && !empty($_POST['question'])) {
    $data = [
        'subject_id' => $_POST['subject_id'],
        'question' => $_POST['question'],
        'grade' => $_POST['grade'],
        'question_type' => $_POST['question_type']
    ];
    
    $result = $messageHandler->processLearningQuestion($data);
    
    if ($result['success']) {
        // 保存到資料庫
        $message_id = $messageHandler->saveLearningQuestion($data, $result);
        
        $_SESSION['learning_result'] = $result;
        $_SESSION['last_question'] = $_POST['question'];
        $_SESSION['last_subject'] = $_POST['subject_id'];
    } else {
        $_SESSION['error'] = "處理問題時發生錯誤: " . $result['error'];
    }
    
    header("Location: learning_platform.php");
    exit();
}

// 獲取歷史問題
$history_questions = $messageHandler->getLearningHistory(10);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 學習輔助平台</title>
    <link rel="stylesheet" href="learning_style.css?v=<?php echo filemtime('learning_style.css'); ?>">
	
    <!-- 簡化 CDN 引入 -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <!-- 更穩定的 MathJax 配置 -->
    <script>
        window.MathJax = {
            tex: {
                inlineMath: [['$', '$']],
                displayMath: [['$$', '$$']],
                processEscapes: true, // 允許 \ 轉義
                tags: 'ams' // 使用 AMS 編號
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre', 'code'],
                ignoreHtmlClass: 'ignore-math', // 忽略包含此類的元素
                processHtmlClass: 'mathjax'     // 處理包含此類的元素
            },
            startup: {
                typeset: false // 我們手動控制渲染時機
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.js" id="MathJax-script" async></script>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🎓 AI 學習輔助平台</h1>
            <p>提出學科問題，獲得專業的知識點講解和延伸學習</p>
        </header>

        <!-- 問題表單 -->
        <div class="question-form-section">
            <form method="POST" class="learning-form">
                <input type="hidden" name="action" value="ask_learning_question">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="subject_id">學科</label>
                        <select name="subject_id" id="subject_id" required>
                            <option value="">選擇學科</option>
                            <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>" 
                                    <?php echo (isset($_SESSION['last_subject']) && $_SESSION['last_subject'] == $subject['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subject['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="grade">年級</label>
                        <select name="grade" id="grade">
                            <option value="">不限</option>
                            <option value="小學" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '小學') ? 'selected' : ''; ?>>小學</option>
                            <option value="國中" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '國中') ? 'selected' : ''; ?>>國中</option>
                            <option value="高中" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '高中') ? 'selected' : ''; ?>>高中</option>
                            <option value="大學" <?php echo (isset($_POST['grade']) && $_POST['grade'] == '大學') ? 'selected' : ''; ?>>大學</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="question_type">問題類型</label>
                        <select name="question_type" id="question_type">
                            <option value="concept" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] == 'concept') ? 'selected' : ''; ?>>概念理解</option>
                            <option value="calculation" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] == 'calculation') ? 'selected' : ''; ?>>計算問題</option>
                            <option value="proof" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] == 'proof') ? 'selected' : ''; ?>>證明問題</option>
                            <option value="application" <?php echo (isset($_POST['question_type']) && $_POST['question_type'] == 'application') ? 'selected' : ''; ?>>應用問題</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="question">你的問題</label>
                    <textarea name="question" id="question" placeholder="請詳細描述你的學習問題..." required rows="5"><?php echo isset($_SESSION['last_question']) ? htmlspecialchars($_SESSION['last_question']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="submit-btn">🎯 尋求解答</button>
                
                <div class="example-questions">
                    <p><strong>範例問題：</strong></p>
                    <ul>
                        <li>請解釋一元二次方程式的求根公式及其推導過程</li>
                        <li>說明牛頓第二運動定律在生活中的應用</li>
                        <li>光合作用的過程分為哪幾個階段？各階段的作用是什麼？</li>
                    </ul>
                </div>
            </form>
        </div>

        <!-- 顯示錯誤訊息 -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- 顯示結果 -->
        <div class="result-section" id="result-section" style="<?php echo isset($_SESSION['learning_result']) ? 'display: block;' : 'display: none;'; ?>">
            <?php if (isset($_SESSION['learning_result'])): ?>
                <?php $result = $_SESSION['learning_result']; ?>
                <div class="ai-response-card">
                    <div class="response-header">
                        <h3>🤖 AI 老師解答 - <?php echo htmlspecialchars($result['subject_name']); ?></h3>
                        <span class="confidence-badge">信心度: <?php echo round($result['confidence_score'] * 100); ?>%</span>
                    </div>
                    
                    <!-- 顯示原始問題 -->
                    <div class="original-question">
                        <h4>原始問題：</h4>
                        <p><?php echo htmlspecialchars($_SESSION['last_question'] ?? ''); ?></p>
                    </div>
                    
                    <!-- 顯示回應 - 使用 data-attribute 儲存原始 Markdown -->
                    <div class="response-content">
                        <div class="markdown-content" data-raw-content="<?php echo htmlspecialchars($result['ai_response'] ?? ''); ?>">
                            <?php echo htmlspecialchars($result['ai_response'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <!-- 知識點分析 -->
                    <div class="knowledge-points">
                        <h4>📚 涉及知識點分析</h4>
                        <div class="knowledge-details">
                            <p><strong>主要主題：</strong> <?php echo htmlspecialchars($result['knowledge_points']['main_topic']); ?></p>
                            <p><strong>難度等級：</strong> 
                                <span class="difficulty-badge <?php echo $result['knowledge_points']['difficulty_level']; ?>">
                                    <?php 
                                    $difficulty_text = [
                                        'basic' => '基礎',
                                        'intermediate' => '中等', 
                                        'advanced' => '進階'
                                    ];
                                    echo $difficulty_text[$result['knowledge_points']['difficulty_level']] ?? '基礎'; 
                                    ?>
                                </span>
                            </p>
                            <div class="points-grid">
                                <?php foreach ($result['knowledge_points']['knowledge_points'] as $point): ?>
                                    <span class="knowledge-tag"><?php echo htmlspecialchars($point); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 延伸問題 - 使用 data-attribute 儲存原始 Markdown -->
                    <div class="follow-up-questions">
                        <h4>💡 延伸思考問題</h4>
                        <ul>
                            <?php foreach ($result['follow_up_questions'] as $index => $follow_up): ?>
                                <li>
                                    <span class="question-number"><?php echo $index + 1; ?>.</span>
                                    <span class="markdown-inline" data-raw-content="<?php echo htmlspecialchars($follow_up); ?>">
                                        <?php echo htmlspecialchars($follow_up); ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php 
                unset($_SESSION['learning_result']); 
                unset($_SESSION['last_question']);
                unset($_SESSION['last_subject']);
                ?>
            <?php endif; ?>
        </div>

        <!-- 歷史問題 -->
        <div class="history-section">
            <h2>📖 最近的學習記錄</h2>
            <div class="history-list">
                <?php if (empty($history_questions)): ?>
                    <p class="no-history">還沒有任何學習記錄，開始提出第一個問題吧！</p>
                <?php else: ?>
                    <?php foreach ($history_questions as $history): ?>
                        <div class="history-item" 
                             data-id="<?php echo $history['id']; ?>"
                             data-question="<?php echo htmlspecialchars($history['question_text']); ?>"
                             data-subject-id="<?php echo $history['subject_id']; ?>"
                             data-grade="<?php echo htmlspecialchars($history['student_grade']); ?>"
                             data-question-type="<?php echo htmlspecialchars($history['question_type']); ?>"
                             data-ai-response="<?php echo htmlspecialchars($history['ai_response']); ?>"
                             data-knowledge-points="<?php echo htmlspecialchars($history['knowledge_points_covered']); ?>"
                             data-follow-up-questions="<?php echo htmlspecialchars($history['follow_up_questions']); ?>"
                             data-confidence="<?php echo $history['confidence_score']; ?>"
                             data-subject-name="<?php echo htmlspecialchars($history['subject_name']); ?>"
                             data-created-at="<?php echo $history['created_at']; ?>">
                            <div class="history-header">
                                <span class="subject-tag"><?php echo htmlspecialchars($history['subject_name']); ?></span>
                                <span class="date"><?php echo date('m/d H:i', strtotime($history['created_at'])); ?></span>
                            </div>
                            <p class="question-preview"><?php echo htmlspecialchars(mb_substr($history['question_text'], 0, 80, 'UTF-8')); ?><?php echo mb_strlen($history['question_text'], 'UTF-8') > 80 ? '...' : ''; ?></p>
                            <div class="confidence-small">信心度: <?php echo round($history['confidence_score'] * 100); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <p>AI 學習輔助平台 - 讓學習更有效率！</p>
    </footer>

    <script src="js/learning_platform.js?v=<?php echo filemtime('js/learning_platform.js'); ?>"></script>
</body>
</html>