<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureRatingTables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评价管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .search-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-md);
        }
        .search-container h2 {
            color: var(--text-primary);
            margin-bottom: var(--spacing-lg);
            font-size: 1.5rem;
        }
        .search-form {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-lg);
        }
        .search-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }
        .search-field {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        .search-field label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        .search-field input,
        .search-field select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .search-field input:focus,
        .search-field select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .search-actions {
            display: flex;
            gap: var(--spacing-sm);
            justify-content: flex-end;
        }
        .results-info {
            color: var(--text-secondary);
            margin-bottom: var(--spacing-md);
            font-size: 0.9375rem;
        }
        .results-info strong {
            color: var(--text-primary);
        }
        .notices-table-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .notices-table {
            width: 100%;
            border-collapse: collapse;
        }
        .notices-table thead {
            background: var(--bg-tertiary);
        }
        .notices-table th {
            padding: var(--spacing-md) var(--spacing-lg);
            text-align: left;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
        }
        .notices-table td {
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--text-secondary);
            font-size: 0.875rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .notices-table tbody tr {
            transition: background 0.2s ease;
        }
        .notices-table tbody tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        .notices-table tbody tr:last-child td {
            border-bottom: none;
        }
        .notice-title-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .notice-title-cell a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .notice-title-cell a:hover {
            color: var(--primary-color);
        }
        .comment-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .rating-stars-display {
            display: inline-flex;
            gap: 2px;
        }
        .rating-stars-display svg {
            width: 16px;
            height: 16px;
        }
        .no-results {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .no-results-icon {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
            flex-wrap: wrap;
        }
        .page-link,
        .page-number {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .page-link:hover,
        .page-number:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .page-number.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        .page-link svg {
            width: 16px;
            height: 16px;
        }
        .page-ellipsis {
            color: var(--text-muted);
            padding: var(--spacing-sm);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: var(--spacing-sm) var(--spacing-lg);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            font-family: inherit;
        }
        .btn-icon {
            width: 18px;
            height: 18px;
        }
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        @media (max-width: 768px) {
            .search-fields {
                grid-template-columns: 1fr;
            }
            .notices-table th:nth-child(4),
            .notices-table td:nth-child(4) {
                display: none;
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
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="rating_admin.php" class="active">评价管理</a></li>
                <li><a href="rating_summary.php">评价汇总</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
                <li><a href="lottery_list.php">抽奖活动</a></li>
                <li><a href="lottery_admin.php">抽奖管理</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <h2>评价管理</h2>
                <form id="filterForm" class="search-form" onsubmit="return false;">
                    <div class="search-fields">
                        <div class="search-field">
                            <label for="filter_score">评分</label>
                            <select id="filter_score">
                                <option value="">全部</option>
                                <option value="5">5 星</option>
                                <option value="4">4 星</option>
                                <option value="3">3 星</option>
                                <option value="2">2 星</option>
                                <option value="1">1 星</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="filter_notice">公告</label>
                            <select id="filter_notice">
                                <option value="">全部</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="filter_keyword">关键词</label>
                            <input type="text" id="filter_keyword" placeholder="搜索评价内容或公告标题">
                        </div>
                        <div class="search-field">
                            <label for="filter_start">开始日期</label>
                            <input type="date" id="filter_start">
                        </div>
                        <div class="search-field">
                            <label for="filter_end">结束日期</label>
                            <input type="date" id="filter_end">
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-primary" onclick="loadRatings(1)">
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
                <p id="resultsInfo">共找到 <strong>0</strong> 条评价，当前第 <strong>1</strong> / <strong>1</strong> 页</p>
            </div>

            <div class="notices-table-container">
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th width="8%">ID</th>
                            <th width="25%">公告标题</th>
                            <th width="12%">评分</th>
                            <th width="35%">评价内容</th>
                            <th width="20%">提交时间</th>
                        </tr>
                    </thead>
                    <tbody id="ratingTableBody">
                    </tbody>
                </table>
                <div id="noResults" class="no-results" style="display:none;">
                    <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>没有找到符合条件的评价</p>
                </div>
            </div>

            <div id="paginationWrap" class="pagination-wrap"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            return dateStr.substring(0, 16);
        }

        function getStarsSVG(score) {
            var html = '<span class="rating-stars-display">';
            for (var i = 1; i <= 5; i++) {
                var filled = i <= score;
                var color = filled ? '#f59e0b' : '#475569';
                var fill = filled ? '#f59e0b' : 'none';
                html += '<svg viewBox="0 0 24 24" fill="' + fill + '" xmlns="http://www.w3.org/2000/svg">' +
                    '<path d="M11.049 2.92698C11.3483 1.98867 12.6517 1.98867 12.951 2.92698L14.8534 8.87647C14.9863 9.29185 15.3776 9.5747 15.8156 9.5747H22.0523C23.0302 9.5747 23.4352 10.8261 22.6134 11.4267L17.6084 15.0714C17.2471 15.3345 17.096 15.8059 17.2093 16.2457L18.8641 22.6132C19.0971 23.5183 18.0282 24.2426 17.2272 23.7718L12.0461 20.7678C11.6731 20.5516 11.2069 20.5516 10.8339 20.7678L5.65276 23.7718C4.85181 24.2426 3.78285 23.5183 4.0159 22.6132L5.67065 16.2457C5.78396 15.8059 5.63283 15.3345 5.27158 15.0714L0.266548 11.4267C-0.555213 10.8261 -0.150201 9.5747 0.827703 9.5747H7.06439C7.5024 9.5747 7.89368 9.29185 8.02663 8.87647L11.049 2.92698Z" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            }
            html += '</span>';
            return html;
        }

        function loadNoticeOptions() {
            fetch('api_notice_ratings.php?action=notice_options')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success || !res.data) return;
                    var select = document.getElementById('filter_notice');
                    res.data.forEach(function(n) {
                        var opt = document.createElement('option');
                        opt.value = n.id;
                        opt.textContent = n.title;
                        select.appendChild(opt);
                    });
                });
        }

        function loadRatings(page) {
            var score = document.getElementById('filter_score').value;
            var notice_id = document.getElementById('filter_notice').value;
            var keyword = document.getElementById('filter_keyword').value;
            var start_date = document.getElementById('filter_start').value;
            var end_date = document.getElementById('filter_end').value;

            var params = new URLSearchParams();
            params.set('action', 'list');
            params.set('page', page || 1);
            params.set('per_page', 10);
            if (score !== '') params.set('score', score);
            if (notice_id !== '') params.set('notice_id', notice_id);
            if (keyword !== '') params.set('keyword', keyword);
            if (start_date !== '') params.set('start_date', start_date);
            if (end_date !== '') params.set('end_date', end_date);

            fetch('api_notice_ratings.php?' + params.toString())
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) return;
                    var data = res.data;
                    var tbody = document.getElementById('ratingTableBody');
                    var noResults = document.getElementById('noResults');
                    tbody.innerHTML = '';

                    if (data.list.length === 0) {
                        noResults.style.display = '';
                    } else {
                        noResults.style.display = 'none';
                        data.list.forEach(function(item) {
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + item.id + '</td>' +
                                '<td class="notice-title-cell"><a href="notice_detail.php?id=' + item.notice_id + '" target="_blank">' + escapeHtml(item.notice_title) + '</a></td>' +
                                '<td>' + getStarsSVG(item.score) + '</td>' +
                                '<td class="comment-cell" title="' + escapeHtml(item.comment) + '">' + escapeHtml(item.comment || '-') + '</td>' +
                                '<td>' + formatDate(item.created_at) + '</td>';
                            tbody.appendChild(tr);
                        });
                    }

                    document.getElementById('resultsInfo').innerHTML =
                        '共找到 <strong>' + data.pagination.total + '</strong> 条评价，当前第 <strong>' + data.pagination.page + '</strong> / <strong>' + data.pagination.total_pages + '</strong> 页';

                    renderPagination(data.pagination.page, data.pagination.total_pages);
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
            a.onclick = function() { loadRatings(page); };
            return a;
        }

        function createPageLink(page, text, isPrev) {
            var a = document.createElement('a');
            a.className = 'page-link';
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadRatings(page); };
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

        function resetFilters() {
            document.getElementById('filter_score').value = '';
            document.getElementById('filter_notice').value = '';
            document.getElementById('filter_keyword').value = '';
            document.getElementById('filter_start').value = '';
            document.getElementById('filter_end').value = '';
            loadRatings(1);
        }

        loadNoticeOptions();
        loadRatings(1);
    </script>
</body>
</html>
