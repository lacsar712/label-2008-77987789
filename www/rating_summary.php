<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureRatingTables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>评价汇总 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        .page-header h2 {
            color: var(--text-primary);
            font-size: 1.5rem;
        }
        .sort-controls {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .sort-controls label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .sort-controls select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-family: inherit;
        }
        .summary-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--shadow-md);
            transition: all 0.2s ease;
        }
        .summary-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }
        .summary-title {
            flex: 1;
            min-width: 200px;
        }
        .summary-title a {
            color: var(--text-primary);
            text-decoration: none;
            font-size: 1.125rem;
            font-weight: 600;
            transition: color 0.2s ease;
            display: inline-block;
        }
        .summary-title a:hover {
            color: var(--primary-color);
        }
        .summary-meta {
            color: var(--text-muted);
            font-size: 0.75rem;
            margin-top: var(--spacing-xs);
        }
        .summary-score-block {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        .score-main {
            text-align: center;
        }
        .score-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--warning-color);
            line-height: 1;
        }
        .score-stars {
            display: inline-flex;
            gap: 2px;
            margin-top: var(--spacing-xs);
        }
        .score-stars svg {
            width: 16px;
            height: 16px;
        }
        .score-count {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: var(--spacing-xs);
        }
        .distribution-block {
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
        }
        .dist-title {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
        }
        .dist-row {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: 4px;
        }
        .dist-row:last-child {
            margin-bottom: 0;
        }
        .dist-label {
            width: 28px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .dist-label svg {
            width: 12px;
            height: 12px;
        }
        .dist-bar-wrap {
            flex: 1;
            height: 8px;
            background: var(--bg-secondary);
            border-radius: 4px;
            overflow: hidden;
            min-width: 100px;
        }
        .dist-bar {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        .dist-count {
            width: 36px;
            font-size: 0.75rem;
            color: var(--text-muted);
            text-align: right;
        }
        .latest-comment {
            border-top: 1px solid var(--border-color);
            padding-top: var(--spacing-md);
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }
        .latest-comment-icon {
            flex-shrink: 0;
            color: var(--primary-color);
            margin-top: 2px;
        }
        .latest-comment-icon svg {
            width: 16px;
            height: 16px;
        }
        .latest-comment-body {
            flex: 1;
            min-width: 0;
        }
        .latest-comment-label {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        .latest-comment-text {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .latest-comment-time {
            color: var(--text-muted);
            font-size: 0.7rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .no-comment {
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-style: italic;
        }
        .no-results {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
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
        @media (max-width: 768px) {
            .summary-score-block {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-md);
            }
            .distribution-block {
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
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="rating_admin.php">评价管理</a></li>
                <li><a href="rating_summary.php" class="active">评价汇总</a></li>
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
            <div class="page-header">
                <h2>评价汇总</h2>
                <div class="sort-controls">
                    <label for="sortSelect">排序方式：</label>
                    <select id="sortSelect" onchange="loadSummary(1)">
                        <option value="avg_desc">平均分从高到低</option>
                        <option value="avg_asc">平均分从低到高</option>
                        <option value="count_desc">评价人数从多到少</option>
                        <option value="count_asc">评价人数从少到多</option>
                        <option value="date_desc">发布时间从新到旧</option>
                    </select>
                </div>
            </div>

            <div id="summaryList"></div>
            <div id="noResults" class="no-results" style="display:none;">
                <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.049 2.92698C11.3483 1.98867 12.6517 1.98867 12.951 2.92698L14.8534 8.87647C14.9863 9.29185 15.3776 9.5747 15.8156 9.5747H22.0523C23.0302 9.5747 23.4352 10.8261 22.6134 11.4267L17.6084 15.0714C17.2471 15.3345 17.096 15.8059 17.2093 16.2457L18.8641 22.6132C19.0971 23.5183 18.0282 24.2426 17.2272 23.7718L12.0461 20.7678C11.6731 20.5516 11.2069 20.5516 10.8339 20.7678L5.65276 23.7718C4.85181 24.2426 3.78285 23.5183 4.0159 22.6132L5.67065 16.2457C5.78396 15.8059 5.63283 15.3345 5.27158 15.0714L0.266548 11.4267C-0.555213 10.8261 -0.150201 9.5747 0.827703 9.5747H7.06439C7.5024 9.5747 7.89368 9.29185 8.02663 8.87647L11.049 2.92698Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p>暂无评价数据</p>
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

        function getStarSVG(filled, small) {
            var size = small ? '16px' : '20px';
            var color = filled ? '#f59e0b' : '#475569';
            var fill = filled ? '#f59e0b' : 'none';
            return '<svg viewBox="0 0 24 24" fill="' + fill + '" xmlns="http://www.w3.org/2000/svg" style="width:' + size + ';height:' + size + ';">' +
                '<path d="M11.049 2.92698C11.3483 1.98867 12.6517 1.98867 12.951 2.92698L14.8534 8.87647C14.9863 9.29185 15.3776 9.5747 15.8156 9.5747H22.0523C23.0302 9.5747 23.4352 10.8261 22.6134 11.4267L17.6084 15.0714C17.2471 15.3345 17.096 15.8059 17.2093 16.2457L18.8641 22.6132C19.0971 23.5183 18.0282 24.2426 17.2272 23.7718L12.0461 20.7678C11.6731 20.5516 11.2069 20.5516 10.8339 20.7678L5.65276 23.7718C4.85181 24.2426 3.78285 23.5183 4.0159 22.6132L5.67065 16.2457C5.78396 15.8059 5.63283 15.3345 5.27158 15.0714L0.266548 11.4267C-0.555213 10.8261 -0.150201 9.5747 0.827703 9.5747H7.06439C7.5024 9.5747 7.89368 9.29185 8.02663 8.87647L11.049 2.92698Z" stroke="' + color + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        }

        function renderStars(score) {
            var html = '<span class="score-stars">';
            var full = Math.floor(score);
            var half = score - full >= 0.5;
            for (var i = 1; i <= 5; i++) {
                if (i <= full) {
                    html += getStarSVG(true, true);
                } else if (i === full + 1 && half) {
                    html += getStarSVG(true, true);
                } else {
                    html += getStarSVG(false, true);
                }
            }
            html += '</span>';
            return html;
        }

        function renderDistribution(dist, total) {
            var html = '<div class="distribution-block">';
            html += '<div class="dist-title">评分分布</div>';
            for (var s = 5; s >= 1; s--) {
                var count = dist[s] || 0;
                var pct = total > 0 ? (count / total) * 100 : 0;
                html += '<div class="dist-row">';
                html += '  <span class="dist-label">' + s + getStarSVG(true, false).replace('width:16px;height:16px;', 'width:12px;height:12px;') + '</span>';
                html += '  <div class="dist-bar-wrap"><div class="dist-bar" style="width:' + pct + '%;"></div></div>';
                html += '  <span class="dist-count">' + count + '</span>';
                html += '</div>';
            }
            html += '</div>';
            return html;
        }

        function renderCard(item) {
            var html = '<div class="summary-card">';
            html += '  <div class="summary-header">';
            html += '    <div class="summary-title">';
            html += '      <a href="notice_detail.php?id=' + item.notice_id + '" target="_blank">' + escapeHtml(item.notice_title) + '</a>';
            html += '      <div class="summary-meta">发布于 ' + formatDate(item.publish_date) + '</div>';
            html += '    </div>';
            html += '    <div class="summary-score-block">';
            html += '      <div class="score-main">';
            html += '        <div class="score-value">' + item.avg_score.toFixed(1) + '</div>';
            html += '        ' + renderStars(item.avg_score);
            html += '        <div class="score-count">' + item.total_count + ' 人评价</div>';
            html += '      </div>';
            html += '      ' + renderDistribution(item.distribution, item.total_count);
            html += '    </div>';
            html += '  </div>';
            html += '  <div class="latest-comment">';
            html += '    <span class="latest-comment-icon">';
            html += '      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            html += '    </span>';
            if (item.latest_comment && item.latest_comment.comment) {
                html += '    <div class="latest-comment-body">';
                html += '      <div class="latest-comment-label">最新评价</div>';
                html += '      <div class="latest-comment-text">' + escapeHtml(item.latest_comment.comment) + '</div>';
                html += '    </div>';
                html += '    <div class="latest-comment-time">' + formatDate(item.latest_comment.created_at) + '</div>';
            } else {
                html += '    <span class="no-comment">暂无文字评价</span>';
            }
            html += '  </div>';
            html += '</div>';
            return html;
        }

        function loadSummary(page) {
            var sort = document.getElementById('sortSelect').value;
            fetch('api_notice_ratings.php?action=summary&sort=' + sort + '&page=' + (page || 1) + '&per_page=10')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) return;
                    var data = res.data;
                    var listEl = document.getElementById('summaryList');
                    var noResults = document.getElementById('noResults');
                    listEl.innerHTML = '';

                    if (data.list.length === 0) {
                        noResults.style.display = '';
                    } else {
                        noResults.style.display = 'none';
                        data.list.forEach(function(item) {
                            listEl.innerHTML += renderCard(item);
                        });
                    }

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
            a.onclick = function() { loadSummary(page); };
            return a;
        }

        function createPageLink(page, text, isPrev) {
            var a = document.createElement('a');
            a.className = 'page-link';
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadSummary(page); };
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

        loadSummary(1);
    </script>
</body>
</html>
