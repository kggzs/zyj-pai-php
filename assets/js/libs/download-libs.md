# 下载第三方库文件

为了更好的安全性和性能，建议将以下库文件下载到本地：

## 需要下载的文件

1. **marked.js** (Markdown 解析库)
   - 下载地址：https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js
   - 保存路径：`assets/js/libs/marked/marked.min.js`

2. **DOMPurify** (HTML 清理库)
   - 下载地址：https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js
   - 保存路径：`assets/js/libs/dompurify/purify.min.js`

## 下载方法

### 方法1：使用浏览器下载
1. 打开上述 URL
2. 右键点击页面，选择"另存为"
3. 保存到对应的目录

### 方法2：使用 curl（Linux/Mac）
```bash
curl -o assets/js/libs/marked/marked.min.js https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js
curl -o assets/js/libs/dompurify/purify.min.js https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js
```

### 方法3：使用 PowerShell（Windows）
```powershell
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/marked@9.1.6/marked.min.js" -OutFile "assets/js/libs/marked/marked.min.js"
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js" -OutFile "assets/js/libs/dompurify/purify.min.js"
```

## 说明

- 如果本地文件不存在，代码会自动回退到使用 CDN
- 下载本地文件后，可以提升安全性和加载速度
- 建议在生产环境中使用本地文件
