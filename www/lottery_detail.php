<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureLotteryTables();

$lotteryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($lotteryId <= 0) {
    echo '<script>alert("无效的活动ID"); window.location.href="lottery_list.php";</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活动详情 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: var(--spacing-lg);
            transition: color 0.2s ease;
        }
        .back-link:hover { color: var(--primary-color); }
        .detail-header {
            background: var(--gradient-primary);
            color: white;
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-xl);
            position: relative;
            overflow: hidden;
        }
        .detail-header.finished {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
        }
        .detail-header.upcoming {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        }
        .detail-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: var(--spacing-md);
        }
        .detail-status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 600;
            backdrop-filter: blur(4px);
        }
        .detail-body {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: var(--spacing-xl);
        }
        .detail-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-lg);
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 20px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-lg);
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-xs);
        }
        .info-label {
            font-size: 0.8125rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        .prizes-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        .prize-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--primary-color);
        }
        .prize-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        .prize-count {
            background: var(--gradient-primary);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
        }
        .countdown-large {
            text-align: center;
        }
        .countdown-large-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-md);
        }
        .countdown-large-timer {
            display: flex;
            justify-content: center;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        .countdown-large-unit {
            background: var(--bg-tertiary);
            padding: var(--spacing-md);
            border-radius: var(--radius-lg);
            min-width: 72px;
        }
        .countdown-large-num {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Courier New', monospace;
            line-height: 1;
        }
        .countdown-large-text {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: var(--spacing-xs);
        }
        .action-btn {
            width: 100%;
            padding: var(--spacing-md);
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: var(--radius-lg);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .action-btn.primary {
            background: var(--gradient-primary);
            color: white;
        }
        .action-btn.primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .action-btn.secondary {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: not-allowed;
        }
        .action-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .participant-stat {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-lg);
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
        }
        .participant-stat-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        .participant-stat-num {
            font-size: 1.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .condition-box {
            padding: var(--spacing-lg);
            background: rgba(99, 102, 241, 0.05);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: var(--radius-lg);
            color: var(--text-secondary);
            font-size: 0.9375rem;
            line-height: 1.7;
        }
        .winners-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .winner-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-md);
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
        }
        .winner-item:hover {
            background: rgba(99, 102, 241, 0.05);
        }
        .winner-prize {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            padding: 0.2rem 0.5rem;
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border-radius: var(--radius-sm);
            font-size: 0.8125rem;
            font-weight: 600;
        }
        .winner-visitor {
            font-family: 'Courier New', monospace;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .winner-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            padding: var(--spacing-md) var(--spacing-xl);
            border-radius: var(--radius-lg);
            color: white;
            font-weight: 500;
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            box-shadow: var(--shadow-lg);
        }
        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .toast.success { background: var(--success-color, #10b981); }
        .toast.error { background: var(--error-color); }
        .toast.info { background: var(--primary-color); }
        .empty-state {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .notice-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .notice-link:hover { text-decoration: underline; }
        @media (max-width: 768px) {
            .detail-body { grid-template-columns: 1fr; }
            .info-grid { grid-template-columns: 1fr; }
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
            <a href="lottery_list.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                返回活动列表
            </a>

            <div id="pageContent">
                <div class="loading" style="text-align:center;padding:var(--spacing-2xl);color:var(--text-muted);">加载中...</div>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
        var lotteryId = <?php echo $lotteryId; ?>;
        var currentLottery = null;
        var countdownInterval = null;
        var participantRefreshInterval = null;

        function showToast(msg, type) {
            var toast = document.getElementById('toast');
            toast.className = 'toast ' + (type || 'info');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 3000);
        }

        function loadDetail() {
            fetch('api_lotteries.php?action=detail&id=' + lotteryId)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        document.getElementById('pageContent').innerHTML = '<div class="empty-state"><p>' + (res.message || '加载失败') + '</p></div>';
                        return;
                    }
                    currentLottery = res.data;
                    checkAndTriggerDraw();
                    renderDetail();
                    startCountdown();
                    startParticipantRefresh();
                });
        }

        function checkAndTriggerDraw() {
            if (!currentLottery || currentLottery.is_drawn) return;
            var now = new Date().getTime();
            var drawTime = new Date(currentLottery.draw_time.replace(' ', 'T')).getTime();
            if (now >= drawTime) {
                fetch('api_lotteries.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'draw', id: lotteryId })
                }).then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        showToast('活动已自动开奖！', 'success');
                        loadDetail();
                    }
                });
            }
        }

        function renderDetail() {
            var l = currentLottery;
            var rs = l.runtime_status;
            var headerClass = rs === 'finished' ? 'finished' : (rs === 'upcoming' ? 'upcoming' : '');
            var statusText = rs === 'finished' ? '已开奖' :
                            rs === 'upcoming' ? '即将开始' :
                            rs === 'ongoing' ? '进行中' :
                            rs === 'waiting_draw' ? '等待开奖' : '待开奖';

            var html = '';

            html += '<div class="detail-header ' + headerClass + '">' +
                '<div class="detail-title">' + escapeHtml(l.name) + '</div>' +
                '<span class="detail-status-badge">' + statusText + '</span>' +
            '</div>';

            html += '<div class="detail-body">';
            html += '<div>';

            html += '<div class="detail-section">';
            html += '<div class="section-title">活动信息</div>';
            html += '<div class="info-grid">';
            html += '<div class="info-item">' +
                '<div class="info-label">' +
                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '参与时间' +
                '</div>' +
                '<div class="info-value">' + formatDate(l.start_time) + ' ~ ' + formatDate(l.end_time) + '</div>' +
            '</div>';
            html += '<div class="info-item">' +
                '<div class="info-label">' +
                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M12 8V12L15 15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '开奖时间' +
                '</div>' +
                '<div class="info-value">' + formatDate(l.draw_time) + '</div>' +
            '</div>';
            if (l.notice_title) {
                html += '<div class="info-item" style="grid-column:1 / -1;">' +
                    '<div class="info-label">' +
                        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '关联公告' +
                    '</div>' +
                    '<div class="info-value"><a href="notice_detail.php?id=' + l.notice_id + '" class="notice-link">' + escapeHtml(l.notice_title) + '</a></div>' +
                '</div>';
            }
            if (l.condition_text) {
                html += '<div class="info-item" style="grid-column:1 / -1;">' +
                    '<div class="info-label">' +
                        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                        '参与条件' +
                    '</div>' +
                    '<div class="condition-box">' + escapeHtml(l.condition_text) + '</div>' +
                '</div>';
            }
            html += '</div></div>';

            html += '<div class="detail-section">';
            html += '<div class="section-title">奖品列表</div>';
            html += '<div class="prizes-list">';
            (l.prizes || []).forEach(function(p) {
                html += '<div class="prize-item">' +
                    '<div class="prize-name">' + escapeHtml(p.name) + '</div>' +
                    '<div class="prize-count">x' + p.count + '</div>' +
                '</div>';
            });
            html += '</div></div>';

            if (l.is_drawn) {
                html += '<div class="detail-section">';
                html += '<div class="section-title">中奖名单</div>';
                html += '<div id="winnersList"><div class="loading" style="text-align:center;color:var(--text-muted);padding:var(--spacing-lg);">加载中奖名单...</div></div>';
                html += '</div>';
                loadWinners();
            }

            html += '</div>';

            html += '<div>';
            html += '<div class="detail-section">';

            var targetTime = rs === 'upcoming' ? l.start_time :
                            rs === 'ongoing' ? l.end_time :
                            rs === 'waiting_draw' ? l.draw_time : l.draw_time;
            var countdownLabel = rs === 'upcoming' ? '距活动开始' :
                                 rs === 'ongoing' ? '距参与结束' :
                                 rs === 'waiting_draw' ? '距开奖' : '开奖时间';

            html += '<div class="countdown-large" id="countdownBox">' +
                '<div class="countdown-large-label">' + countdownLabel + '</div>' +
                '<div class="countdown-large-timer" data-target="' + targetTime + '">' +
                    '<div class="countdown-large-unit"><div class="countdown-large-num" id="cd_days">00</div><div class="countdown-large-text">天</div></div>' +
                    '<div class="countdown-large-unit"><div class="countdown-large-num" id="cd_hours">00</div><div class="countdown-large-text">时</div></div>' +
                    '<div class="countdown-large-unit"><div class="countdown-large-num" id="cd_minutes">00</div><div class="countdown-large-text">分</div></div>' +
                    '<div class="countdown-large-unit"><div class="countdown-large-num" id="cd_seconds">00</div><div class="countdown-large-text">秒</div></div>' +
                '</div>';

            html += '<div class="participant-stat">' +
                '<div class="participant-stat-label">' +
                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"><path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13M16 3.13C16.8604 3.3503 17.623 3.8507 18.1676 4.55232C18.7122 5.25393 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75607 18.1676 9.45768C17.623 10.1593 16.8604 10.6597 16 10.88M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '已有 <strong id="participantNum" style="color:var(--text-primary);">' + l.participant_count + '</strong> 人参与' +
                '</div>' +
            '</div>';

            if (!l.is_drawn) {
                html += '<button class="action-btn ' + (rs === 'ongoing' ? 'primary' : 'secondary') + '" id="participateBtn" onclick="handleParticipate()">' +
                    (rs === 'upcoming' ? '活动尚未开始' :
                     rs === 'ongoing' ? '立即参与' :
                     rs === 'waiting_draw' ? '参与已结束，等待开奖' :
                     rs === 'draw_pending' ? '即将开奖' : '活动已结束') +
                '</button>';
            } else {
                html += '<button class="action-btn secondary" disabled>活动已开奖</button>';
            }

            html += '</div></div>';
            html += '</div>';

            document.getElementById('pageContent').innerHTML = html;
        }

        function startCountdown() {
            if (countdownInterval) clearInterval(countdownInterval);
            updateCountdown();
            countdownInterval = setInterval(updateCountdown, 1000);
        }

        function updateCountdown() {
            var box = document.querySelector('.countdown-large-timer');
            if (!box) return;
            var now = new Date().getTime();
            var target = new Date(box.dataset.target.replace(' ', 'T')).getTime();
            var diff = target - now;

            if (diff <= 0) {
                document.getElementById('cd_days').textContent = '00';
                document.getElementById('cd_hours').textContent = '00';
                document.getElementById('cd_minutes').textContent = '00';
                document.getElementById('cd_seconds').textContent = '00';
                if (countdownInterval) clearInterval(countdownInterval);
                setTimeout(function() { loadDetail(); }, 1000);
                return;
            }

            var days = Math.floor(diff / (1000 * 60 * 60 * 24));
            var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((diff % (1000 * 60)) / 1000);

            var dEl = document.getElementById('cd_days');
            var hEl = document.getElementById('cd_hours');
            var mEl = document.getElementById('cd_minutes');
            var sEl = document.getElementById('cd_seconds');
            if (dEl) dEl.textContent = String(days).padStart(2, '0');
            if (hEl) hEl.textContent = String(hours).padStart(2, '0');
            if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
            if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
        }

        function startParticipantRefresh() {
            if (participantRefreshInterval) clearInterval(participantRefreshInterval);
            participantRefreshInterval = setInterval(function() {
                fetch('api_lotteries.php?action=detail&id=' + lotteryId)
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            var el = document.getElementById('participantNum');
                            if (el) el.textContent = res.data.participant_count;
                        }
                    });
            }, 5000);
        }

        function handleParticipate() {
            var btn = document.getElementById('participateBtn');
            if (!btn || btn.disabled) return;
            if (currentLottery.runtime_status !== 'ongoing') {
                showToast('当前不可参与', 'info');
                return;
            }

            btn.disabled = true;
            btn.textContent = '提交中...';

            fetch('api_lotteries.php?action=eligibility&id=' + lotteryId)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        showToast(res.message || '检查资格失败', 'error');
                        btn.disabled = false;
                        btn.textContent = '立即参与';
                        return;
                    }
                    if (!res.data.eligible) {
                        showToast(res.data.reason || '您不满足参与条件', 'info');
                        btn.disabled = false;
                        if (res.data.has_participated) {
                            btn.textContent = '已参与';
                            btn.classList.remove('primary');
                            btn.classList.add('secondary');
                        } else {
                            btn.textContent = '立即参与';
                        }
                        return;
                    }

                    fetch('api_lotteries.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'participate', id: lotteryId })
                    }).then(function(r) { return r.json(); })
                    .then(function(res2) {
                        if (res2.success) {
                            showToast(res2.message || '参与成功！', 'success');
                            btn.textContent = '已参与';
                            btn.classList.remove('primary');
                            btn.classList.add('secondary');
                            loadDetail();
                        } else {
                            showToast(res2.message || '参与失败', 'error');
                            btn.disabled = false;
                            btn.textContent = '立即参与';
                        }
                    });
                });
        }

        function loadWinners() {
            fetch('api_lotteries.php?action=winners&id=' + lotteryId)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var box = document.getElementById('winnersList');
                    if (!box) return;
                    if (!res.success) {
                        box.innerHTML = '<div class="empty-state"><p>' + (res.message || '加载失败') + '</p></div>';
                        return;
                    }
                    if (!res.data || res.data.length === 0) {
                        box.innerHTML = '<div class="empty-state"><p>暂无中奖记录</p></div>';
                        return;
                    }
                    var html = '<div class="winners-list">';
                    res.data.forEach(function(w) {
                        html += '<div class="winner-item">' +
                            '<div style="display:flex;flex-direction:column;gap:4px;">' +
                                '<span class="winner-prize">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path d="M5 16L3 5L8.5 10L12 4L15.5 10L21 5L19 16M5 16C5 16.5304 5.21071 17.0391 5.58579 17.4142C5.96086 17.7893 6.46957 18 7 18H17C17.5304 18 18.0391 17.7893 18.4142 17.4142C18.7893 17.0391 19 16.5304 19 16M5 16V19C5 19.5304 5.21071 20.0391 5.58579 20.4142C5.96086 20.7893 6.46957 21 7 21H17C17.5304 21 18.0391 20.7893 18.4142 20.4142C18.7893 20.0391 19 19.5304 19 19V16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                                    escapeHtml(w.prize_name) +
                                '</span>' +
                                '<span class="winner-visitor">中奖者：' + escapeHtml(w.visitor_id) + '</span>' +
                            '</div>' +
                            '<span class="winner-time">' + formatDate(w.drawn_at) + '</span>' +
                        '</div>';
                    });
                    html += '</div>';
                    box.innerHTML = html;
                });
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

        loadDetail();

        window.addEventListener('beforeunload', function() {
            if (countdownInterval) clearInterval(countdownInterval);
            if (participantRefreshInterval) clearInterval(participantRefreshInterval);
        });
    </script>
</body>
</html>
