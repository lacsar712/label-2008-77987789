<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureLotteryTables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽奖活动 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
        }
        .page-header h2 {
            font-size: 1.75rem;
            color: var(--text-primary);
        }
        .section-group {
            margin-bottom: var(--spacing-2xl);
        }
        .section-group-title {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-lg);
            font-size: 1.25rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        .section-group-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 24px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        .lottery-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: var(--spacing-lg);
        }
        .lottery-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
        }
        .lottery-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .lottery-card-header {
            background: var(--gradient-primary);
            color: white;
            padding: var(--spacing-lg);
            position: relative;
        }
        .lottery-card-header.finished {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
        }
        .lottery-card-header.upcoming {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        }
        .lottery-card-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        .lottery-card-badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }
        .lottery-card-body {
            padding: var(--spacing-lg);
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .lottery-card-info {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-md);
            line-height: 1.7;
        }
        .lottery-card-info-row {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xs);
        }
        .lottery-card-info-row svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .lottery-prizes-preview {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-xs);
            margin-bottom: var(--spacing-md);
        }
        .prize-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-light);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .countdown-box {
            margin-top: auto;
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            text-align: center;
        }
        .countdown-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-xs);
        }
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 1.125rem;
        }
        .countdown-unit {
            background: var(--bg-secondary);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            color: var(--primary-color);
            min-width: 36px;
            text-align: center;
        }
        .lottery-card-footer {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .participant-count {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .status-group-empty {
            grid-column: 1 / -1;
        }
        @media (max-width: 768px) {
            .lottery-cards { grid-template-columns: 1fr; }
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
                <li><a href="search_notice.php">查询公告</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="lottery_list.php" class="active">抽奖活动</a></li>
                <li><a href="lottery_admin.php">抽奖管理</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>抽奖活动</h2>
            </div>

            <div id="lotteryContainer"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
        var allLotteries = {};
        var timerInterval = null;

        function loadLotteries() {
            fetch('api_lotteries.php?action=group_list')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        document.getElementById('lotteryContainer').innerHTML = '<div class="empty-state"><p>' + (res.message || '加载失败') + '</p></div>';
                        return;
                    }
                    allLotteries = res.data;
                    renderGroups();
                    startCountdown();
                });
        }

        function renderGroups() {
            var container = document.getElementById('lotteryContainer');
            var html = '';

            html += renderGroup('进行中', allLotteries.ongoing || [], 'ongoing');
            html += renderGroup('即将开始', allLotteries.upcoming || [], 'upcoming');
            html += renderGroup('已开奖', allLotteries.finished || [], 'finished');

            if ((allLotteries.ongoing || []).length === 0 &&
                (allLotteries.upcoming || []).length === 0 &&
                (allLotteries.finished || []).length === 0) {
                html = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无活动</p></div>';
            }

            container.innerHTML = html;
        }

        function renderGroup(title, list, status) {
            var html = '<div class="section-group">';
            html += '<div class="section-group-title">' + title + '<span style="font-size:0.875rem;font-weight:400;color:var(--text-muted);">(' + list.length + ')</span></div>';
            html += '<div class="lottery-cards">';
            if (list.length === 0) {
                html += '<div class="empty-state status-group-empty"><p>暂无' + title + '的活动</p></div>';
            } else {
                list.forEach(function(l) {
                    html += renderCard(l, status);
                });
            }
            html += '</div></div>';
            return html;
        }

        function renderCard(l, status) {
            var prizeHtml = '';
            (l.prizes || []).slice(0, 3).forEach(function(p) {
                prizeHtml += '<span class="prize-tag">' + escapeHtml(p.name) + ' x' + p.count + '</span>';
            });
            if ((l.prizes || []).length > 3) {
                prizeHtml += '<span class="prize-tag">+' + (l.prizes.length - 3) + '</span>';
            }

            var badgeText = '';
            if (status === 'ongoing') badgeText = '进行中';
            else if (status === 'upcoming') badgeText = '即将开始';
            else badgeText = '已开奖';

            var targetTime = status === 'upcoming' ? l.start_time :
                            status === 'ongoing' ? l.end_time : l.draw_time;
            var countdownLabel = status === 'upcoming' ? '距开始' :
                                 status === 'ongoing' ? '距结束' : '开奖时间';

            return '<div class="lottery-card" onclick="viewDetail(' + l.id + ')">' +
                '<div class="lottery-card-header ' + status + '">' +
                    '<div class="lottery-card-name">' + escapeHtml(l.name) + '</div>' +
                    '<span class="lottery-card-badge">' + badgeText + '</span>' +
                '</div>' +
                '<div class="lottery-card-body">' +
                    '<div class="lottery-prizes-preview">' + prizeHtml + '</div>' +
                    '<div class="lottery-card-info">' +
                        '<div class="lottery-card-info-row">' +
                            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '<span>参与：' + formatDate(l.start_time) + ' 至 ' + formatDate(l.end_time) + '</span>' +
                        '</div>' +
                        '<div class="lottery-card-info-row">' +
                            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '<span>开奖：' + formatDate(l.draw_time) + '</span>' +
                        '</div>' +
                        (l.condition_text ? '<div class="lottery-card-info-row">' +
                            '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '<span>' + escapeHtml(l.condition_text) + '</span>' +
                        '</div>' : '') +
                    '</div>' +
                    '<div class="countdown-box" data-id="' + l.id + '" data-target="' + targetTime + '" data-status="' + status + '">' +
                        '<div class="countdown-label">' + countdownLabel + '</div>' +
                        '<div class="countdown-timer" id="timer_' + l.id + '">' +
                            '<span class="countdown-unit" data-unit="days">00</span>' +
                            '<span>:</span>' +
                            '<span class="countdown-unit" data-unit="hours">00</span>' +
                            '<span>:</span>' +
                            '<span class="countdown-unit" data-unit="minutes">00</span>' +
                            '<span>:</span>' +
                            '<span class="countdown-unit" data-unit="seconds">00</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="lottery-card-footer">' +
                    '<span class="participant-count">' +
                        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.3503 17.623 3.8507 18.1676 4.55232C18.7122 5.25393 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75607 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        l.participant_count + ' 人已参与' +
                    '</span>' +
                    '<span>查看详情 →</span>' +
                '</div>' +
            '</div>';
        }

        function startCountdown() {
            if (timerInterval) clearInterval(timerInterval);
            updateCountdown();
            timerInterval = setInterval(updateCountdown, 1000);
        }

        function updateCountdown() {
            var boxes = document.querySelectorAll('.countdown-box');
            var now = new Date().getTime();
            boxes.forEach(function(box) {
                var target = new Date(box.dataset.target.replace(' ', 'T')).getTime();
                var diff = target - now;
                var timerEl = document.getElementById('timer_' + box.dataset.id);
                if (!timerEl) return;

                if (diff <= 0) {
                    timerEl.innerHTML = '<span class="countdown-unit" style="color:var(--error-color)">已结束</span>';
                    return;
                }

                var days = Math.floor(diff / (1000 * 60 * 60 * 24));
                var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((diff % (1000 * 60)) / 1000);

                var dEl = timerEl.querySelector('[data-unit="days"]');
                var hEl = timerEl.querySelector('[data-unit="hours"]');
                var mEl = timerEl.querySelector('[data-unit="minutes"]');
                var sEl = timerEl.querySelector('[data-unit="seconds"]');
                if (dEl) dEl.textContent = String(days).padStart(2, '0');
                if (hEl) hEl.textContent = String(hours).padStart(2, '0');
                if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
                if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
            });
        }

        function viewDetail(id) {
            window.location.href = 'lottery_detail.php?id=' + id;
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var h = String(d.getHours()).padStart(2, '0');
            var min = String(d.getMinutes()).padStart(2, '0');
            return y + '-' + m + '-' + day + ' ' + h + ':' + min;
        }

        loadLotteries();
    </script>
</body>
</html>
