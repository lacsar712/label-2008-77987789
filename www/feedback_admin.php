<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>反馈管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .drawer-overlay.drawer-open {
            opacity: 1;
            pointer-events: auto;
        }
        .drawer {
            position: fixed;
            right: 0;
            top: 0;
            width: 600px;
            height: 100vh;
            background: var(--bg-secondary);
            border-left: 1px solid var(--border-color);
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .drawer.drawer-open {
            transform: translateX(0);
        }
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-lg) var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
            flex-shrink: 0;
        }
        .drawer-header h2 {
            font-size: 1.25rem;
            color: var(--text-primary);
        }
        .drawer-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .drawer-close:hover {
            background: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--primary-color);
        }
        .drawer-close svg {
            width: 18px;
            height: 18px;
        }
        .drawer-body {
            padding: var(--spacing-xl);
            flex: 1;
            overflow-y: auto;
        }
        .drawer-section {
            margin-bottom: var(--spacing-xl);
        }
        .drawer-section-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--border-color);
        }
        .detail-row {
            display: flex;
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
        }
        .detail-label {
            color: var(--text-muted);
            width: 80px;
            flex-shrink: 0;
        }
        .detail-value {
            color: var(--text-secondary);
            flex: 1;
            word-break: break-all;
        }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.pending {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        .status-badge.processing {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .status-badge.resolved {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .status-badge.closed {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
        }
        .type-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .type-badge.bug {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .type-badge.feature {
            background: rgba(99, 102, 241, 0.2);
            color: #6366f1;
        }
        .type-badge.complaint {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .type-badge.suggestion {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .type-badge.other {
            background: rgba(148, 163, 184, 0.2);
            color: #94a3b8;
        }
        .status-select-wrapper {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .status-select-wrapper select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            flex: 1;
            font-family: inherit;
        }
        .status-select-wrapper select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .note-display {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            color: var(--text-secondary);
            font-size: 0.875rem;
            white-space: pre-wrap;
            word-break: break-all;
            margin-bottom: var(--spacing-md);
            min-height: 60px;
        }
        .note-input-group {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .note-input-group textarea {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-family: inherit;
            min-height: 80px;
            resize: vertical;
        }
        .note-input-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .note-input-group .btn {
            align-self: flex-end;
        }
        .timeline {
            position: relative;
            padding-left: 24px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 4px;
            bottom: 4px;
            width: 2px;
            background: var(--border-color);
        }
        .timeline-item {
            position: relative;
            padding-bottom: var(--spacing-lg);
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-dot {
            position: absolute;
            left: -20px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid var(--bg-secondary);
        }
        .timeline-content {
            font-size: 0.875rem;
        }
        .timeline-change {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 2px;
        }
        .timeline-note {
            color: var(--text-secondary);
            margin-bottom: 2px;
        }
        .timeline-time {
            color: var(--text-muted);
            font-size: 0.75rem;
        }
        .screenshots-list {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
        }
        .screenshots-list img {
            max-width: 120px;
            max-height: 90px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            cursor: pointer;
            object-fit: cover;
        }
        .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
        }
        .pagination-wrap .page-link,
        .pagination-wrap .page-number {
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .drawer {
                width: 100%;
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
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php" class="active">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="rating_admin.php">评价管理</a></li>
                <li><a href="rating_summary.php">评价汇总</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <h2>反馈管理</h2>
                <form id="filterForm" class="search-form" onsubmit="return false;">
                    <div class="search-fields">
                        <div class="search-field">
                            <label for="filter_status">状态</label>
                            <select id="filter_status">
                                <option value="">全部</option>
                                <option value="pending">待处理</option>
                                <option value="processing">处理中</option>
                                <option value="resolved">已解决</option>
                                <option value="closed">已关闭</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="filter_type">类型</label>
                            <select id="filter_type">
                                <option value="">全部</option>
                                <option value="bug">问题反馈</option>
                                <option value="feature">功能建议</option>
                                <option value="complaint">投诉</option>
                                <option value="suggestion">建议</option>
                                <option value="other">其他</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="filter_keyword">关键词</label>
                            <input type="text" id="filter_keyword" placeholder="搜索标题或描述">
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-primary" onclick="loadFeedbacks(1)">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            搜索
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            重置
                        </button>
                    </div>
                </form>
            </div>

            <div class="results-info">
                <p id="resultsInfo">共找到 <strong>0</strong> 条反馈，当前第 <strong>1</strong> / <strong>1</strong> 页</p>
            </div>

            <div class="notices-table-container">
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th width="10%">工单号</th>
                            <th width="10%">类型</th>
                            <th width="25%">标题</th>
                            <th width="10%">状态</th>
                            <th width="13%">提交时间</th>
                            <th width="13%">最后处理</th>
                            <th width="8%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                    </tbody>
                </table>
                <div id="noResults" class="no-results" style="display:none;">
                    <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>没有找到符合条件的反馈</p>
                </div>
            </div>

            <div id="paginationWrap" class="pagination-wrap"></div>
        </div>
    </div>

    <div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>
    <div class="drawer" id="drawer">
        <div class="drawer-header">
            <h2>反馈详情</h2>
            <button class="drawer-close" onclick="closeDrawer()">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="drawer-body" id="drawerBody"></div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
        var currentDetailId = null;

        var typeLabels = {
            bug: '问题反馈',
            feature: '功能建议',
            complaint: '投诉',
            suggestion: '建议',
            other: '其他'
        };

        var statusLabels = {
            pending: '待处理',
            processing: '处理中',
            resolved: '已解决',
            closed: '已关闭'
        };

        function loadFeedbacks(page) {
            var status = document.getElementById('filter_status').value;
            var type = document.getElementById('filter_type').value;
            var keyword = document.getElementById('filter_keyword').value;

            var params = {
                action: 'list',
                page: page || 1,
                per_page: 10
            };
            if (status !== '') params.status = status;
            if (type !== '') params.type = type;
            if (keyword !== '') params.keyword = keyword;

            fetch('api_feedback_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(params)
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) return;
                var data = res.data;
                var tbody = document.getElementById('feedbackTableBody');
                var noResults = document.getElementById('noResults');
                tbody.innerHTML = '';

                if (data.items.length === 0) {
                    noResults.style.display = '';
                } else {
                    noResults.style.display = 'none';
                    data.items.forEach(function(item) {
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>' + escapeHtml(item.ticket_no) + '</td>' +
                            '<td><span class="type-badge ' + item.type + '">' + (typeLabels[item.type] || item.type) + '</span></td>' +
                            '<td class="notice-title-cell">' + escapeHtml(item.title) + '</td>' +
                            '<td><span class="status-badge ' + item.status + '">' + (statusLabels[item.status] || item.status) + '</span></td>' +
                            '<td>' + formatDate(item.created_at) + '</td>' +
                            '<td>' + formatDate(item.updated_at) + '</td>' +
                            '<td class="action-buttons">' +
                                '<button class="btn-icon-action edit" title="详情" onclick="openDetail(' + item.id + ')">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
                                        '<path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
                                    '</svg>' +
                                '</button>' +
                            '</td>';
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('resultsInfo').innerHTML =
                    '共找到 <strong>' + data.total + '</strong> 条反馈，当前第 <strong>' + data.page + '</strong> / <strong>' + data.total_pages + '</strong> 页';

                renderPagination(data.page, data.total_pages);
            });
        }

        function renderPagination(currentPage, totalPages) {
            var wrap = document.getElementById('paginationWrap');
            wrap.innerHTML = '';
            if (totalPages <= 1) return;

            if (currentPage > 1) {
                wrap.appendChild(createPageLink(currentPage - 1, '上一页', true));
            }

            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, currentPage + 2);

            if (startPage > 1) {
                wrap.appendChild(createPageNum(1, currentPage));
                if (startPage > 2) {
                    var ellipsis = document.createElement('span');
                    ellipsis.className = 'page-ellipsis';
                    ellipsis.textContent = '...';
                    wrap.appendChild(ellipsis);
                }
            }

            for (var i = startPage; i <= endPage; i++) {
                wrap.appendChild(createPageNum(i, currentPage));
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    var ellipsis2 = document.createElement('span');
                    ellipsis2.className = 'page-ellipsis';
                    ellipsis2.textContent = '...';
                    wrap.appendChild(ellipsis2);
                }
                wrap.appendChild(createPageNum(totalPages, currentPage));
            }

            if (currentPage < totalPages) {
                wrap.appendChild(createPageLink(currentPage + 1, '下一页', false));
            }
        }

        function createPageNum(page, currentPage) {
            var a = document.createElement('a');
            a.className = 'page-number' + (page === currentPage ? ' active' : '');
            a.textContent = page;
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadFeedbacks(page); };
            return a;
        }

        function createPageLink(page, text, isPrev) {
            var a = document.createElement('a');
            a.className = 'page-link';
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadFeedbacks(page); };
            var svg = isPrev
                ? '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            if (isPrev) {
                a.innerHTML = svg + text;
            } else {
                a.innerHTML = text + svg;
            }
            return a;
        }

        function openDetail(id) {
            currentDetailId = id;
            fetch('api_feedback_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'detail', id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) return;
                var d = res.data;
                var body = document.getElementById('drawerBody');

                var screenshotsHtml = '';
                if (d.screenshots && d.screenshots.length > 0) {
                    screenshotsHtml = '<div class="screenshots-list">';
                    d.screenshots.forEach(function(src) {
                        screenshotsHtml += '<img src="' + escapeHtml(src) + '" alt="截图">';
                    });
                    screenshotsHtml += '</div>';
                } else {
                    screenshotsHtml = '<span style="color:var(--text-muted)">无</span>';
                }

                var noteHtml = d.internal_note
                    ? '<div class="note-display">' + escapeHtml(d.internal_note) + '</div>'
                    : '<div class="note-display" style="color:var(--text-muted)">暂无备注</div>';

                var timelineHtml = '';
                if (d.timeline && d.timeline.length > 0) {
                    timelineHtml = '<div class="timeline">';
                    d.timeline.forEach(function(t) {
                        var fromLabel = statusLabels[t.from_status] || t.from_status;
                        var toLabel = statusLabels[t.to_status] || t.to_status;
                        timelineHtml +=
                            '<div class="timeline-item">' +
                                '<div class="timeline-dot"></div>' +
                                '<div class="timeline-content">' +
                                    '<div class="timeline-change">' + escapeHtml(fromLabel) + ' → ' + escapeHtml(toLabel) + '</div>' +
                                    (t.note ? '<div class="timeline-note">' + escapeHtml(t.note) + '</div>' : '') +
                                    '<div class="timeline-time">' + formatDate(t.created_at) + '</div>' +
                                '</div>' +
                            '</div>';
                    });
                    timelineHtml += '</div>';
                } else {
                    timelineHtml = '<span style="color:var(--text-muted)">暂无记录</span>';
                }

                body.innerHTML =
                    '<div class="drawer-section">' +
                        '<div class="drawer-section-title">基本信息</div>' +
                        '<div class="detail-row"><div class="detail-label">工单号</div><div class="detail-value">' + escapeHtml(d.ticket_no) + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">类型</div><div class="detail-value"><span class="type-badge ' + d.type + '">' + (typeLabels[d.type] || d.type) + '</span></div></div>' +
                        '<div class="detail-row"><div class="detail-label">状态</div><div class="detail-value"><span class="status-badge ' + d.status + '">' + (statusLabels[d.status] || d.status) + '</span></div></div>' +
                        '<div class="detail-row"><div class="detail-label">标题</div><div class="detail-value">' + escapeHtml(d.title) + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">描述</div><div class="detail-value">' + escapeHtml(d.description || '') + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">联系方式</div><div class="detail-value">' + escapeHtml(d.contact || '无') + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">截图</div><div class="detail-value">' + screenshotsHtml + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">提交时间</div><div class="detail-value">' + formatDate(d.created_at) + '</div></div>' +
                        '<div class="detail-row"><div class="detail-label">更新时间</div><div class="detail-value">' + formatDate(d.updated_at) + '</div></div>' +
                    '</div>' +
                    '<div class="drawer-section">' +
                        '<div class="drawer-section-title">状态切换</div>' +
                        '<div class="status-select-wrapper">' +
                            '<select id="statusSelect" onchange="updateStatus(' + d.id + ', this.value)">' +
                                '<option value="pending"' + (d.status === 'pending' ? ' selected' : '') + '>待处理</option>' +
                                '<option value="processing"' + (d.status === 'processing' ? ' selected' : '') + '>处理中</option>' +
                                '<option value="resolved"' + (d.status === 'resolved' ? ' selected' : '') + '>已解决</option>' +
                                '<option value="closed"' + (d.status === 'closed' ? ' selected' : '') + '>已关闭</option>' +
                            '</select>' +
                        '</div>' +
                    '</div>' +
                    '<div class="drawer-section">' +
                        '<div class="drawer-section-title">内部备注</div>' +
                        noteHtml +
                        '<div class="note-input-group">' +
                            '<textarea id="noteInput" placeholder="输入备注内容..."></textarea>' +
                            '<button class="btn btn-primary" onclick="addNote(' + d.id + ')">添加备注</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="drawer-section">' +
                        '<div class="drawer-section-title">状态变更时间线</div>' +
                        timelineHtml +
                    '</div>';

                document.getElementById('drawer').classList.add('drawer-open');
                document.getElementById('drawerOverlay').classList.add('drawer-open');
            });
        }

        function closeDrawer() {
            document.getElementById('drawer').classList.remove('drawer-open');
            document.getElementById('drawerOverlay').classList.remove('drawer-open');
            currentDetailId = null;
        }

        function updateStatus(id, newStatus) {
            fetch('api_feedback_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'update_status', id: id, status: newStatus })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                openDetail(id);
                loadFeedbacks();
            });
        }

        function addNote(id) {
            var textarea = document.getElementById('noteInput');
            var note = textarea.value.trim();
            if (!note) return;
            fetch('api_feedback_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'add_note', id: id, note: note })
            })
            .then(function(r) { return r.json(); })
            .then(function() {
                openDetail(id);
            });
        }

        function resetFilters() {
            document.getElementById('filter_status').value = '';
            document.getElementById('filter_type').value = '';
            document.getElementById('filter_keyword').value = '';
            loadFeedbacks(1);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            var d = new Date(dateStr);
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var h = String(d.getHours()).padStart(2, '0');
            var min = String(d.getMinutes()).padStart(2, '0');
            return y + '-' + m + '-' + day + ' ' + h + ':' + min;
        }

        loadFeedbacks(1);
    </script>
</body>
</html>
