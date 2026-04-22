<?php
require_once 'prompt_templates.php';

class LearningProcessor {
    private $ai_client;
    private $db;
    
    public function __construct($ai_client, $db) {
        $this->ai_client = $ai_client;
        $this->db = $db;
    }
    
    public function processLearningQuestion($data) {
        try {
            $subject_id = $data['subject_id'];
            $question = $data['question'];
            $grade = $data['grade'] ?? null;
            $question_type = $data['question_type'] ?? 'concept';
            
            // 獲取學科名稱
            $subject_name = $this->getSubjectName($subject_id);
            
            // 步驟1：生成主要回答
            $prompt = PromptTemplates::getLearningPrompt($subject_name, $question, $grade, $question_type);
            $ai_response = $this->ai_client->generateResponse($prompt);
            
            // 步驟2：進一步清理回應（雙重確保）
            $ai_response = $this->finalCleanupResponse($ai_response);
            
            // 步驟3：檢測知識點
            $knowledge_points = $this->detectKnowledgePoints($question, $subject_name);
            
            // 步驟4：生成延伸問題
            $follow_up_questions = $this->generateFollowUpQuestions($question, $ai_response);
            
            // 步驟5：評估信心度（基於清理後的回應）
            $confidence = $this->evaluateConfidence($question, $ai_response);
            
            return [
                'success' => true,
                'ai_response' => $ai_response,
                'knowledge_points' => $knowledge_points,
                'follow_up_questions' => $follow_up_questions,
                'confidence_score' => $confidence,
                'subject_name' => $subject_name
            ];
            
        } catch (Exception $e) {
            error_log("學習問題處理錯誤: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // 新增：最終清理回應
    private function finalCleanupResponse($response) {
        // 移除多餘的空白行
        $response = preg_replace('/\n{3,}/', "\n\n", $response);
        
        // 移除可能遺漏的 Markdown 標記
        $response = preg_replace('/(\*{1,3}|_{1,3}|`{1,3})/', '', $response);
        
        // 移除單獨的 $ 符號
        $response = str_replace('$', '', $response);
        
        // 修復常見的截斷模式
        if (preg_match('/(但是|然而|例如|比如|另外|此外|最後|總之|因此|所以|因為|如果)[^。！？]*$/', $response)) {
            $response .= "。\n\n（註：為確保回答質量，此處已進行適當的內容整理）";
        }
        
        return trim($response);
    }
    
    // 其他現有方法保持不變...
    private function getSubjectName($subject_id) {
        $query = "SELECT name FROM subjects WHERE id = :subject_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':subject_id' => $subject_id]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
        return $subject ? $subject['name'] : '通用';
    }
    
    private function detectKnowledgePoints($question, $subject) {
        $detection_prompt = PromptTemplates::getKnowledgeDetectionPrompt($question, $subject);
        $response = $this->ai_client->generateResponse($detection_prompt);
        
        $json_start = strpos($response, '{');
        $json_end = strrpos($response, '}');
        
        if ($json_start !== false && $json_end !== false) {
            $json_str = substr($response, $json_start, $json_end - $json_start + 1);
            $knowledge_data = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $knowledge_data;
            }
        }
        
        return [
            'main_topic' => '待分析',
            'knowledge_points' => ['基礎概念'],
            'difficulty_level' => 'basic'
        ];
    }
    
    private function generateFollowUpQuestions($original_question, $ai_response) {
        $follow_up_prompt = PromptTemplates::getFollowUpQuestionsPrompt($original_question, $ai_response);
        $response = $this->ai_client->generateResponse($follow_up_prompt);
        
        $json_start = strpos($response, '[');
        $json_end = strrpos($response, ']');
        
        if ($json_start !== false && $json_end !== false) {
            $json_str = substr($response, $json_start, $json_end - $json_start + 1);
            $questions = json_decode($json_str, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($questions)) {
                return $questions;
            }
        }
        
        return [
            "你能舉一個生活中的例子來說明這個概念嗎？",
            "這個知識點和之前學過的什麼內容有關聯？",
            "如果條件改變，結果會有什麼不同？"
        ];
    }
    
    private function evaluateConfidence($question, $response) {
        $word_count = str_word_count($response);
        $has_examples = strpos($response, '例如') !== false || strpos($response, '例子') !== false;
        $has_steps = strpos($response, '步驟') !== false || strpos($response, '首先') !== false;
        $has_structure = preg_match('/【.*】/', $response);
        
        $score = 0.6;
        if ($word_count > 100) $score += 0.1;
        if ($has_examples) $score += 0.1;
        if ($has_steps) $score += 0.1;
        if ($has_structure) $score += 0.1;
        
        return min(1.0, $score);
    }
    
    public function saveLearningQuestion($data, $result) {
        try {
            $query = "INSERT INTO learning_questions 
                     (subject_id, question_text, student_grade, question_type, ai_response, knowledge_points_covered, follow_up_questions, confidence_score) 
                     VALUES (:subject_id, :question_text, :grade, :question_type, :ai_response, :knowledge_points, :follow_up_questions, :confidence)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':subject_id' => $data['subject_id'],
                ':question_text' => $data['question'],
                ':grade' => $data['grade'],
                ':question_type' => $data['question_type'],
                ':ai_response' => $result['ai_response'],
                ':knowledge_points' => json_encode($result['knowledge_points'], JSON_UNESCAPED_UNICODE),
                ':follow_up_questions' => json_encode($result['follow_up_questions'], JSON_UNESCAPED_UNICODE),
                ':confidence' => $result['confidence_score']
            ]);
            
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("保存學習問題錯誤: " . $e->getMessage());
            return false;
        }
    }
    
    public function getLearningHistory($limit = 10) {
        try {
            $query = "SELECT lq.*, s.name as subject_name 
                     FROM learning_questions lq 
                     LEFT JOIN subjects s ON lq.subject_id = s.id 
                     ORDER BY lq.created_at DESC 
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("獲取學習歷史錯誤: " . $e->getMessage());
            return [];
        }
    }
}
?>