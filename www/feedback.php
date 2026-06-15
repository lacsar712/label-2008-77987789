<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>意见反馈 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .file-input-wrapper {
            position: relative;
        }
        .file-input-wrapper input[type="file"] {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 1rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-input-wrapper input[type="file"]:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .file-hint {
            color: var(--text-muted);
            font-size: 0.8125rem;
            margin-top: var(--spacing-xs);
        }
        .preview-area {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            margin-top: var(--spacing-sm);
        }
        .preview-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .preview-remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 12px;
            line-height: 20px;
            text-align: center;
            cursor: pointer;
            padding: 0;
        }
        .success-panel {
            text-align: center;
            padding: var(--spacing-2xl) var(--spacing-xl);
        }
        .success-icon {
            width: 64px;
            height: 64px;
            color: var(--success-color);
            margin-bottom: var(--spacing-lg);
        }
        .success-panel h3 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-lg);
        }
        .ticket-box {
            background: var(--bg-tertiary);
            border: 2px solid var(--primary-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            display: inline-block;
            margin-bottom: var(--spacing-xl);
        }
        .ticket-box .ticket-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-xs);
        }
        .ticket-box .ticket-number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: 2px;
        }
        .success-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: center;
            flex-wrap: wrap;
        }
        .alert-error {
            display: none;
        }
        .alert-error.show {
            display: flex;
        }
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1>公告信息管理系统</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">首页</a></li>
                <li><a href="add_notice.php">添加公告</a></li>
                <li><a href="search_notice.php">查询公告</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="feedback.php" class="active">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2>意见反馈</h2>
                    <p>提交您的反馈意见，我们会尽快处理</p>
                </div>

                <div class="alert alert-error" id="formError">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span id="errorMessage"></span>
                </div>

                <form id="feedbackForm" class="notice-form">
                    <div class="form-group">
                        <label for="type">类型 <span class="required">*</span></label>
                        <select id="type" name="type" required>
                            <option value="">请选择反馈类型</option>
                            <option value="bug">问题反馈</option>
                            <option value="feature">功能建议</option>
                            <option value="complaint">投诉</option>
                            <option value="suggestion">建议</option>
                            <option value="other">其他</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="title">标题 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required placeholder="请输入反馈标题">
                    </div>

                    <div class="form-group">
                        <label for="description">详细描述 <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="8" required placeholder="请详细描述您遇到的问题或建议"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="contact">联系方式</label>
                        <input type="text" id="contact" name="contact" placeholder="手机号/邮箱/微信等">
                    </div>

                    <div class="form-group">
                        <label for="screenshots">截图</label>
                        <div class="file-input-wrapper">
                            <input type="file" id="screenshots" name="screenshots" accept="image/*" multiple>
                        </div>
                        <div class="preview-area" id="previewArea"></div>
                        <p class="file-hint">最多上传3张截图，单张不超过5MB</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            提交反馈
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4V9H4.582M4.582 9C5.83828 5.65131 9.09693 3.26816 12.8844 3.01819C16.6718 2.76822 20.2261 4.70364 21.9683 7.91331M20 16V11H19.418M19.418 11C18.1617 14.3487 14.9031 16.7318 11.1156 16.9818C7.32818 17.2318 3.77386 15.2964 2.03172 12.0867" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            重置
                        </button>
                    </div>
                </form>

                <div class="success-panel" id="successPanel" style="display:none;">
                    <svg class="success-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>反馈提交成功！</h3>
                    <div class="ticket-box">
                        <div class="ticket-label">工单编号</div>
                        <div class="ticket-number" id="ticketNumber"></div>
                    </div>
                    <div class="success-actions">
                        <a href="feedback_query.php" class="btn btn-primary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            查询工单进度
                        </a>
                        <button type="button" class="btn btn-secondary" id="continueBtn">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            继续提交
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
    (function() {
        var fileInput = document.getElementById('screenshots');
        var previewArea = document.getElementById('previewArea');
        var feedbackForm = document.getElementById('feedbackForm');
        var submitBtn = document.getElementById('submitBtn');
        var formError = document.getElementById('formError');
        var errorMessage = document.getElementById('errorMessage');
        var successPanel = document.getElementById('successPanel');
        var ticketNumber = document.getElementById('ticketNumber');
        var continueBtn = document.getElementById('continueBtn');
        var selectedFiles = [];

        fileInput.addEventListener('change', function(e) {
            var files = Array.from(e.target.files);
            var totalFiles = selectedFiles.length + files.length;

            if (totalFiles > 3) {
                showError('最多只能上传3张截图');
                fileInput.value = '';
                return;
            }

            for (var i = 0; i < files.length; i++) {
                if (!files[i].type.startsWith('image/')) {
                    showError('只能上传图片文件');
                    fileInput.value = '';
                    return;
                }
                if (files[i].size > 5 * 1024 * 1024) {
                    showError('单张截图不能超过5MB');
                    fileInput.value = '';
                    return;
                }
            }

            hideError();
            files.forEach(function(file) {
                selectedFiles.push(file);
                var reader = new FileReader();
                reader.onload = function(ev) {
                    var item = document.createElement('div');
                    item.className = 'preview-item';
                    item.dataset.index = selectedFiles.indexOf(file);

                    var img = document.createElement('img');
                    img.src = ev.target.result;

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'preview-remove';
                    removeBtn.textContent = '×';
                    removeBtn.addEventListener('click', function() {
                        var idx = parseInt(item.dataset.index, 10);
                        selectedFiles.splice(idx, 1);
                        refreshPreviews();
                    });

                    item.appendChild(img);
                    item.appendChild(removeBtn);
                    previewArea.appendChild(item);
                };
                reader.readAsDataURL(file);
            });

            fileInput.value = '';
        });

        function refreshPreviews() {
            previewArea.innerHTML = '';
            selectedFiles.forEach(function(file, index) {
                var reader = new FileReader();
                reader.onload = function(ev) {
                    var item = document.createElement('div');
                    item.className = 'preview-item';
                    item.dataset.index = index;

                    var img = document.createElement('img');
                    img.src = ev.target.result;

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'preview-remove';
                    removeBtn.textContent = '×';
                    removeBtn.addEventListener('click', function() {
                        selectedFiles.splice(index, 1);
                        refreshPreviews();
                    });

                    item.appendChild(img);
                    item.appendChild(removeBtn);
                    previewArea.appendChild(item);
                };
                reader.readAsDataURL(file);
            });
        }

        feedbackForm.addEventListener('submit', function(e) {
            e.preventDefault();

            hideError();

            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '提交中...';

            var screenshotUrls = [];
            var uploadPromises = selectedFiles.map(function(file) {
                var fd = new FormData();
                fd.append('screenshot', file);
                return fetch('api_upload.php', {
                    method: 'POST',
                    body: fd
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (data.success) {
                        screenshotUrls.push(data.url);
                    } else {
                        throw new Error(data.message || '上传失败');
                    }
                });
            });

            Promise.all(uploadPromises).then(function() {
                var payload = {
                    type: document.getElementById('type').value,
                    title: document.getElementById('title').value,
                    description: document.getElementById('description').value,
                    contact: document.getElementById('contact').value,
                    screenshots: screenshotUrls
                };

                return fetch('api_feedback_submit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
            }).then(function(res) {
                return res.json();
            }).then(function(data) {
                if (data.success) {
                    feedbackForm.style.display = 'none';
                    successPanel.style.display = 'block';
                    ticketNumber.textContent = data.ticket_no;
                } else {
                    showError(data.message || '提交失败，请稍后重试');
                }
            }).catch(function(err) {
                showError(err.message || '网络错误，请稍后重试');
            }).finally(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.originalText;
            });
        });

        continueBtn.addEventListener('click', function() {
            feedbackForm.reset();
            selectedFiles = [];
            previewArea.innerHTML = '';
            feedbackForm.style.display = '';
            successPanel.style.display = 'none';
            ticketNumber.textContent = '';
            hideError();
        });

        function showError(msg) {
            errorMessage.textContent = msg;
            formError.classList.add('show');
        }

        function hideError() {
            formError.classList.remove('show');
            errorMessage.textContent = '';
        }
    })();
    </script>
</body>
</html>
