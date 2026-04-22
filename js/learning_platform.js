// js/learning_platform.js - 完全修復版本
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM 加載完成，初始化學習平台...');
    
    // 自動聚焦到問題輸入框
    const questionInput = document.getElementById('question');
    if (questionInput) {
        questionInput.focus();
    }
    
    // 配置 marked（如果可用）
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true,
            sanitize: false
        });
        console.log('Marked 配置完成');
    }
    
    // 點擊歷史紀錄顯示完整內容
    const historyItems = document.querySelectorAll('.history-item');
    historyItems.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            
            // 獲取數據屬性
            const question = this.getAttribute('data-question');
            const subjectId = this.getAttribute('data-subject-id');
            const grade = this.getAttribute('data-grade');
            const questionType = this.getAttribute('data-question-type');
            const aiResponse = this.getAttribute('data-ai-response');
            const knowledgePoints = this.getAttribute('data-knowledge-points');
            const followUpQuestions = this.getAttribute('data-follow-up-questions');
            const confidence = this.getAttribute('data-confidence');
            const subjectName = this.getAttribute('data-subject-name');
            const createdAt = this.getAttribute('data-created-at');
            
            console.log('點擊歷史紀錄:', subjectName);
            
            // 填充表單
            if (document.getElementById('question')) {
                document.getElementById('question').value = question || '';
            }
            if (document.getElementById('subject_id')) {
                document.getElementById('subject_id').value = subjectId || '';
            }
            if (document.getElementById('grade')) {
                document.getElementById('grade').value = grade || '';
            }
            if (document.getElementById('question_type')) {
                document.getElementById('question_type').value = questionType || 'concept';
            }
            
            // 顯示歷史紀錄內容
            displayHistoryContent({
                question: question,
                ai_response: aiResponse,
                knowledge_points: knowledgePoints ? JSON.parse(knowledgePoints) : null,
                follow_up_questions: followUpQuestions ? JSON.parse(followUpQuestions) : [],
                confidence_score: parseFloat(confidence) || 0.7,
                subject_name: subjectName,
                created_at: createdAt
            });
            
            // 滾動到結果區域
            const resultSection = document.querySelector('.result-section');
            if (resultSection) {
                resultSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
            
            // 添加活躍狀態到點擊的歷史項目
            historyItems.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // 添加範例問題點擊功能
    const exampleItems = document.querySelectorAll('.example-questions li');
    exampleItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.style.color = '#667eea';
        item.addEventListener('click', function() {
            if (document.getElementById('question')) {
                document.getElementById('question').value = this.textContent;
                document.getElementById('question').focus();
            }
        });
    });
    
    // 清除顯示按鈕功能
    const clearBtn = document.querySelector('.btn-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', clearHistoryDisplay);
    }
    
    console.log('學習平台初始化完成');
});

// 重新渲染數學公式
function renderMathJax() {
    if (window.MathJax && typeof MathJax.typesetPromise === 'function') {
        console.log('開始渲染數學公式...');
        return MathJax.typesetPromise().then(() => {
            console.log('數學公式渲染完成');
        }).catch(error => {
            console.warn('數學公式渲染失敗:', error);
        });
    } else {
        console.log('MathJax 不可用，跳過數學公式渲染');
        return Promise.resolve();
    }
}

// 顯示歷史紀錄內容的函數
function displayHistoryContent(data) {
    const resultSection = document.querySelector('.result-section');
    if (!resultSection) {
        console.error('找不到結果區域');
        return;
    }
    
    console.log('顯示歷史內容:', data.subject_name);
    
    // 構建 HTML 內容
    let html = `
        <div class="ai-response-card">
            <div class="response-header">
                <h3>📚 歷史記錄 - ${escapeHtml(data.subject_name || '未知學科')}</h3>
                <div>
                    <span class="confidence-badge">信心度: ${Math.round((data.confidence_score || 0.7) * 100)}%</span>
                    <span class="history-date">${data.created_at || ''}</span>
                </div>
            </div>
            
            <div class="original-question">
                <h4>原始問題：</h4>
                <p>${escapeHtml(data.question || '')}</p>
            </div>
            
            <div class="response-content">
                ${formatAIResponseForJS(data.ai_response || '')}
            </div>
    `;
    
    // 添加知識點分析
    if (data.knowledge_points && data.knowledge_points.knowledge_points) {
        html += `
            <div class="knowledge-points">
                <h4>📚 涉及知識點分析</h4>
                <div class="knowledge-details">
                    <p><strong>主要主題：</strong> ${escapeHtml(data.knowledge_points.main_topic || '待分析')}</p>
                    <p><strong>難度等級：</strong> 
                        <span class="difficulty-badge ${data.knowledge_points.difficulty_level || 'basic'}">
                            ${getDifficultyText(data.knowledge_points.difficulty_level || 'basic')}
                        </span>
                    </p>
                    <div class="points-grid">
        `;
        
        data.knowledge_points.knowledge_points.forEach(point => {
            html += `<span class="knowledge-tag">${escapeHtml(point)}</span>`;
        });
        
        html += `
                    </div>
                </div>
            </div>
        `;
    }
    
    // 添加延伸問題
    if (data.follow_up_questions && data.follow_up_questions.length > 0) {
        html += `
            <div class="follow-up-questions">
                <h4>💡 延伸思考問題</h4>
                <ul>
        `;
        
        data.follow_up_questions.forEach((question, index) => {
            html += `
                <li>
                    <span class="question-number">${index + 1}.</span>
                    ${formatAIResponseForJS(question)}
                </li>
            `;
        });
        
        html += `
                </ul>
            </div>
        `;
    }
    
    html += `</div>`; // 結束 ai-response-card
    
    // 更新結果區域
    resultSection.innerHTML = html;
    resultSection.style.display = 'block';
    
    // 渲染數學公式
    setTimeout(() => {
        renderMathJax();
    }, 300);
}

