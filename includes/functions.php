<?php
require_once 'config/database.php';
require_once 'api/google-ai.php';
require_once 'includes/learning_processor.php';

class MessageHandler {
    private $db;
    private $ai_client;
    private $learning_processor;
    
    public function __construct($google_api_key) {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->ai_client = new GoogleAIClient($google_api_key);
        $this->learning_processor = new LearningProcessor($this->ai_client, $this->db);
    }
    
    public function getSubjects() {
        try {
            $query = "SELECT * FROM subjects ORDER BY name";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("獲取學科列表錯誤: " . $e->getMessage());
            return [];
        }
    }
    
    public function processLearningQuestion($data) {
        return $this->learning_processor->processLearningQuestion($data);
    }
    
    public function saveLearningQuestion($data, $result) {
        return $this->learning_processor->saveLearningQuestion($data, $result);
    }
    
    public function getLearningHistory($limit = 10) {
        return $this->learning_processor->getLearningHistory($limit);
    }
}
?>