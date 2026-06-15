<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告日历 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .calendar-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            padding: var(--spacing-lg);
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            margin-bottom: var(--spacing-lg);
        }
        .calendar-toolbar-left,
        .calendar-toolbar-right {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }
        .calendar-nav-btn {
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
            transition: all 0.2s ease;
        }
        .calendar-nav-btn:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        .calendar-nav-btn svg {
            width: 18px;
            height: 18px;
        }
        .calendar-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            min-width: 160px;
            text-align: center;
        }
        .calendar-today-btn {
            padding: 8px 16px;
            border-radius: var(--radius-md);
            background: var(--gradient-primary);
            color: white;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .calendar-today-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .view-switcher {
            display: flex;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            padding: 4px;
            border: 1px solid var(--border-color);
        }
        .view-switcher-btn {
            padding: 6px 14px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            background: transparent;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        .view-switcher-btn:hover {
            color: var(--text-primary);
        }
        .view-switcher-btn.active {
            background: var(--primary-color);
            color: white;
        }
        .priority-filter {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            background: var(--bg-tertiary);
            padding: 6px 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }
        .priority-filter-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .priority-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            user-select: none;
        }
        .priority-checkbox input {
            display: none;
        }
        .priority-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid transparent;
            transition: all 0.2s ease;
            opacity: 0.4;
        }
        .priority-checkbox input:checked + .priority-dot {
            opacity: 1;
            transform: scale(1.1);
        }
        .priority-dot.high { background: var(--priority-high); }
        .priority-dot.medium { background: var(--priority-medium); }
        .priority-dot.low { background: var(--priority-low); }

        .calendar-container {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
        }
        .calendar-weekday {
            padding: var(--spacing-md);
            text-align: center;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .calendar-weekday.weekend {
            color: var(--warning-color);
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
        }
        .calendar-day {
            min-height: 130px;
            padding: var(--spacing-sm);
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: relative;
            background: var(--bg-secondary);
        }
        .calendar-day:nth-child(7n) {
            border-right: none;
        }
        .calendar-day:hover {
            background: var(--bg-tertiary);
        }
        .calendar-day.other-month {
            background: rgba(30, 41, 59, 0.5);
            opacity: 0.5;
        }
        .calendar-day.other-month:hover {
            background: var(--bg-tertiary);
            opacity: 0.8;
        }
        .calendar-day.today {
            background: rgba(99, 102, 241, 0.08);
        }
        .calendar-day.today::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
        }
        .calendar-day-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .calendar-day-number {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9375rem;
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .calendar-day.today .calendar-day-number {
            background: var(--primary-color);
            color: white;
        }
        .calendar-badges {
            display: flex;
            gap: 3px;
        }
        .calendar-badge {
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 999px;
            color: white;
            min-width: 20px;
            text-align: center;
        }
        .calendar-badge.high { background: var(--priority-high); }
        .calendar-badge.medium { background: var(--priority-medium); }
        .calendar-badge.low { background: var(--priority-low); }

        .calendar-notices {
            display: flex;
            flex-direction: column;
            gap: 3px;
            flex: 1;
            overflow: hidden;
        }
        .calendar-notice-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            padding: 3px 6px;
            border-radius: var(--radius-sm);
            background: rgba(99, 102, 241, 0.1);
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: all 0.2s ease;
            line-height: 1.3;
        }
        .calendar-notice-item:hover {
            background: rgba(99, 102, 241, 0.2);
            color: var(--text-primary);
        }
        .calendar-notice-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .calendar-notice-dot.high { background: var(--priority-high); }
        .calendar-notice-dot.medium { background: var(--priority-medium); }
        .calendar-notice-dot.low { background: var(--priority-low); }
        .calendar-notice-title {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .calendar-more-btn {
            font-size: 0.75rem;
            color: var(--primary-color);
            font-weight: 500;
            padding: 2px 6px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .calendar-more-btn:hover {
            background: rgba(99, 102, 241, 0.15);
        }

        .week-view-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: var(--spacing-md);
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }
        .week-view-row {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            min-height: 80px;
        }
        .week-view-day {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-right: 1px solid var(--border-color);
        }
        .week-view-day.today {
            background: rgba(99, 102, 241, 0.15);
            border-radius: var(--radius-md);
        }
        .week-view-weekday {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        .week-view-date {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .week-view-day.today .week-view-date {
            color: var(--primary-color);
        }
        .week-view-badges {
            display: flex;
            gap: 4px;
        }
        .week-view-content {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .week-view-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: 8px 12px;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .week-view-item:hover {
            background: rgba(99, 102, 241, 0.1);
            transform: translateX(3px);
        }
        .week-view-item-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .week-view-item-dot.high { background: var(--priority-high); }
        .week-view-item-dot.medium { background: var(--priority-medium); }
        .week-view-item-dot.low { background: var(--priority-low); }
        .week-view-item-info {
            flex: 1;
            overflow: hidden;
        }
        .week-view-item-title {
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .week-view-item-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .list-view-container {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .list-view-date-group {
            border-bottom: 1px solid var(--border-color);
        }
        .list-view-date-group:last-child {
            border-bottom: none;
        }
        .list-view-date-header {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--bg-tertiary);
            position: sticky;
            top: 0;
        }
        .list-view-date-info {
            display: flex;
            flex-direction: column;
        }
        .list-view-date-main {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .list-view-date-sub {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .list-view-date-badges {
            display: flex;
            gap: 6px;
            margin-left: auto;
        }
        .list-view-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .list-view-item:last-child {
            border-bottom: none;
        }
        .list-view-item:hover {
            background: rgba(99, 102, 241, 0.08);
        }
        .list-view-item-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .list-view-item-dot.high { background: var(--priority-high); }
        .list-view-item-dot.medium { background: var(--priority-medium); }
        .list-view-item-dot.low { background: var(--priority-low); }
        .list-view-item-content {
            flex: 1;
            overflow: hidden;
        }
        .list-view-item-title {
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .list-view-item-excerpt {
            font-size: 0.8125rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .list-view-item-meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: flex-end;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .side-panel-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .side-panel-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .side-panel {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 450px;
            max-width: 100%;
            background: var(--bg-secondary);
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.3);
        }
        .side-panel.active {
            transform: translateX(0);
        }
        .side-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
        }
        .side-panel-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .side-panel-close {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            background: var(--bg-tertiary);
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .side-panel-close:hover {
            background: var(--error-color);
            color: white;
        }
        .side-panel-close svg {
            width: 18px;
            height: 18px;
        }
        .side-panel-stats {
            display: flex;
            gap: var(--spacing-md);
            padding: var(--spacing-md) var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
            background: var(--bg-tertiary);
        }
        .side-panel-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }
        .side-panel-stat-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .side-panel-stat-dot.high { background: var(--priority-high); }
        .side-panel-stat-dot.medium { background: var(--priority-medium); }
        .side-panel-stat-dot.low { background: var(--priority-low); }
        .side-panel-body {
            flex: 1;
            overflow-y: auto;
            padding: var(--spacing-md);
        }
        .side-panel-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .side-panel-item {
            padding: var(--spacing-md);
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }
        .side-panel-item:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        .side-panel-item-header {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            margin-bottom: 8px;
        }
        .side-panel-item-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 5px;
            flex-shrink: 0;
        }
        .side-panel-item-dot.high { background: var(--priority-high); }
        .side-panel-item-dot.medium { background: var(--priority-medium); }
        .side-panel-item-dot.low { background: var(--priority-low); }
        .side-panel-item-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9375rem;
            flex: 1;
            line-height: 1.4;
        }
        .side-panel-item-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .side-panel-item-excerpt {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        .side-panel-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .side-panel-empty svg {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }

        .calendar-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-2xl);
        }
        .loading-spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .calendar-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .calendar-toolbar-left,
            .calendar-toolbar-right {
                justify-content: center;
            }
            .calendar-day {
                min-height: 80px;
                padding: 4px;
            }
            .calendar-notice-item {
                font-size: 0.6875rem;
                padding: 2px 4px;
            }
            .side-panel {
                width: 100%;
            }
            .calendar-badges {
                display: none;
            }
            .week-view-row {
                grid-template-columns: 70px 1fr;
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
                <li><a href="notice_calendar.php" class="active">公告日历</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="calendar-toolbar">
                <div class="calendar-toolbar-left">
                    <button class="calendar-nav-btn" onclick="navigatePrev()" title="上一页">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="calendar-title" id="calendarTitle">2024年1月</div>
                    <button class="calendar-nav-btn" onclick="navigateNext()" title="下一页">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="calendar-today-btn" onclick="goToToday()">回到今日</button>
                </div>
                <div class="calendar-toolbar-right">
                    <div class="view-switcher">
                        <button class="view-switcher-btn active" data-view="month" onclick="switchView('month')">月</button>
                        <button class="view-switcher-btn" data-view="week" onclick="switchView('week')">周</button>
                        <button class="view-switcher-btn" data-view="list" onclick="switchView('list')">列表</button>
                    </div>
                    <div class="priority-filter">
                        <span class="priority-filter-label">优先级：</span>
                        <label class="priority-checkbox">
                            <input type="checkbox" value="high" checked onchange="updatePriorityFilter()">
                            <span class="priority-dot high"></span>
                        </label>
                        <label class="priority-checkbox">
                            <input type="checkbox" value="medium" checked onchange="updatePriorityFilter()">
                            <span class="priority-dot medium"></span>
                        </label>
                        <label class="priority-checkbox">
                            <input type="checkbox" value="low" checked onchange="updatePriorityFilter()">
                            <span class="priority-dot low"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div id="calendarContent">
                <div class="calendar-loading">
                    <div class="loading-spinner"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="side-panel-overlay" id="sidePanelOverlay" onclick="closeSidePanel()"></div>
    <div class="side-panel" id="sidePanel">
        <div class="side-panel-header">
            <div class="side-panel-title" id="sidePanelTitle">2024年1月1日 公告</div>
            <button class="side-panel-close" onclick="closeSidePanel()">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="side-panel-stats" id="sidePanelStats"></div>
        <div class="side-panel-body" id="sidePanelBody">
            <div class="calendar-loading">
                <div class="loading-spinner"></div>
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
        var currentDate = new Date();
        var currentYear = currentDate.getFullYear();
        var currentMonth = currentDate.getMonth();
        var currentView = 'month';
        var weekStartDate = null;
        var selectedPriorities = ['high', 'medium', 'low'];
        var cachedMonthData = null;
        var cachedWeekData = null;

        var weekdayNames = ['日', '一', '二', '三', '四', '五', '六'];
        var monthNames = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'];

        function init() {
            updateTitle();
            loadData();
        }

        function updateTitle() {
            var titleEl = document.getElementById('calendarTitle');
            if (currentView === 'month') {
                titleEl.textContent = currentYear + '年' + (currentMonth + 1) + '月';
            } else if (currentView === 'week') {
                if (!weekStartDate) {
                    setWeekFromDate(new Date());
                }
                var weekEnd = new Date(weekStartDate);
                weekEnd.setDate(weekEnd.getDate() + 6);
                titleEl.textContent = formatDate(weekStartDate) + ' - ' + formatDate(weekEnd);
            } else {
                titleEl.textContent = '公告列表';
            }
        }

        function setWeekFromDate(date) {
            weekStartDate = new Date(date);
            var day = weekStartDate.getDay();
            var diff = weekStartDate.getDate() - day + (day === 0 ? -6 : 1);
            weekStartDate = new Date(weekStartDate.setDate(diff));
            weekStartDate.setHours(0, 0, 0, 0);
        }

        function formatDate(date) {
            return (date.getMonth() + 1) + '月' + date.getDate() + '日';
        }

        function formatFullDate(date) {
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
        }

        function getPriorityParam() {
            return selectedPriorities.length > 0 ? '&priorities=' + selectedPriorities.join(',') : '';
        }

        function loadData() {
            var container = document.getElementById('calendarContent');
            container.innerHTML = '<div class="calendar-loading"><div class="loading-spinner"></div></div>';

            if (currentView === 'month') {
                loadMonthView();
            } else if (currentView === 'week') {
                loadWeekView();
            } else {
                loadListView();
            }
        }

        function loadMonthView() {
            var url = 'api_notice_calendar.php?action=month_aggregate&year=' + currentYear + '&month=' + (currentMonth + 1) + getPriorityParam();

            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        cachedMonthData = data.data;
                        renderMonthView(data.data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(function() {
                    showError('加载失败，请稍后重试');
                });
        }

        function renderMonthView(data) {
            var container = document.getElementById('calendarContent');
            var today = new Date();
            var todayStr = formatFullDate(today);

            var firstDay = new Date(data.year, data.month - 1, 1);
            var startWeekday = firstDay.getDay();
            var daysInMonth = new Date(data.year, data.month, 0).getDate();

            var prevMonth = data.month - 1 < 1 ? 12 : data.month - 1;
            var prevYear = data.month - 1 < 1 ? data.year - 1 : data.year;
            var daysInPrevMonth = new Date(prevYear, prevMonth, 0).getDate();

            var daysMap = {};
            data.days.forEach(function(d) {
                daysMap[d.date] = d;
            });

            var html = '<div class="calendar-container">';
            html += '<div class="calendar-weekdays">';
            for (var i = 0; i < 7; i++) {
                var isWeekend = i === 0 || i === 6;
                html += '<div class="calendar-weekday' + (isWeekend ? ' weekend' : '') + '">周' + weekdayNames[i] + '</div>';
            }
            html += '</div>';
            html += '<div class="calendar-days">';

            var totalCells = 42;
            for (var cell = 0; cell < totalCells; cell++) {
                var dayNum, dateStr, isOtherMonth = false;
                var cellYear, cellMonth;

                if (cell < startWeekday) {
                    dayNum = daysInPrevMonth - startWeekday + cell + 1;
                    cellYear = prevYear;
                    cellMonth = prevMonth;
                    isOtherMonth = true;
                } else if (cell >= startWeekday + daysInMonth) {
                    dayNum = cell - startWeekday - daysInMonth + 1;
                    cellYear = data.month === 12 ? data.year + 1 : data.year;
                    cellMonth = data.month === 12 ? 1 : data.month + 1;
                    isOtherMonth = true;
                } else {
                    dayNum = cell - startWeekday + 1;
                    cellYear = data.year;
                    cellMonth = data.month;
                }

                dateStr = cellYear + '-' + String(cellMonth).padStart(2, '0') + '-' + String(dayNum).padStart(2, '0');
                var dayData = daysMap[dateStr] || { total: 0, high_count: 0, medium_count: 0, low_count: 0, notices: [] };
                var isToday = dateStr === todayStr;
                var weekday = new Date(cellYear, cellMonth - 1, dayNum).getDay();
                var isWeekend = weekday === 0 || weekday === 6;

                html += '<div class="calendar-day' + (isOtherMonth ? ' other-month' : '') + (isToday ? ' today' : '') + '" onclick="openDayDetail(\'' + dateStr + '\')">';
                html += '<div class="calendar-day-header">';
                html += '<span class="calendar-day-number">' + dayNum + '</span>';
                html += '<div class="calendar-badges">';
                if (dayData.high_count > 0) html += '<span class="calendar-badge high">' + dayData.high_count + '</span>';
                if (dayData.medium_count > 0) html += '<span class="calendar-badge medium">' + dayData.medium_count + '</span>';
                if (dayData.low_count > 0) html += '<span class="calendar-badge low">' + dayData.low_count + '</span>';
                html += '</div>';
                html += '</div>';

                html += '<div class="calendar-notices">';
                dayData.notices.forEach(function(n) {
                    html += '<div class="calendar-notice-item" onclick="event.stopPropagation(); gotoNotice(' + n.id + ')">';
                    html += '<span class="calendar-notice-dot ' + n.priority + '"></span>';
                    html += '<span class="calendar-notice-title">' + htmlEscape(n.title) + '</span>';
                    html += '</div>';
                });
                var remaining = dayData.total - dayData.notices.length;
                if (remaining > 0) {
                    html += '<div class="calendar-more-btn">+' + remaining + ' 更多</div>';
                }
                html += '</div>';

                html += '</div>';
            }

            html += '</div>';
            html += '</div>';
            container.innerHTML = html;
        }

        function loadWeekView() {
            if (!weekStartDate) {
                setWeekFromDate(new Date());
            }
            var weekEnd = new Date(weekStartDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            var startStr = formatFullDate(weekStartDate);
            var endStr = formatFullDate(weekEnd);

            var url = 'api_notice_calendar.php?action=week_aggregate&start_date=' + startStr + '&end_date=' + endStr + getPriorityParam();

            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        cachedWeekData = data.data;
                        renderWeekView(data.data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(function() {
                    showError('加载失败，请稍后重试');
                });
        }

        function renderWeekView(data) {
            var container = document.getElementById('calendarContent');
            var today = new Date();
            var todayStr = formatFullDate(today);

            var html = '<div class="week-view-container">';
            data.days.forEach(function(day) {
                var dateObj = new Date(day.date);
                var isToday = day.date === todayStr;

                html += '<div class="week-view-row" onclick="openDayDetail(\'' + day.date + '\')">';
                html += '<div class="week-view-day' + (isToday ? ' today' : '') + '">';
                html += '<span class="week-view-weekday">周' + weekdayNames[dateObj.getDay()] + '</span>';
                html += '<span class="week-view-date">' + dateObj.getDate() + '</span>';
                html += '<div class="week-view-badges">';
                if (day.high_count > 0) html += '<span class="calendar-badge high">' + day.high_count + '</span>';
                if (day.medium_count > 0) html += '<span class="calendar-badge medium">' + day.medium_count + '</span>';
                if (day.low_count > 0) html += '<span class="calendar-badge low">' + day.low_count + '</span>';
                html += '</div>';
                html += '</div>';

                html += '<div class="week-view-content">';
                if (day.notices.length === 0) {
                    html += '<div style="color:var(--text-muted);font-size:0.875rem;display:flex;align-items:center;height:100%;">暂无公告</div>';
                } else {
                    day.notices.forEach(function(n) {
                        html += '<div class="week-view-item" onclick="event.stopPropagation(); gotoNotice(' + n.id + ')">';
                        html += '<span class="week-view-item-dot ' + n.priority + '"></span>';
                        html += '<div class="week-view-item-info">';
                        html += '<div class="week-view-item-title">' + htmlEscape(n.title) + '</div>';
                        html += '<div class="week-view-item-meta">' + n.publish_time + ' · ' + htmlEscape(n.author) + '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    if (day.total > day.notices.length) {
                        html += '<div class="calendar-more-btn" style="align-self:flex-start;">+' + (day.total - day.notices.length) + ' 更多</div>';
                    }
                }
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function loadListView() {
            if (currentView === 'list' && weekStartDate) {
            } else {
                setWeekFromDate(new Date(currentYear, currentMonth, 1));
            }

            var listStart = new Date(weekStartDate);
            var listEnd = new Date(listStart);
            listEnd.setDate(listEnd.getDate() + 41);

            var startStr = formatFullDate(listStart);
            var endStr = formatFullDate(listEnd);

            var url = 'api_notice_calendar.php?action=week_aggregate&start_date=' + startStr + '&end_date=' + endStr + getPriorityParam();

            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderListView(data.data);
                    } else {
                        showError(data.message);
                    }
                })
                .catch(function() {
                    showError('加载失败，请稍后重试');
                });
        }

        function renderListView(data) {
            var container = document.getElementById('calendarContent');
            var today = new Date();
            var todayStr = formatFullDate(today);

            var html = '<div class="list-view-container">';
            var hasAny = false;
            data.days.forEach(function(day) {
                if (day.total === 0) return;
                hasAny = true;
                var dateObj = new Date(day.date);
                var isToday = day.date === todayStr;

                html += '<div class="list-view-date-group">';
                html += '<div class="list-view-date-header">';
                html += '<div class="list-view-date-info">';
                html += '<div class="list-view-date-main">' + (dateObj.getMonth() + 1) + '月' + dateObj.getDate() + '日 周' + weekdayNames[dateObj.getDay()] + (isToday ? ' (今天)' : '') + '</div>';
                html += '<div class="list-view-date-sub">共 ' + day.total + ' 条公告</div>';
                html += '</div>';
                html += '<div class="list-view-date-badges">';
                if (day.high_count > 0) html += '<span class="calendar-badge high">' + day.high_count + ' 高</span>';
                if (day.medium_count > 0) html += '<span class="calendar-badge medium">' + day.medium_count + ' 中</span>';
                if (day.low_count > 0) html += '<span class="calendar-badge low">' + day.low_count + ' 低</span>';
                html += '</div>';
                html += '</div>';

                day.notices.forEach(function(n) {
                    html += '<div class="list-view-item" onclick="gotoNotice(' + n.id + ')">';
                    html += '<span class="list-view-item-dot ' + n.priority + '"></span>';
                    html += '<div class="list-view-item-content">';
                    html += '<div class="list-view-item-title">' + htmlEscape(n.title) + '</div>';
                    html += '<div class="list-view-item-excerpt">' + n.publish_time + ' · ' + htmlEscape(n.author) + '</div>';
                    html += '</div>';
                    html += '<div class="list-view-item-meta">';
                    html += '<span>' + (['high' => '高', 'medium' => '中', 'low' => '低'][n.priority] || n.priority) + '优先级</span>';
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
            });

            if (!hasAny) {
                html = '<div class="side-panel-empty">';
                html += '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '<p>暂无公告</p>';
                html += '</div>';
            }

            html += '</div>';
            container.innerHTML = html;
        }

        function showError(msg) {
            var container = document.getElementById('calendarContent');
            container.innerHTML = '<div style="padding:var(--spacing-2xl);text-align:center;color:var(--error-color);">' + msg + '</div>';
        }

        function openDayDetail(dateStr) {
            var overlay = document.getElementById('sidePanelOverlay');
            var panel = document.getElementById('sidePanel');
            var titleEl = document.getElementById('sidePanelTitle');
            var statsEl = document.getElementById('sidePanelStats');
            var bodyEl = document.getElementById('sidePanelBody');

            var dateObj = new Date(dateStr);
            titleEl.textContent = dateObj.getFullYear() + '年' + (dateObj.getMonth() + 1) + '月' + dateObj.getDate() + '日 公告';
            statsEl.innerHTML = '<div class="calendar-loading"><div class="loading-spinner"></div></div>';
            bodyEl.innerHTML = '<div class="calendar-loading"><div class="loading-spinner"></div></div>';

            overlay.classList.add('active');
            panel.classList.add('active');

            var url = 'api_notice_calendar.php?action=date_detail&date=' + dateStr + getPriorityParam();

            fetch(url)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderDayDetail(data.data);
                    } else {
                        bodyEl.innerHTML = '<div class="side-panel-empty"><p>' + data.message + '</p></div>';
                    }
                })
                .catch(function() {
                    bodyEl.innerHTML = '<div class="side-panel-empty"><p>加载失败，请稍后重试</p></div>';
                });
        }

        function renderDayDetail(data) {
            var statsEl = document.getElementById('sidePanelStats');
            var bodyEl = document.getElementById('sidePanelBody');

            statsEl.innerHTML = '';
            if (data.stats.total > 0) {
                statsEl.innerHTML += '<div class="side-panel-stat">共 <strong style="color:var(--text-primary);margin:0 4px;">' + data.stats.total + '</strong> 条</div>';
            }
            if (data.stats.high > 0) statsEl.innerHTML += '<div class="side-panel-stat"><span class="side-panel-stat-dot high"></span>' + data.stats.high + ' 高优</div>';
            if (data.stats.medium > 0) statsEl.innerHTML += '<div class="side-panel-stat"><span class="side-panel-stat-dot medium"></span>' + data.stats.medium + ' 中优</div>';
            if (data.stats.low > 0) statsEl.innerHTML += '<div class="side-panel-stat"><span class="side-panel-stat-dot low"></span>' + data.stats.low + ' 低优</div>';

            if (data.notices.length === 0) {
                bodyEl.innerHTML = '<div class="side-panel-empty">';
                bodyEl.innerHTML += '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                bodyEl.innerHTML += '<p>当天暂无公告</p>';
                bodyEl.innerHTML += '</div>';
                return;
            }

            var html = '<div class="side-panel-list">';
            data.notices.forEach(function(n) {
                html += '<div class="side-panel-item" onclick="gotoNotice(' + n.id + ')">';
                html += '<div class="side-panel-item-header">';
                html += '<span class="side-panel-item-dot ' + n.priority + '"></span>';
                html += '<div class="side-panel-item-title">' + htmlEscape(n.title) + '</div>';
                html += '</div>';
                html += '<div class="side-panel-item-meta">';
                html += '<span>' + n.publish_time + '</span>';
                html += '<span>' + htmlEscape(n.author) + '</span>';
                if (n.category) html += '<span>' + htmlEscape(n.category) + '</span>';
                html += '<span>' + n.views + ' 浏览</span>';
                html += '</div>';
                if (n.excerpt) {
                    html += '<div class="side-panel-item-excerpt">' + htmlEscape(n.excerpt) + '...</div>';
                }
                html += '</div>';
            });
            html += '</div>';
            bodyEl.innerHTML = html;
        }

        function closeSidePanel() {
            document.getElementById('sidePanelOverlay').classList.remove('active');
            document.getElementById('sidePanel').classList.remove('active');
        }

        function gotoNotice(id) {
            window.location.href = 'notice_detail.php?id=' + id;
        }

        window.navigatePrev = function() {
            if (currentView === 'month') {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
            } else {
                if (!weekStartDate) setWeekFromDate(new Date());
                weekStartDate.setDate(weekStartDate.getDate() - 7);
            }
            updateTitle();
            loadData();
        };

        window.navigateNext = function() {
            if (currentView === 'month') {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
            } else {
                if (!weekStartDate) setWeekFromDate(new Date());
                weekStartDate.setDate(weekStartDate.getDate() + 7);
            }
            updateTitle();
            loadData();
        };

        window.goToToday = function() {
            var now = new Date();
            currentYear = now.getFullYear();
            currentMonth = now.getMonth();
            setWeekFromDate(now);
            updateTitle();
            loadData();
        };

        window.switchView = function(view) {
            if (currentView === view) return;
            currentView = view;
            document.querySelectorAll('.view-switcher-btn').forEach(function(btn) {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            var now = new Date();
            setWeekFromDate(now);
            updateTitle();
            loadData();
        };

        window.updatePriorityFilter = function() {
            var checkboxes = document.querySelectorAll('.priority-checkbox input');
            selectedPriorities = [];
            checkboxes.forEach(function(cb) {
                if (cb.checked) selectedPriorities.push(cb.value);
            });
            loadData();
        };

        window.openDayDetail = openDayDetail;
        window.closeSidePanel = closeSidePanel;
        window.gotoNotice = gotoNotice;

        function htmlEscape(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        init();
    })();
    </script>
</body>
</html>