// 清除歷史顯示
function clearHistoryDisplay() {
    const resultSection = document.querySelector('.result-section');
    if (resultSection) {
        resultSection.style.display = 'none';
        resultSection.innerHTML = '';
    }
    
    // 清除表單
    if (document.getElementById('question')) {
        document.getElementById('question').value = '';
    }
    if (document.getElementById('subject_id')) {
        document.getElementById('subject_id').value = '';
    }
    if (document.getElementById('grade')) {
        document.getElementById('grade').value = '';
    }
    if (document.getElementById('question_type')) {
        document.getElementById('question_type').value = 'concept';
    }
    
    // 移除活躍狀態
    document.querySelectorAll('.history-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // 聚焦到問題輸入框
    if (document.getElementById('question')) {
        document.getElementById('question').focus();
    }
}

// 輔助函數：HTML 轉義
function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return unsafe
         .toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// 輔助函數：難度等級轉文字
function getDifficultyText(level) {
    const levels = {
        'basic': '基礎',
        'intermediate': '中等',
        'advanced': '進階'
    };
    return levels[level] || '基礎';
}

// 改進的 Markdown 解析器
function enhancedMarkdownParser(text) {
    if (!text) return '';
    
    let html = text;
    
    // 處理標題
    html = html.replace(/^##### (.*$)/gim, '<h5>$1</h5>');
    html = html.replace(/^#### (.*$)/gim, '<h4>$1</h4>');
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
    
    // 處理粗體和斜體
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
    
    // 處理代碼塊
    html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
    html = html.replace(/`(.*?)`/g, '<code>$1</code>');
    
    // 處理引用
    html = html.replace(/^> (.*$)/gim, '<blockquote>$1</blockquote>');
    
    // 處理水平線
    html = html.replace(/^-{3,}$/gim, '<hr>');
    
    // 處理列表 - 有序列表
    const olRegex = /(\d+\.\s+.*(\n\d+\.\s+.*)*)/g;
    html = html.replace(olRegex, (match) => {
        const items = match.split('\n').filter(item => item.trim());
        const listItems = items.map(item => 
            `<li>${item.replace(/^\d+\.\s+/, '')}</li>`
        ).join('');
        return `<ol>${listItems}</ol>`;
    });
    
    // 處理列表 - 無序列表
    const ulRegex = /(-\s+.*(\n-\s+.*)*)/g;
    html = html.replace(ulRegex, (match) => {
        const items = match.split('\n').filter(item => item.trim());
        const listItems = items.map(item => 
            `<li>${item.replace(/^-\s+/, '')}</li>`
        ).join('');
        return `<ul>${listItems}</ul>`;
    });
    
    // 處理段落和換行
    const lines = html.split('\n');
    let inList = false;
    let output = '';
    
    lines.forEach((line, index) => {
        const trimmed = line.trim();
        
        if (!trimmed) {
            // 空行
            if (inList) {
                output += '</ul>';
                inList = false;
            }
            output += '<div class="paragraph-gap"></div>';
        } else if (trimmed.startsWith('<') && trimmed.endsWith('>')) {
            // 已經是 HTML 標籤
            output += trimmed;
        } else if (trimmed.startsWith('- ') || /^\d+\./.test(trimmed)) {
            // 列表項
            if (!inList) {
                output += '<ul>';
                inList = true;
            }
            const content = trimmed.replace(/^(- |\d+\.\s+)/, '');
            output += `<li>${content}</li>`;
        } else {
            // 普通段落
            if (inList) {
                output += '</ul>';
                inList = false;
            }
            output += `<p>${trimmed}</p>`;
        }
    });
    
    // 確保列表正確關閉
    if (inList) {
        output += '</ul>';
    }
    
    return output;
}
//數學表達式
// ---- 修正版 fixMathFormulas ----
function fixMathFormulas(text) {
    if (!text) return { text: '', mathBlocks: [] };

    console.log('原始數學公式:', text);

    let working = text;
    const mathBlocks = [];

    // 使用不會被 Markdown 解讀的 placeholder
    function addMathBlock(mathContent) {
        const idx = mathBlocks.length;
        const ph = `@@MATH_BLOCK_${idx}@@`;
        mathBlocks.push(mathContent);
        return ph;
    }

    // 先保護已存在的 math environment（$$...$$, $...$, \(...\), \[...\]）
    working = working.replace(/\$\$([\s\S]*?)\$\$/g, (m) => addMathBlock(m));
    working = working.replace(/\$([^$\n]+)\$/g, (m) => addMathBlock(m));
    working = working.replace(/\\\(([\s\S]*?)\\\)/g, (m) => addMathBlock(m));
    working = working.replace(/\\\[([\s\S]*?)\\\]/g, (m) => addMathBlock(m));

    // 處理 90^\circ 與 90 \circ
    // case A: 數字 ^ \circ
    working = working.replace(/(\d+)\s*\^\s*\\circ\b/g, (m, deg) => {
        console.log('找到角度 (caret):', m);
        return addMathBlock(`$${deg}^\\circ$`); // mathContent 中用單一反斜線
    });
    // case B: 數字 \circ（沒有 caret）
    working = working.replace(/(\d+)\s*\\circ\b/g, (m, deg) => {
        console.log('找到角度 (no caret):', m);
        return addMathBlock(`$${deg}^\\circ$`);
    });

    // 處理右箭頭 \Rightarrow
    working = working.replace(/\\Rightarrow\b/g, (m) => {
        console.log('找到右箭頭:', m);
        return addMathBlock(`$\\Rightarrow$`);
    });

    // 字母上標 a^2
    working = working.replace(/([a-zA-Z])\^(\d+)/g, (m, p1, p2) => {
        console.log('找到字母上標:', m);
        return addMathBlock(`$${p1}^{${p2}}$`);
    });

    // 數字上標 3^2
    working = working.replace(/(\d+)\^(\d+)/g, (m, p1, p2) => {
        console.log('找到數字上標:', m);
        return addMathBlock(`$${p1}^{${p2}}$`);
    });

    // 根號 \sqrt{...}
    working = working.replace(/\\sqrt\{([^}]+)\}/g, (m, content) => {
        console.log('找到根號:', m);
        return addMathBlock(`$\\sqrt{${content}}$`);
    });

    // 常見符號清單（字串中用單一反斜線）
    const mathSymbols = [
        '\\leq', '\\geq', '\\neq', '\\approx', '\\pm', '\\mp',
        '\\times', '\\div', '\\cdot', '\\infty', '\\pi', '\\alpha',
        '\\beta', '\\gamma', '\\delta', '\\theta', '\\lambda',
        '\\leftarrow', '\\rightarrow', '\\leftrightarrow', '\\Leftarrow', '\\Rightarrow'
    ];

    mathSymbols.forEach(symbol => {
        // 建立正規表達式（把字串中的 '\' 轉成 Regex 可接受的 '\\\\'）
        const regex = new RegExp(symbol.replace(/\\/g, '\\\\'), 'g');
        working = working.replace(regex, (m) => {
            console.log('找到數學符號:', m);
            return addMathBlock(`$${m}$`); // m 本身已包含單一反斜線
        });
    });

    // 分數 \frac{a}{b}
    working = working.replace(/\\frac\{([^}]+)\}\{([^}]+)\}/g, (m, num, den) => {
        console.log('找到分數:', m);
        return addMathBlock(`$\\frac{${num}}{${den}}$`);
    });

    console.log('placeholder 版文字:', working);
    return { text: working, mathBlocks: mathBlocks };
}

