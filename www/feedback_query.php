<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工单进度查询 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .query-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        .query-input-row {
            display: flex;
            gap: var(--spacing-md);
            align-items: flex-end;
        }
        .query-input-row .form-group {
            flex: 1;
        }
        .query-actions {
            display: flex;
            gap: var(--spacing-md);
            align-items: flex-end;
        }
        .result-area {
            display: none;
            margin-top: var(--spacing-xl);
        }
        .result-area.visible {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
        .status-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        .status-card .ticket-no {
            font-size: 1.125rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: 0.375rem 1rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-badge.status-pending {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .status-badge.status-processing {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .status-badge.status-resolved {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-badge.status-closed {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        .detail-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
        }
        .detail-card h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-lg);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        .detail-item.full-width {
            grid-column: 1 / -1;
        }
        .detail-label {
            font-size: 0.8125rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 0.9375rem;
            color: var(--text-primary);
            line-height: 1.6;
        }
        .screenshot-list {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        .screenshot-list img {
            max-width: 200px;
            max-height: 150px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .screenshot-list img:hover {
            transform: scale(1.05);
        }
        .timeline-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
        }
        .timeline-section h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
        }
        .timeline-container {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        .timeline-entry {
            position: relative;
            padding-bottom: var(--spacing-xl);
        }
        .timeline-entry:last-child {
            padding-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: -1.625rem;
            top: 0.25rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--bg-tertiary);
            border: 2px solid var(--text-muted);
        }
        .timeline-entry.highlighted .timeline-dot {
            background: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }
        .timeline-content {
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            padding: var(--spacing-md) var(--spacing-lg);
        }
        .timeline-entry.highlighted .timeline-content {
            border: 1px solid var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        .timeline-status-change {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xs);
            flex-wrap: wrap;
        }
        .timeline-status {
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 0.125rem 0.5rem;
            border-radius: var(--radius-sm);
        }
        .timeline-status.s-pending {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }
        .timeline-status.s-processing {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        .timeline-status.s-resolved {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        .timeline-status.s-closed {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
        }
        .timeline-arrow {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .timeline-note {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-xs);
        }
        .timeline-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        @media (max-width: 768px) {
            .query-input-row {
                flex-direction: column;
            }
            .query-actions {
                flex-direction: column;
                width: 100%;
            }
            .query-actions .btn {
                width: 100%;
                justify-content: center;
            }
            .detail-grid {
                grid-template-columns: 1fr;
            }
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
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php" class="active">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2>工单进度查询</h2>
                    <p>输入工单号查询处理进度</p>
                </div>
                <form id="queryForm" class="query-form">
                    <div class="query-input-row">
                        <div class="form-group">
                            <label for="ticket_no">工单号</label>
                            <input type="text" id="ticket_no" name="ticket_no" placeholder="请输入8位工单号" maxlength="8" pattern="[A-Za-z0-9]{8}" required>
                        </div>
                    </div>
                    <div class="query-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            查询
                        </button>
                        <a href="feedback.php" class="btn btn-secondary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 8H17M7 12H11M12 20H5C3.89543 20 3 19.1046 3 18V6C3 4.89543 3.89543 4 5 4H19C20.1046 4 21 4.89543 21 6V13M16 19L19 22L23 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            意见反馈
                        </a>
                    </div>
                </form>

                <div id="errorAlert" class="alert alert-error" style="display:none; margin-top: var(--spacing-lg);">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span id="errorMessage"></span>
                </div>

                <div id="resultArea" class="result-area">
                    <div class="status-card">
                        <span class="ticket-no">工单号：<span id="resultTicketNo"></span></span>
                        <span id="resultStatusBadge" class="status-badge"></span>
                    </div>

                    <div class="detail-card">
                        <h3>反馈详情</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">类型</span>
                                <span id="resultType" class="detail-value"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">联系方式</span>
                                <span id="resultContact" class="detail-value"></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">标题</span>
                                <span id="resultTitle" class="detail-value"></span>
                            </div>
                            <div class="detail-item full-width">
                                <span class="detail-label">描述</span>
                                <span id="resultDescription" class="detail-value"></span>
                            </div>
                            <div class="detail-item full-width" id="screenshotItem" style="display:none;">
                                <span class="detail-label">截图</span>
                                <div id="resultScreenshots" class="screenshot-list"></div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">提交时间</span>
                                <span id="resultCreatedAt" class="detail-value"></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">最后更新</span>
                                <span id="resultUpdatedAt" class="detail-value"></span>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-section">
                        <h3>处理时间线</h3>
                        <div id="resultTimeline" class="timeline-container"></div>
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
        var statusLabels = {
            pending: '待处理',
            processing: '处理中',
            resolved: '已解决',
            closed: '已关闭'
        };

        var queryForm = document.getElementById('queryForm');
        var errorAlert = document.getElementById('errorAlert');
        var errorMessage = document.getElementById('errorMessage');
        var resultArea = document.getElementById('resultArea');

        function showError(msg) {
            errorMessage.textContent = msg;
            errorAlert.style.display = 'flex';
            resultArea.classList.remove('visible');
        }

        function hideError() {
            errorAlert.style.display = 'none';
        }

        function renderTimeline(timeline) {
            var container = document.getElementById('resultTimeline');
            container.innerHTML = '';
            if (!timeline || timeline.length === 0) return;
            var lastIdx = timeline.length - 1;
            timeline.forEach(function(entry, idx) {
                var isHighlighted = idx === lastIdx;
                var div = document.createElement('div');
                div.className = 'timeline-entry' + (isHighlighted ? ' highlighted' : '');
                var fromLabel = statusLabels[entry.from_status] || entry.from_status;
                var toLabel = statusLabels[entry.to_status] || entry.to_status;
                var noteHtml = entry.note ? '<div class="timeline-note">' + escapeHtml(entry.note) + '</div>' : '';
                div.innerHTML =
                    '<div class="timeline-dot"></div>' +
                    '<div class="timeline-content">' +
                        '<div class="timeline-status-change">' +
                            '<span class="timeline-status s-' + entry.from_status + '">' + fromLabel + '</span>' +
                            '<span class="timeline-arrow">→</span>' +
                            '<span class="timeline-status s-' + entry.to_status + '">' + toLabel + '</span>' +
                        '</div>' +
                        noteHtml +
                        '<div class="timeline-time">' + escapeHtml(entry.created_at) + '</div>' +
                    '</div>';
                container.appendChild(div);
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            var d = document.createElement('div');
            d.textContent = text;
            return d.innerHTML;
        }

        queryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            hideError();

            var ticketNo = document.getElementById('ticket_no').value.trim();
            if (!ticketNo) {
                showError('请输入工单号');
                return;
            }
            if (ticketNo.length !== 8) {
                showError('工单号必须为8位');
                return;
            }
            if (!/^[A-Za-z0-9]{8}$/.test(ticketNo)) {
                showError('工单号只能包含字母和数字');
                return;
            }

            fetch('api_feedback_query.php?ticket_no=' + encodeURIComponent(ticketNo))
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    if (!json.success) {
                        showError(json.message || '查询失败');
                        return;
                    }
                    var data = json.data;
                    document.getElementById('resultTicketNo').textContent = data.ticket_no;

                    var badge = document.getElementById('resultStatusBadge');
                    badge.className = 'status-badge status-' + data.status;
                    badge.textContent = statusLabels[data.status] || data.status;

                    document.getElementById('resultType').textContent = data.type || '';
                    document.getElementById('resultTitle').textContent = data.title || '';
                    document.getElementById('resultDescription').textContent = data.description || '';
                    document.getElementById('resultContact').textContent = data.contact || '';
                    document.getElementById('resultCreatedAt').textContent = data.created_at || '';
                    document.getElementById('resultUpdatedAt').textContent = data.updated_at || '';

                    var screenshotItem = document.getElementById('screenshotItem');
                    var screenshotsEl = document.getElementById('resultScreenshots');
                    screenshotsEl.innerHTML = '';
                    if (data.screenshots && Array.isArray(data.screenshots) && data.screenshots.length > 0) {
                        screenshotItem.style.display = '';
                        data.screenshots.forEach(function(src) {
                            var img = document.createElement('img');
                            img.src = src;
                            img.alt = '截图';
                            screenshotsEl.appendChild(img);
                        });
                    } else {
                        screenshotItem.style.display = 'none';
                    }

                    renderTimeline(data.timeline);
                    resultArea.classList.add('visible');
                })
                .catch(function() {
                    showError('网络请求失败，请稍后重试');
                });
        });
    })();
    </script>
</body>
</html>
