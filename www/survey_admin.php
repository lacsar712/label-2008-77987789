<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureSurveyTables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>问卷管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .survey-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-lg);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-md);
            transition: all 0.3s ease;
        }
        .survey-list-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .survey-info {
            flex: 1;
        }
        .survey-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        .survey-desc {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-sm);
        }
        .survey-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .survey-actions {
            display: flex;
            gap: var(--spacing-sm);
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-lg);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal {
            background: var(--bg-secondary);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            background: var(--bg-secondary);
            z-index: 1;
        }
        .modal-header h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
        }
        .modal-close {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal-close:hover {
            background: var(--error-color);
            border-color: var(--error-color);
            color: white;
        }
        .modal-body {
            padding: var(--spacing-xl);
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
            padding: var(--spacing-xl);
            border-top: 1px solid var(--border-color);
            position: sticky;
            bottom: 0;
            background: var(--bg-secondary);
        }
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: var(--spacing-sm);
            font-size: 0.875rem;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-md);
        }
        .questions-section {
            margin-top: var(--spacing-2xl);
        }
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        .section-title h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
        }
        .question-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md);
            cursor: move;
            transition: all 0.2s ease;
        }
        .question-item:hover {
            border-color: var(--primary-color);
        }
        .question-item.dragging {
            opacity: 0.5;
            transform: scale(1.02);
        }
        .question-item.drag-over {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--primary-color);
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }
        .question-type-badge {
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        .question-type-badge.single {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }
        .question-type-badge.multiple {
            background: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
        }
        .question-type-badge.text {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .question-actions {
            display: flex;
            gap: var(--spacing-xs);
            flex-shrink: 0;
        }
        .question-action-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .question-action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .question-action-btn.delete:hover {
            border-color: var(--error-color);
            color: var(--error-color);
        }
        .question-action-btn svg {
            width: 14px;
            height: 14px;
        }
        .drag-handle {
            cursor: grab;
            color: var(--text-muted);
        }
        .drag-handle:active {
            cursor: grabbing;
        }
        .option-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
        }
        .option-item input {
            flex: 1;
        }
        .remove-option-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .remove-option-btn:hover {
            border-color: var(--error-color);
            color: var(--error-color);
        }
        .add-option-btn {
            width: 100%;
            padding: var(--spacing-sm);
            background: transparent;
            border: 1px dashed var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        .add-option-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .required-row {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }
        .required-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .required-row label {
            margin: 0;
            cursor: pointer;
        }
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
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 26px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bg-tertiary);
            transition: .3s;
            border-radius: 26px;
            border: 1px solid var(--border-color);
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: var(--text-muted);
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        input:checked + .slider:before {
            transform: translateX(22px);
            background-color: white;
        }
        .stats-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.2rem 0.6rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-light);
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
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
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php" class="active">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <div class="section-header" style="margin-bottom: var(--spacing-lg);">
                    <h2>问卷管理</h2>
                    <button class="btn btn-primary" onclick="openEditor()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新建问卷
                    </button>
                </div>
                <form class="search-form" onsubmit="return false;">
                    <div class="search-fields">
                        <div class="search-field">
                            <label for="searchKeyword">关键词</label>
                            <input type="text" id="searchKeyword" placeholder="搜索问卷标题">
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-primary" onclick="loadSurveys(1)">
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
                <p id="resultsInfo">共找到 <strong>0</strong> 份问卷，当前第 <strong>1</strong> / <strong>1</strong> 页</p>
            </div>

            <div id="surveyList"></div>

            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <div class="modal-overlay" id="editorModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">新建问卷</h2>
                <button class="modal-close" onclick="closeEditor()">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="surveyTitle">问卷标题 <span class="required">*</span></label>
                    <input type="text" id="surveyTitle" placeholder="请输入问卷标题">
                </div>
                <div class="form-group">
                    <label for="surveyDesc">问卷描述</label>
                    <textarea id="surveyDesc" rows="3" placeholder="请输入问卷描述"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="startTime">开始时间</label>
                        <input type="datetime-local" id="startTime">
                    </div>
                    <div class="form-group">
                        <label for="endTime">结束时间</label>
                        <input type="datetime-local" id="endTime">
                    </div>
                </div>
                <div class="form-group">
                    <div class="required-row">
                        <label class="switch">
                            <input type="checkbox" id="isEnabled" checked>
                            <span class="slider"></span>
                        </label>
                        <label for="isEnabled">启用问卷</label>
                    </div>
                </div>

                <div class="questions-section">
                    <div class="section-title">
                        <h3>题目列表</h3>
                        <div style="display: flex; gap: var(--spacing-sm);">
                            <button class="btn btn-secondary" onclick="addQuestion('single')">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                添加单选
                            </button>
                            <button class="btn btn-secondary" onclick="addQuestion('multiple')">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                添加多选
                            </button>
                            <button class="btn btn-secondary" onclick="addQuestion('text')">
                                <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                添加简答
                            </button>
                        </div>
                    </div>
                    <div id="questionsContainer"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditor()">取消</button>
                <button class="btn btn-primary" onclick="saveSurvey()">保存</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
        var currentPage = 1;
        var totalPages = 1;
        var editingId = null;
        var questions = [];
        var questionIdCounter = 0;
        var draggedIndex = null;

        var typeLabels = {
            single: '单选',
            multiple: '多选',
            text: '简答'
        };

        function loadSurveys(page) {
            page = page || 1;
            currentPage = page;
            var keyword = document.getElementById('searchKeyword').value.trim();
            var params = { page: page, per_page: 10 };
            if (keyword) params.keyword = keyword;

            fetch('api_surveys.php?' + new URLSearchParams(params))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        alert(res.message || '加载失败');
                        return;
                    }
                    renderSurveyList(res.data.list);
                    renderPagination(res.data.pagination);
                });
        }

        function renderSurveyList(list) {
            var container = document.getElementById('surveyList');
            if (!list || list.length === 0) {
                container.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M9 12H15M9 16H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无问卷，点击"新建问卷"创建</p></div>';
                return;
            }

            var html = '';
            list.forEach(function(s) {
                var dateRange = '';
                if (s.start_time || s.end_time) {
                    dateRange = '有效期：' + (s.start_time ? formatDate(s.start_time) : '不限') + ' 至 ' + (s.end_time ? formatDate(s.end_time) : '不限');
                }
                html += '<div class="survey-list-item">' +
                    '<div class="survey-info">' +
                        '<div class="survey-title">' + escapeHtml(s.title) + '</div>' +
                        '<div class="survey-desc">' + escapeHtml(s.description || '暂无描述') + '</div>' +
                        '<div class="survey-meta">' +
                            '<span>' + s.question_count + ' 题</span>' +
                            '<span class="stats-badge">' + s.response_count + ' 份作答</span>' +
                            (dateRange ? '<span>' + dateRange + '</span>' : '') +
                            '<span>' + formatDate(s.created_at) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="survey-actions">' +
                        '<label class="switch" title="' + (s.is_enabled ? '点击禁用' : '点击启用') + '">' +
                            '<input type="checkbox" ' + (s.is_enabled ? 'checked' : '') + ' onchange="toggleSurvey(' + s.id + ', this)">' +
                            '<span class="slider"></span>' +
                        '</label>' +
                        '<button class="btn btn-secondary" onclick="editSurvey(' + s.id + ')">' +
                            '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '编辑' +
                        '</button>' +
                        '<button class="btn btn-secondary" onclick="viewResults(' + s.id + ')">' +
                            '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 19V6L12 3L15 6V19M9 19H15M9 19H5M15 19H19M5 19V10M19 19V10M5 10H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '结果' +
                        '</button>' +
                        '<button class="btn btn-secondary" onclick="deleteSurvey(' + s.id + ')" style="border-color: var(--error-color); color: var(--error-color);">' +
                            '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 7L18.1327 19.1425C18.0588 20.1891 17.187 21 16.1378 21H7.86216C6.81296 21 5.94115 20.1891 5.86725 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '删除' +
                        '</button>' +
                    '</div>' +
                '</div>';
            });
            container.innerHTML = html;
        }

        function renderPagination(pagination) {
            totalPages = pagination.total_pages;
            document.getElementById('resultsInfo').innerHTML =
                '共找到 <strong>' + pagination.total + '</strong> 份问卷，当前第 <strong>' + pagination.page + '</strong> / <strong>' + pagination.total_pages + '</strong> 页';

            var container = document.getElementById('pagination');
            container.innerHTML = '';
            if (totalPages <= 1) return;

            if (currentPage > 1) {
                var prev = document.createElement('a');
                prev.className = 'page-link';
                prev.href = 'javascript:void(0)';
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>上一页';
                prev.onclick = function() { loadSurveys(currentPage - 1); };
                container.appendChild(prev);
            }

            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, currentPage + 2);
            if (start > 1) {
                container.appendChild(createPageNum(1));
                if (start > 2) {
                    var ell = document.createElement('span');
                    ell.className = 'page-ellipsis';
                    ell.textContent = '...';
                    container.appendChild(ell);
                }
            }
            for (var i = start; i <= end; i++) {
                container.appendChild(createPageNum(i));
            }
            if (end < totalPages) {
                if (end < totalPages - 1) {
                    var ell2 = document.createElement('span');
                    ell2.className = 'page-ellipsis';
                    ell2.textContent = '...';
                    container.appendChild(ell2);
                }
                container.appendChild(createPageNum(totalPages));
            }

            if (currentPage < totalPages) {
                var next = document.createElement('a');
                next.className = 'page-link';
                next.href = 'javascript:void(0)';
                next.innerHTML = '下一页<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                next.onclick = function() { loadSurveys(currentPage + 1); };
                container.appendChild(next);
            }
        }

        function createPageNum(page) {
            var a = document.createElement('a');
            a.className = 'page-number' + (page === currentPage ? ' active' : '');
            a.textContent = page;
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadSurveys(page); };
            return a;
        }

        function openEditor() {
            editingId = null;
            questions = [];
            questionIdCounter = 0;
            document.getElementById('modalTitle').textContent = '新建问卷';
            document.getElementById('surveyTitle').value = '';
            document.getElementById('surveyDesc').value = '';
            document.getElementById('startTime').value = '';
            document.getElementById('endTime').value = '';
            document.getElementById('isEnabled').checked = true;
            renderQuestions();
            document.getElementById('editorModal').classList.add('active');
        }

        function closeEditor() {
            document.getElementById('editorModal').classList.remove('active');
        }

        function editSurvey(id) {
            fetch('api_surveys.php?action=detail&id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        alert(res.message || '加载失败');
                        return;
                    }
                    var d = res.data;
                    editingId = id;
                    document.getElementById('modalTitle').textContent = '编辑问卷';
                    document.getElementById('surveyTitle').value = d.title;
                    document.getElementById('surveyDesc').value = d.description || '';
                    document.getElementById('startTime').value = d.start_time ? d.start_time.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('endTime').value = d.end_time ? d.end_time.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('isEnabled').checked = d.is_enabled;
                    questions = d.questions.map(function(q, i) {
                        return {
                            _tempId: ++questionIdCounter,
                            question_text: q.question_text,
                            question_type: q.question_type,
                            sort_order: q.sort_order,
                            is_required: q.is_required,
                            options: q.options.map(function(o) {
                                return { option_text: o.option_text, sort_order: o.sort_order };
                            })
                        };
                    });
                    renderQuestions();
                    document.getElementById('editorModal').classList.add('active');
                });
        }

        function addQuestion(type) {
            questions.push({
                _tempId: ++questionIdCounter,
                question_text: '',
                question_type: type,
                sort_order: questions.length,
                is_required: true,
                options: type !== 'text' ? [
                    { option_text: '', sort_order: 0 },
                    { option_text: '', sort_order: 1 }
                ] : []
            });
            renderQuestions();
        }

        function removeQuestion(tempId) {
            if (!confirm('确定删除该题目吗？')) return;
            questions = questions.filter(function(q) { return q._tempId !== tempId; });
            questions.forEach(function(q, i) { q.sort_order = i; });
            renderQuestions();
        }

        function updateQuestion(tempId, field, value) {
            var q = questions.find(function(q) { return q._tempId === tempId; });
            if (q) q[field] = value;
        }

        function addOption(tempId) {
            var q = questions.find(function(q) { return q._tempId === tempId; });
            if (q) {
                q.options.push({ option_text: '', sort_order: q.options.length });
                renderQuestions();
            }
        }

        function removeOption(tempId, optIndex) {
            var q = questions.find(function(q) { return q._tempId === tempId; });
            if (q && q.options.length > 2) {
                q.options.splice(optIndex, 1);
                q.options.forEach(function(o, i) { o.sort_order = i; });
                renderQuestions();
            } else {
                alert('至少需要保留2个选项');
            }
        }

        function updateOption(tempId, optIndex, value) {
            var q = questions.find(function(q) { return q._tempId === tempId; });
            if (q && q.options[optIndex]) {
                q.options[optIndex].option_text = value;
            }
        }

        function renderQuestions() {
            var container = document.getElementById('questionsContainer');
            if (questions.length === 0) {
                container.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.228 9C8.73983 7.8342 9.96676 7 11.4 7C13.3875 7 15 8.79086 15 11C15 12.6569 13.6569 14 12 14C11.2817 14 10.6279 13.7895 10.0858 13.4202L9 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无题目，点击上方按钮添加</p></div>';
                return;
            }

            var html = '';
            questions.forEach(function(q, index) {
                html += '<div class="question-item" draggable="true" data-index="' + index + '" ondragstart="handleDragStart(event, ' + index + ')" ondragend="handleDragEnd(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event, ' + index + ')">' +
                    '<div class="question-header">' +
                        '<div style="flex: 1;">' +
                            '<div style="display: flex; align-items: center; gap: var(--spacing-sm); margin-bottom: var(--spacing-sm);">' +
                                '<span class="drag-handle" title="拖拽排序">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"><circle cx="9" cy="6" r="1.5" fill="currentColor"/><circle cx="9" cy="12" r="1.5" fill="currentColor"/><circle cx="9" cy="18" r="1.5" fill="currentColor"/><circle cx="15" cy="6" r="1.5" fill="currentColor"/><circle cx="15" cy="12" r="1.5" fill="currentColor"/><circle cx="15" cy="18" r="1.5" fill="currentColor"/></svg>' +
                                '</span>' +
                                '<span class="question-type-badge ' + q.question_type + '">' + typeLabels[q.question_type] + '</span>' +
                            '</div>' +
                            '<input type="text" value="' + escapeHtml(q.question_text) + '" placeholder="请输入题目内容" onchange="updateQuestion(' + q._tempId + ', \'question_text\', this.value)">' +
                        '</div>' +
                        '<div class="question-actions">' +
                            '<button class="question-action-btn delete" onclick="removeQuestion(' + q._tempId + ')" title="删除">' +
                                '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 7L18.1327 19.1425C18.0588 20.1891 17.187 21 16.1378 21H7.86216C6.81296 21 5.94115 20.1891 5.86725 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '</button>' +
                        '</div>' +
                    '</div>';

                if (q.question_type !== 'text') {
                    html += '<div style="padding-left: 34px;">';
                    q.options.forEach(function(opt, optIndex) {
                        html += '<div class="option-item">' +
                            '<input type="text" value="' + escapeHtml(opt.option_text) + '" placeholder="选项 ' + (optIndex + 1) + '" onchange="updateOption(' + q._tempId + ', ' + optIndex + ', this.value)">' +
                            '<button class="remove-option-btn" onclick="removeOption(' + q._tempId + ', ' + optIndex + ')" title="删除选项">' +
                                '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="14" height="14"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '</button>' +
                        '</div>';
                    });
                    html += '<button class="add-option-btn" onclick="addOption(' + q._tempId + ')">+ 添加选项</button>';
                    html += '</div>';
                }

                html += '<div class="required-row" style="padding-left: 34px;">' +
                    '<input type="checkbox" id="req_' + q._tempId + '" ' + (q.is_required ? 'checked' : '') + ' onchange="updateQuestion(' + q._tempId + ', \'is_required\', this.checked)">' +
                    '<label for="req_' + q._tempId + '">必填</label>' +
                '</div>';

                html += '</div>';
            });
            container.innerHTML = html;
        }

        function handleDragStart(e, index) {
            draggedIndex = index;
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }

        function handleDragEnd(e) {
            e.target.classList.remove('dragging');
            document.querySelectorAll('.question-item').forEach(function(el) {
                el.classList.remove('drag-over');
            });
        }

        function handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            e.currentTarget.classList.add('drag-over');
        }

        function handleDragLeave(e) {
            e.currentTarget.classList.remove('drag-over');
        }

        function handleDrop(e, dropIndex) {
            e.preventDefault();
            e.currentTarget.classList.remove('drag-over');
            if (draggedIndex === null || draggedIndex === dropIndex) return;
            var item = questions[draggedIndex];
            questions.splice(draggedIndex, 1);
            questions.splice(dropIndex, 0, item);
            questions.forEach(function(q, i) { q.sort_order = i; });
            renderQuestions();
        }

        function saveSurvey() {
            var title = document.getElementById('surveyTitle').value.trim();
            if (!title) {
                alert('请填写问卷标题');
                return;
            }

            for (var i = 0; i < questions.length; i++) {
                var q = questions[i];
                if (!q.question_text.trim()) {
                    alert('第 ' + (i + 1) + ' 题的题目内容不能为空');
                    return;
                }
                if (q.question_type !== 'text') {
                    if (q.options.length < 2) {
                        alert('第 ' + (i + 1) + ' 题至少需要2个选项');
                        return;
                    }
                    for (var j = 0; j < q.options.length; j++) {
                        if (!q.options[j].option_text.trim()) {
                            alert('第 ' + (i + 1) + ' 题的选项 ' + (j + 1) + ' 内容不能为空');
                            return;
                        }
                    }
                }
            }

            var payload = {
                action: editingId ? 'update' : 'create',
                title: title,
                description: document.getElementById('surveyDesc').value.trim() || null,
                start_time: document.getElementById('startTime').value || null,
                end_time: document.getElementById('endTime').value || null,
                is_enabled: document.getElementById('isEnabled').checked ? 1 : 0,
                questions: questions.map(function(q, i) {
                    return {
                        question_text: q.question_text,
                        question_type: q.question_type,
                        sort_order: i,
                        is_required: q.is_required ? 1 : 0,
                        options: q.options.map(function(o, j) {
                            return {
                                option_text: o.option_text,
                                sort_order: j
                            };
                        })
                    };
                })
            };

            if (editingId) payload.id = editingId;

            fetch('api_surveys.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    alert(res.message);
                    closeEditor();
                    loadSurveys(currentPage);
                } else {
                    alert(res.message || '保存失败');
                }
            });
        }

        function toggleSurvey(id, checkbox) {
            fetch('api_surveys.php?action=toggle&id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        alert(res.message || '操作失败');
                        checkbox.checked = !checkbox.checked;
                    }
                });
        }

        function deleteSurvey(id) {
            if (!confirm('确定删除该问卷吗？关联的公告也会解除绑定。')) return;
            fetch('api_surveys.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    alert(res.message);
                    loadSurveys(currentPage);
                } else {
                    alert(res.message || '删除失败');
                }
            });
        }

        function viewResults(id) {
            window.location.href = 'survey_results.php?id=' + id;
        }

        function resetFilters() {
            document.getElementById('searchKeyword').value = '';
            loadSurveys(1);
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

        loadSurveys(1);
    </script>
</body>
</html>
