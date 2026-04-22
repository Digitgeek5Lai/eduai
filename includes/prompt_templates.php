<?php
class PromptTemplates {
    
    public static function getLearningPrompt($subject, $question, $grade = null, $question_type = 'concept') {
        $base_prompt = "你是一位經驗豐富的{$subject}老師，請用繁體中文回答學生的問題。";
        
        if ($grade) {
            $base_prompt .= " 提問的學生是{$grade}年級，請用適合該年級的理解程度來解釋。";
        }
        
        $type_prompts = [
            'concept' => "請深入淺出地解釋相關概念，並提供生活中的例子幫助理解。",
            'calculation' => "請逐步展示計算過程，解釋每一步的原理和意義。",
            'proof' => "請詳細說明證明思路，並解釋背後的邏輯推理。",
            'application' => "請說明這個知識點的實際應用場景，並舉例說明。"
        ];
        
        $type_prompt = $type_prompts[$question_type] ?? $type_prompts['concept'];
        
        // 修正數學公式要求
        $format_requirements = "

【重要格式要求】
請使用 Markdown 語法來格式化你的回答，特別是數學公式：

1. 【數學公式必須用 $ 符號包圍】：
   - 行內公式：$90^\\circ$ 或 $a \\times a = a^2$
   - 獨立公式：$$x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}$$

2. 章節標題使用 ## 標題
3. 列表使用 - 或 1. 2. 3.
4. 強調使用 **粗體** 或 *斜體*
5. 段落之間用空行分隔，但不要過多空行
6. 請確保回答完整，不要中途截斷

請按照以下結構組織你的回答：

## 核心概念
先用一兩句話總結核心概念

## 詳細解釋
深入講解相關知識點

## 範例說明
提供具體的例子或應用

## 常見誤區
提醒學生容易混淆的地方

## 延伸思考
提出相關的思考問題

學生的問題：{$question}
";
        
        return $base_prompt . $type_prompt . $format_requirements;
    }
    
    public static function getKnowledgeDetectionPrompt($question, $subject) {
        return "分析以下{$subject}問題涉及的主要知識點，請用簡潔的JSON格式返回：

{
  \"main_topic\": \"主要主題\",
  \"knowledge_points\": [\"知識點1\", \"知識點2\"],
  \"difficulty_level\": \"basic|intermediate|advanced\"
}

問題：{$question}

請只返回JSON格式的數據，不要其他文字。";
    }
    
    public static function getFollowUpQuestionsPrompt($original_question, $ai_response) {
        return "基於以下問答，生成3個相關的延伸問題來幫助學生深入學習：
        
原始問題：{$original_question}
AI回答：{$ai_response}

請用JSON數組格式返回延伸問題，例如：[\"問題1\", \"問題2\", \"問題3\"]

請只返回JSON格式的數據，不要其他文字。";
    }
}
?>