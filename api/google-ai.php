<?php
class GoogleAIClient {
    private $api_key;
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    public function generateResponse($prompt) {
        $model = "gemini-2.5-flash";
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$this->api_key}";
        
        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "topK" => 40,
                "topP" => 0.95,
                "maxOutputTokens" => 4096, // 增加 token 限制，避免截斷
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        error_log("Google AI API 呼叫 - 狀態碼: " . $http_code);
        
        if ($curl_error) {
            error_log("cURL 錯誤: " . $curl_error);
            return "網路錯誤: " . $curl_error;
        }
        
        if ($http_code !== 200) {
            error_log("API 錯誤回應: " . $response);
            $error_info = "API 服務暫時不可用 (HTTP {$http_code})";
            $response_data = json_decode($response, true);
            if (isset($response_data['error']['message'])) {
                $error_info .= ": " . $response_data['error']['message'];
            }
            return $error_info;
        }
        
        $response_data = json_decode($response, true);
        
        if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
            $raw_response = $response_data['candidates'][0]['content']['parts'][0]['text'];
            
            // 清理和格式化回應
            return $this->cleanAndFormatResponse($raw_response);
            
        } else {
            error_log("無法解析的回應格式: " . print_r($response_data, true));
            return "抱歉，無法解析 AI 的回應格式。";
        }
    }
    
    // 新增：清理和格式化 AI 回應
	private function cleanAndFormatResponse($raw_response) {
		// 1. 統一換行符號
		$cleaned = str_replace(["\r\n", "\r"], "\n", $raw_response);
		
		// 2. 移除多餘的空白行
		$cleaned = preg_replace('/\n{3,}/', "\n\n", $cleaned);
		
		// 3. 移除行首行尾的多餘空格
		$cleaned = preg_replace('/^[ \t]+|[ \t]+$/m', '', $cleaned);
		
		// 4. 不再移除 Markdown 標記，因為我們要渲染它們
		// 5. 不再移除數學公式符號
		
		return trim($cleaned);
	}
    
    // 格式化數學方程式
    private function formatMathEquations($text) {
        // 移除單獨的 $ 符號，但保留數學表達式
        $text = preg_replace('/\$(.*?)\$/', '$1', $text);
        
        // 將常見的數學表示轉換為更易讀的形式
        $replacements = [
            '/\\\frac\{(.*?)\}\{(.*?)\}/' => '($1)/($2)',
            '/\\\sqrt\{(.*?)\}/' => '√($1)',
            '/\\\times/' => '×',
            '/\\\div/' => '÷',
            '/\\\pm/' => '±',
            '/\\\neq/' => '≠',
            '/\\\leq/' => '≤',
            '/\\\geq/' => '≥',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }
    
    // 確保回應完整（檢查截斷）
    private function ensureResponseComplete($text) {
        // 檢查常見的截斷模式
        $truncation_indicators = [
            '句子突然結束',
            '最後一個字被切斷',
            '在明顯不完整的地方結束',
            '沒有結束標點符號'
        ];
        
        // 如果文字以逗號、連接詞等結束，可能被截斷
        $last_sentence = substr($text, -50);
        if (preg_match('/(?:但是|然而|例如|比如|另外|此外|最後|總之|因此|所以|因為|如果)$/', trim($last_sentence))) {
            $text .= "（回答可能因長度限制而被截斷，如需完整解答請提出更具體的問題）";
        }
        
        return $text;
    }
}
?>