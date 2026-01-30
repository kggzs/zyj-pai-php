/**
 * 内容渲染工具
 * 支持Plain、HTML和Markdown格式
 */

// 检查是否已加载Markdown和DOMPurify库
let markdownLoaded = false;
let dompurifyLoaded = false;

// 加载Markdown库
function loadMarkdownLibrary() {
    if (markdownLoaded || typeof marked !== 'undefined') {
        markdownLoaded = true;
        return Promise.resolve();
    }
    
    return new Promise((resolve, reject) => {
        // 优先使用本地文件
        const localPath = 'assets/js/libs/marked/marked.min.js';
        const cdnPath = 'https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js';
        
        const script = document.createElement('script');
        script.src = localPath;
        
        script.onload = () => {
            markdownLoaded = true;
            resolve();
        };
        
        script.onerror = () => {
            // 如果本地文件加载失败，尝试从 CDN 加载
            const cdnScript = document.createElement('script');
            cdnScript.src = cdnPath;
            cdnScript.onload = () => {
                markdownLoaded = true;
                resolve();
            };
            cdnScript.onerror = reject;
            document.head.appendChild(cdnScript);
        };
        
        document.head.appendChild(script);
    });
}

// 加载DOMPurify库
function loadDOMPurifyLibrary() {
    if (dompurifyLoaded || typeof DOMPurify !== 'undefined') {
        dompurifyLoaded = true;
        return Promise.resolve();
    }
    
    return new Promise((resolve, reject) => {
        // 优先使用本地文件
        const localPath = 'assets/js/libs/dompurify/purify.min.js';
        const cdnPath = 'https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js';
        
        const script = document.createElement('script');
        script.src = localPath;
        
        script.onload = () => {
            dompurifyLoaded = true;
            resolve();
        };
        
        script.onerror = () => {
            // 如果本地文件加载失败，尝试从 CDN 加载
            const cdnScript = document.createElement('script');
            cdnScript.src = cdnPath;
            cdnScript.onload = () => {
                dompurifyLoaded = true;
                resolve();
            };
            cdnScript.onerror = reject;
            document.head.appendChild(cdnScript);
        };
        
        document.head.appendChild(script);
    });
}

// 初始化库
async function initContentRenderer() {
    try {
        await Promise.all([
            loadMarkdownLibrary(),
            loadDOMPurifyLibrary()
        ]);
    } catch (error) {
        console.error('加载内容渲染库失败:', error);
    }
}

// 渲染内容
async function renderContent(content, contentType = 'auto') {
    if (!content) {
        return '';
    }
    
    // 确保库已加载
    await initContentRenderer();
    
    // 自动检测内容类型
    if (contentType === 'auto') {
        contentType = detectContentType(content);
    }
    
    let rendered = '';
    
    switch (contentType) {
        case 'html':
            // HTML内容，使用DOMPurify清理
            if (typeof DOMPurify !== 'undefined') {
                rendered = DOMPurify.sanitize(content, {
                    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
                                   'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'a', 'img', 'hr', 'div', 'span',
                                   'table', 'thead', 'tbody', 'tr', 'th', 'td'],
                    ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'style', 'target', 'rel']
                });
            } else {
                // 如果DOMPurify未加载，转义HTML
                rendered = escapeHtml(content);
            }
            break;
            
        case 'markdown':
            // Markdown内容，使用marked解析
            if (typeof marked !== 'undefined') {
                // 配置marked支持表格和GFM扩展
                const markedOptions = {
                    breaks: true,  // 支持换行
                    gfm: true,     // 启用GitHub Flavored Markdown（支持表格）
                };
                
                const markdownHtml = marked.parse(content, markedOptions);
                // 使用DOMPurify清理解析后的HTML
                if (typeof DOMPurify !== 'undefined') {
                    rendered = DOMPurify.sanitize(markdownHtml, {
                        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 
                                       'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'a', 'img', 'hr', 'div', 'span',
                                       'table', 'thead', 'tbody', 'tr', 'th', 'td'],
                        ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'style', 'target', 'rel']
                    });
                } else {
                    rendered = markdownHtml;
                }
            } else {
                // 如果marked未加载，转义内容
                rendered = escapeHtml(content).replace(/\n/g, '<br>');
            }
            break;
            
        case 'plain':
        default:
            // 纯文本，转义HTML并保留换行
            rendered = escapeHtml(content).replace(/\n/g, '<br>');
            break;
    }
    
    return rendered;
}

// 检测内容类型
function detectContentType(content) {
    if (!content) {
        return 'plain';
    }
    
    // 检测是否包含HTML标签
    if (/<[a-z][\s\S]*>/i.test(content)) {
        return 'html';
    }
    
    // 检测是否包含Markdown语法
    const markdownPatterns = [
        /^#{1,6}\s+/m,           // 标题
        /\*\*.*?\*\*/,           // 粗体
        /\*.*?\*/,               // 斜体
        /\[.*?\]\(.*?\)/,        // 链接
        /!\[.*?\]\(.*?\)/,       // 图片
        /^[-*+]\s+/m,            // 无序列表
        /^\d+\.\s+/m,            // 有序列表
        /^>\s+/m,                // 引用
        /```/,                   // 代码块
        /`[^`]+`/,               // 行内代码
        /\|.*\|/,                // 表格（包含|符号）
        /^\|.*\|$/m,             // 表格行
    ];
    
    for (const pattern of markdownPatterns) {
        if (pattern.test(content)) {
            return 'markdown';
        }
    }
    
    return 'plain';
}

// 转义HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 导出函数（如果在模块环境中）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        renderContent,
        detectContentType,
        initContentRenderer
    };
}