// ---- 修正版 formatAIResponseForJS ----
function formatAIResponseForJS(response) {
    if (!response) return '';

    console.log('開始格式化 AI 回應');

    const mathResult = fixMathFormulas(response);
    let parsedHtml;

    if (typeof marked === 'undefined') {
        console.log('Marked 不可用，使用備用解析器');
        parsedHtml = enhancedMarkdownParser(mathResult.text);
    } else {
        try {
            parsedHtml = marked.parse(mathResult.text);
            console.log('Markdown 渲染成功');
        } catch (error) {
            console.error('Markdown 解析錯誤，使用備用解析器:', error);
            parsedHtml = enhancedMarkdownParser(mathResult.text);
        }
    }

    // 還原 placeholder -> mathContent（使用 split/join 確保 global 替換）
    if (mathResult.mathBlocks && mathResult.mathBlocks.length > 0) {
        mathResult.mathBlocks.forEach((mathContent, idx) => {
            const ph = `@@MATH_BLOCK_${idx}@@`;
            parsedHtml = parsedHtml.split(ph).join(mathContent);
        });
    }

    console.log('格式化完成，placeholder 已還原');
    return `<div class="markdown-content">${parsedHtml}</div>`;
}
//
// 新增：渲染 PHP 生成的 AI 回應內容
function renderPHPGeneratedContent() {
    console.log('開始渲染 PHP 生成的內容...');
    
    // 渲染 AI 老師解答的主要內容
    const aiResponseElements = document.querySelectorAll('.markdown-content[data-raw-content]');
    aiResponseElements.forEach(element => {
        const rawContent = element.getAttribute('data-raw-content');
        if (rawContent) {
            console.log('渲染 AI 回應內容');
            const formattedContent = formatAIResponseForJS(rawContent);
            element.innerHTML = formattedContent;
        }
    });
    
    // 渲染延伸思考問題
    const followUpElements = document.querySelectorAll('.markdown-inline[data-raw-content]');
    followUpElements.forEach(element => {
        const rawContent = element.getAttribute('data-raw-content');
        if (rawContent) {
            console.log('渲染延伸問題:', rawContent);
            const formattedContent = formatAIResponseForJS(rawContent);
            element.innerHTML = formattedContent;
        }
    });
    
    // 渲染數學公式
    setTimeout(() => {
        renderMathJax();
    }, 500);
}

