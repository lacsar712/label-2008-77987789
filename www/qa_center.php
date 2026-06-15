<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureQATables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>问答中心 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .qa-center-header {
            background: var(--gradient-primary);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-2xl);
            box-shadow: var(--shadow-lg);
        }
        .qa-center-header h2 {
            font-size: 1.75rem;
            color: white;
            margin-bottom: var(--spacing-sm);
        }
        .qa-center-header p {
            color: rgba(255, 255, 255, 0.9);
        }
        .qa-stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-lg);
        }
        .qa-stat-item {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .qa-stat-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }
        .qa-stat-icon svg {
            width: 22px;
            height: 22px;
        }
        .qa-stat-info .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            line-height: 1;
        }
        .qa-stat-info .stat-label {
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 4px;
        }
        .qa-filter-bar {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }
        .qa-search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        .qa-search-box input {
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.5rem;
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-family: inherit;
        }
        .qa-search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .qa-search-box svg {
            position: absolute;
            left: var(--spacing-md);
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
        }
        .filter-group {
            display: flex;
            gap: var(--spacing-sm);
            align-items: center;
        }
        .filter-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        .filter-btn {
            padding: var(--spacing-xs) var(--spacing-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .filter-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        .sort-select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-xs) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            cursor: pointer;
            font-family: inherit;
        }
        .qa-table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .qa-table {
            width: 100%;
            border-collapse: collapse;
        }
        .qa-table thead {
            background: var(--bg-tertiary);
        }
        .qa-table th {
            padding: var(--spacing-md) var(--spacing-lg);
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
        }
        .qa-table tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        .qa-table tbody tr:hover {
            background: var(--bg-tertiary);
        }
        .qa-table tbody tr:last-child {
            border-bottom: none;
        }
        .qa-table td {
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--text-secondary);
            font-size: 0.875rem;
            vertical-align: middle;
        }
        .qa-question-cell {
            max-width: 400px;
        }
        .qa-question-content {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 4px;
            line-height: 1.5;
            cursor: pointer;
            transition: color 0.2s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .qa-question-content:hover {
            color: var(--primary-color);
        }
        .qa-question-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .qa-notice-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .qa-notice-link:hover {
            color: var(--primary-light);
        }
        .qa-notice-link svg {
            width: 12px;
            height: 12px;
        }
        .qa-stats-cell {
            display: flex;
            gap: var(--spacing-md);
        }
        .qa-mini-stat {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8125rem;
        }
        .qa-mini-stat svg {
            width: 14px;
            height: 14px;
        }
        .qa-empty {
            text-align: center;
            padding: var(--spacing-2xl);
        }
        .qa-empty svg {
            width: 80px;
            height: 80px;
            color: var(--text-muted);
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .qa-empty h3 {
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }
        .qa-empty p {
            color: var(--text-muted);
        }
        @media (max-width: 768px) {
            .qa-table-container {
                overflow-x: auto;
            }
            .qa-table {
                min-width: 700px;
            }
            .qa-filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                flex-wrap: wrap;
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
                <li><a href="qa_center.php" class="active">问答中心</a></li>
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="rating_admin.php">评价管理</a></li>
                <li><a href="rating_summary.php">评价汇总</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="lottery_list.php">抽奖活动</a></li>
                <li><a href="lottery_admin.php">抽奖管理</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="qa-center-header">
                <h2>问答中心</h2>
                <p>浏览全站公告问答，参与讨论与互助</p>
                <div class="qa-stats-row" id="qaStats">
                    <div class="qa-stat-item">
                        <div class="qa-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.228 9C8.73983 7.8342 9.96676 7 11.4 7C13.3875 7 15 8.79086 15 11C15 12.6569 13.6569 14 12 14C11.2817 14 10.6279 13.7895 10.0858 13.4202L9 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="qa-stat-info">
                            <div class="stat-number" id="statTotal">-</div>
                            <div class="stat-label">总问题数</div>
                        </div>
                    </div>
                    <div class="qa-stat-item">
                        <div class="qa-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="qa-stat-info">
                            <div class="stat-number" id="statResolved">-</div>
                            <div class="stat-label">已解答</div>
                        </div>
                    </div>
                    <div class="qa-stat-item">
                        <div class="qa-stat-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div class="qa-stat-info">
                            <div class="stat-number" id="statOpen">-</div>
                            <div class="stat-label">待解答</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="qa-filter-bar">
                <div class="qa-search-box">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <input type="text" id="searchKeyword" placeholder="搜索问题、回答或公告标题...">
                </div>
                <div class="filter-group">
                    <span class="filter-label">状态：</span>
                    <button class="filter-btn active" data-status="">全部</button>
                    <button class="filter-btn" data-status="open">待解答</button>
                    <button class="filter-btn" data-status="resolved">已解答</button>
                </div>
                <div class="filter-group">
                    <span class="filter-label">排序：</span>
                    <select class="sort-select" id="sortSelect">
                        <option value="time">最新发布</option>
                        <option value="hot">热度排序</option>
                    </select>
                </div>
            </div>

            <div class="qa-table-container">
                <table class="qa-table">
                    <thead>
                        <tr>
                            <th width="40%">问题</th>
                            <th width="20%">所属公告</th>
                            <th width="12%">提问者</th>
                            <th width="12%">统计</th>
                            <th width="8%">状态</th>
                            <th width="8%">时间</th>
                        </tr>
                    </thead>
                    <tbody id="qaTableBody"></tbody>
                </table>
                <div id="qaEmpty" class="qa-empty" style="display:none;">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <h3>暂无匹配的问题</h3>
                    <p>试试其他关键词或筛选条件吧</p>
                </div>
            </div>

            <div class="pagination" id="qaPagination"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
    (function() {
        var currentPage = 1;
        var totalPages = 1;
        var currentStatus = '';
        var currentKeyword = '';
        var currentSort = 'time';
        var searchTimer = null;

        loadStats();
        loadQuestions();

        document.querySelectorAll('.filter-btn[data-status]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn[data-status]').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                currentStatus = btn.dataset.status;
                currentPage = 1;
                loadQuestions();
            });
        });

        document.getElementById('sortSelect').addEventListener('change', function(e) {
            currentSort = e.target.value;
            currentPage = 1;
            loadQuestions();
        });

        document.getElementById('searchKeyword').addEventListener('input', function(e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                currentKeyword = e.target.value.trim();
                currentPage = 1;
                loadQuestions();
            }, 300);
        });

        function loadStats() {
            fetch('api_qa_search.php?per_page=1&page=1')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('statTotal').textContent = data.data.pagination.total;
                    }
                });
            fetch('api_qa_search.php?per_page=1&page=1&status=resolved')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('statResolved').textContent = data.data.pagination.total;
                    }
                });
            fetch('api_qa_search.php?per_page=1&page=1&status=open')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        document.getElementById('statOpen').textContent = data.data.pagination.total;
                    }
                });
        }

        function loadQuestions(page) {
            page = page || 1;
            currentPage = page;
            var url = 'api_qa_search.php?page=' + page + '&per_page=10&sort=' + currentSort;
            if (currentStatus) url += '&status=' + currentStatus;
            if (currentKeyword) url += '&keyword=' + encodeURIComponent(currentKeyword);
            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderTable(data.data.list);
                        renderPagination(data.data.pagination);
                    }
                });
        }

        function renderTable(list) {
            var tbody = document.getElementById('qaTableBody');
            var empty = document.getElementById('qaEmpty');
            if (!list || list.length === 0) {
                tbody.innerHTML = '';
                empty.style.display = 'block';
                return;
            }
            empty.style.display = 'none';
            var html = '';
            list.forEach(function(q) {
                html += '<tr>';
                html += '  <td class="qa-question-cell">';
                html += '    <div class="qa-question-content" onclick="goToDetail(' + q.notice_id + ',' + q.id + ')">' + escapeHtml(q.content) + '</div>';
                html += '    <div class="qa-question-meta">';
                html += '      <span>提问：' + escapeHtml(q.asker) + '</span>';
                html += '    </div>';
                html += '  </td>';
                html += '  <td>';
                html += '    <a class="qa-notice-link" href="notice_detail.php?id=' + q.notice_id + '">';
                html += '      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V10M10 14L21 3M21 14V21C21 21.5304 20.7893 22.0391 20.4142 22.4142C20.0391 22.7893 19.5304 23 19 23H5C4.46957 23 3.96086 22.7893 3.58579 22.4142C3.21071 22.0391 3 21.5304 3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '      ' + (q.notice_title ? truncate(escapeHtml(q.notice_title), 20) : '未知公告') + '';
                html += '    </a>';
                html += '  </td>';
                html += '  <td>' + escapeHtml(q.asker) + '</td>';
                html += '  <td>';
                html += '    <div class="qa-stats-cell">';
                html += '      <span class="qa-mini-stat" title="回答数">';
                html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '        ' + q.answer_count;
                html += '      </span>';
                html += '      <span class="qa-mini-stat" title="点赞数">';
                html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.84 4.61C20.3293 4.099 19.7228 3.69365 19.0554 3.41708C18.3879 3.14052 17.6725 3 16.95 3C15.7749 3 14.6534 3.46464 13.7917 4.32628L12 6.118L10.2083 4.32628C8.52313 2.64109 5.77687 2.64109 4.09168 4.32628C2.40649 6.01147 2.40649 8.75773 4.09168 10.4429L12 18.3512L19.9083 10.4429C20.4193 9.93224 20.8247 9.32574 21.1012 8.65828C21.3778 7.99081 21.5183 7.27543 21.5 6.553C21.5 5.85074 21.3608 5.15728 21.095 4.509L20.84 4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '        ' + q.total_likes;
                html += '      </span>';
                html += '    </div>';
                html += '  </td>';
                html += '  <td>';
                html += '    <span class="qa-status-badge status-' + q.status + '">' + (q.status === 'resolved' ? '已解答' : '待解答') + '</span>';
                html += '  </td>';
                html += '  <td style="color:var(--text-muted);font-size:0.8125rem;">';
                html +=     formatDate(q.created_at);
                html += '  </td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        }

        function renderPagination(pagination) {
            totalPages = pagination.total_pages;
            var container = document.getElementById('qaPagination');
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            var html = '';
            if (currentPage > 1) {
                html += '<a class="page-link" onclick="loadQuestions(' + (currentPage - 1) + ')">';
                html += '  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '  上一页</a>';
            }
            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, currentPage + 2);
            if (start > 1) {
                html += '<a class="page-number" onclick="loadQuestions(1)">1</a>';
                if (start > 2) html += '<span class="page-ellipsis">...</span>';
            }
            for (var i = start; i <= end; i++) {
                html += '<a class="page-number ' + (i === currentPage ? 'active' : '') + '" onclick="loadQuestions(' + i + ')">' + i + '</a>';
            }
            if (end < totalPages) {
                if (end < totalPages - 1) html += '<span class="page-ellipsis">...</span>';
                html += '<a class="page-number" onclick="loadQuestions(' + totalPages + ')">' + totalPages + '</a>';
            }
            if (currentPage < totalPages) {
                html += '<a class="page-link" onclick="loadQuestions(' + (currentPage + 1) + ')">下一页';
                html += '  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '</a>';
            }
            container.innerHTML = html;
        }

        window.goToDetail = function(noticeId, questionId) {
            window.location.href = 'notice_detail.php?id=' + noticeId + '#qa-question-' + questionId;
        };

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function truncate(text, len) {
            return text.length > len ? text.substring(0, len) + '...' : text;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            var d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr.substring(0, 10);
            var now = new Date();
            var diff = (now - d) / 1000;
            if (diff < 60) return '刚刚';
            if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
            if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
            if (diff < 604800) return Math.floor(diff / 86400) + '天前';
            return dateStr.substring(0, 10);
        }

        window.loadQuestions = loadQuestions;
    })();
    </script>
</body>
</html>