// 在 DOMContentLoaded 事件中添加對 PHP 生成內容的渲染
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM 加載完成，初始化學習平台...');
    
    // 自動聚焦到問題輸入框
    const questionInput = document.getElementById('question');
    if (questionInput) {
        questionInput.focus();
    }
    
    // 配置 marked（如果可用）
    if (typeof marked !== 'undefined') {
        marked.setOptions({
            breaks: true,
            gfm: true,
            sanitize: false
        });
        console.log('Marked 配置完成');
    }
    
    // 渲染 PHP 生成的內容（新增這行）
    renderPHPGeneratedContent();
    
    // 點擊歷史紀錄顯示完整內容
    const historyItems = document.querySelectorAll('.history-item');
    historyItems.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            
            // 獲取數據屬性
            const question = this.getAttribute('data-question');
            const subjectId = this.getAttribute('data-subject-id');
            const grade = this.getAttribute('data-grade');
            const questionType = this.getAttribute('data-question-type');
            const aiResponse = this.getAttribute('data-ai-response');
            const knowledgePoints = this.getAttribute('data-knowledge-points');
            const followUpQuestions = this.getAttribute('data-follow-up-questions');
            const confidence = this.getAttribute('data-confidence');
            const subjectName = this.getAttribute('data-subject-name');
            const createdAt = this.getAttribute('data-created-at');
            
            console.log('點擊歷史紀錄:', subjectName);
            
            // 填充表單
            if (document.getElementById('question')) {
                document.getElementById('question').value = question || '';
            }
            if (document.getElementById('subject_id')) {
                document.getElementById('subject_id').value = subjectId || '';
            }
            if (document.getElementById('grade')) {
                document.getElementById('grade').value = grade || '';
            }
            if (document.getElementById('question_type')) {
                document.getElementById('question_type').value = questionType || 'concept';
            }
            
            // 顯示歷史紀錄內容
            displayHistoryContent({
                question: question,
                ai_response: aiResponse,
                knowledge_points: knowledgePoints ? JSON.parse(knowledgePoints) : null,
                follow_up_questions: followUpQuestions ? JSON.parse(followUpQuestions) : [],
                confidence_score: parseFloat(confidence) || 0.7,
                subject_name: subjectName,
                created_at: createdAt
            });
            
            // 滾動到結果區域
            const resultSection = document.querySelector('.result-section');
            if (resultSection) {
                resultSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
            
            // 添加活躍狀態到點擊的歷史項目
            historyItems.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // 添加範例問題點擊功能
    const exampleItems = document.querySelectorAll('.example-questions li');
    exampleItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.style.color = '#667eea';
        item.addEventListener('click', function() {
            if (document.getElementById('question')) {
                document.getElementById('question').value = this.textContent;
                document.getElementById('question').focus();
            }
        });
    });
    
    // 清除顯示按鈕功能
    const clearBtn = document.querySelector('.btn-clear');
    if (clearBtn) {
        clearBtn.addEventListener('click', clearHistoryDisplay);
    }
    
    console.log('學習平台初始化完成');
});
