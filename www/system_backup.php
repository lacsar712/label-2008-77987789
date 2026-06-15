<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统备份 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .backup-header {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            margin-bottom: var(--spacing-xl);
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: var(--spacing-lg);
        }
        .backup-header-info h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }
        .backup-header-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .backup-progress {
            margin-top: var(--spacing-lg);
            width: 100%;
            display: none;
        }
        .backup-progress.show {
            display: block;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: var(--spacing-sm);
        }
        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 4px;
            width: 0%;
            transition: width 0.3s ease;
            animation: progressAnim 2s ease-in-out infinite;
        }
        @keyframes progressAnim {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }
        .progress-text {
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .backup-logs {
            background: #0a0f1a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-md);
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            line-height: 1.6;
            display: none;
        }
        .backup-logs.show {
            display: block;
        }
        .backup-logs .log-info { color: #60a5fa; }
        .backup-logs .log-success { color: #34d399; }
        .backup-logs .log-warn { color: #fbbf24; }
        .backup-logs .log-error { color: #f87171; }

        .backup-type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .backup-type-badge.manual {
            background: rgba(99, 102, 241, 0.15);
            color: #818cf8;
        }
        .backup-type-badge.auto_pre_restore {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }
        .backup-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .backup-status-badge.success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .backup-status-badge.failed {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }
        .backup-status-badge.processing {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .remark-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: var(--text-muted);
            font-size: 0.8125rem;
            cursor: pointer;
        }

        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999;
            display: none;
            justify-content: center;
            align-items: center;
            padding: var(--spacing-lg);
        }
        .modal-overlay.show { display: flex; }
        .modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            width: 100%;
            max-width: 560px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            animation: modalFadeIn 0.25s ease;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .modal-header {
            padding: var(--spacing-lg) var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            font-size: 1.125rem;
            color: var(--text-primary);
        }
        .modal-close {
            width: 36px; height: 36px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .modal-close:hover {
            background: var(--error-color);
            color: white;
            border-color: var(--error-color);
        }
        .modal-body {
            padding: var(--spacing-xl);
        }
        .modal-footer {
            padding: var(--spacing-lg) var(--spacing-xl);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-md);
        }
        .restore-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            box-shadow: var(--shadow-lg);
            margin-top: var(--spacing-xl);
        }
        .restore-section h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-lg);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .restore-section h3 svg {
            color: var(--warning-color);
            width: 24px;
            height: 24px;
        }
        .restore-tabs {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
        }
        .restore-tab {
            padding: var(--spacing-md) var(--spacing-lg);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            transition: all 0.2s;
        }
        .restore-tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .restore-tab:hover:not(.active) {
            color: var(--text-secondary);
        }
        .restore-tab-content {
            display: none;
        }
        .restore-tab-content.active { display: block; }
        .restore-select-row {
            display: flex;
            gap: var(--spacing-md);
            align-items: flex-end;
        }
        .restore-select-row .form-group { flex: 1; }
        .warn-box {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            margin-top: var(--spacing-xl);
        }
        .warn-box h4 {
            color: var(--warning-color);
            font-size: 0.9375rem;
            margin-bottom: var(--spacing-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        .warn-box ul {
            color: var(--text-secondary);
            font-size: 0.8125rem;
            padding-left: 1.25rem;
            line-height: 1.8;
        }
        .restore-log-panel {
            background: #0a0f1a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-top: var(--spacing-lg);
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            line-height: 1.6;
            display: none;
        }
        .restore-log-panel.show { display: block; }
        .restore-log-panel .log-info { color: #60a5fa; }
        .restore-log-panel .log-success { color: #34d399; }
        .restore-log-panel .log-warn { color: #fbbf24; }
        .restore-log-panel .log-error { color: #f87171; }
        .restore-log-panel .log-ts { color: #64748b; margin-right: 8px; }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        .confirm-text-hint {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: var(--spacing-xs);
        }
        .confirm-code {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: var(--error-color);
            background: rgba(239, 68, 68, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
        }
        .toast {
            position: fixed;
            top: 100px;
            right: var(--spacing-xl);
            padding: var(--spacing-md) var(--spacing-lg);
            border-radius: var(--radius-md);
            color: white;
            font-size: 0.875rem;
            z-index: 9999;
            box-shadow: var(--shadow-xl);
            animation: toastIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(100%); }
            to { opacity: 1; transform: translateX(0); }
        }
        .toast.success { background: var(--success-color); }
        .toast.error { background: var(--error-color); }
        .toast.info { background: var(--info-color); }

        @media (max-width: 768px) {
            .backup-header { flex-direction: column; align-items: stretch; }
            .restore-select-row { flex-direction: column; }
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
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="system_backup.php" class="active">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="backup-header">
                <div class="backup-header-info">
                    <h2>数据备份与恢复</h2>
                    <p>备份公告、反馈、问答、打印模板等核心数据，支持一键恢复</p>
                </div>
                <button class="btn btn-primary" onclick="createBackup()" id="createBackupBtn">
                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M17 10L12 15L7 10M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    立即备份
                </button>
            </div>

            <div class="backup-progress" id="backupProgress">
                <div class="progress-bar"><div class="progress-fill" style="width:100%"></div></div>
                <div class="progress-text" id="progressText">正在创建备份...</div>
                <div class="backup-logs" id="backupLogs"></div>
            </div>

            <div class="results-info">
                <p id="resultsInfo">共找到 <strong>0</strong> 条备份，当前第 <strong>1</strong> / <strong>1</strong> 页</p>
            </div>

            <div class="notices-table-container">
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th width="8%">ID</th>
                            <th width="22%">备份名称</th>
                            <th width="8%">类型</th>
                            <th width="10%">文件大小</th>
                            <th width="8%">状态</th>
                            <th width="15%">备注</th>
                            <th width="14%">创建时间</th>
                            <th width="15%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="backupTableBody"></tbody>
                </table>
                <div id="noResults" class="no-results" style="display:none;">
                    <svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M17 10L12 15L7 10M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>暂无备份记录</p>
                </div>
            </div>
            <div id="paginationWrap" class="pagination-wrap"></div>

            <div class="restore-section">
                <h3>
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21M3 12H8M3 12L7 8M7 17L12 12L15 15L18 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    数据恢复
                </h3>

                <div class="restore-tabs">
                    <button class="restore-tab active" data-tab="history" onclick="switchRestoreTab('history')">选择历史备份</button>
                    <button class="restore-tab" data-tab="upload" onclick="switchRestoreTab('upload')">上传SQL文件</button>
                </div>

                <div class="restore-tab-content active" id="tab-history">
                    <div class="restore-select-row">
                        <div class="form-group">
                            <label>选择备份文件</label>
                            <select id="restoreBackupSelect">
                                <option value="">请选择要恢复的备份...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="restore-tab-content" id="tab-upload">
                    <div class="form-group">
                        <label>上传SQL文件</label>
                        <div class="screenshot-upload-area">
                            <input type="file" id="restoreSqlFile" accept=".sql">
                            <div class="screenshot-upload-hint">仅支持 .sql 格式文件，建议使用本系统导出的备份文件</div>
                        </div>
                    </div>
                </div>

                <div class="warn-box">
                    <h4>
                        <svg viewBox="0 0 24 24" fill="none" style="width:18px;height:18px" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.64407 18.302 1.54921 18.6512 1.54309 19.008C1.53697 19.3648 1.62012 19.718 1.78678 20.0426C1.95344 20.3672 2.19875 20.6545 2.50252 20.8842C2.80629 21.1139 3.16082 21.279 3.53889 21.3672C3.91696 21.4553 4.30917 21.4639 4.69126 21.3918C5.07335 21.3197 5.43619 21.1694 5.75 20.95L22.25 20.95C22.5638 21.1694 22.9267 21.3197 23.3087 21.3918C23.6908 21.4639 24.083 21.4553 24.4611 21.3672C24.8392 21.279 25.1937 21.1139 25.4975 20.8842C25.8013 20.6545 26.0466 20.3672 26.2132 20.0426C26.3799 19.718 26.463 19.3648 26.4569 19.008C26.4508 18.6512 26.3559 18.302 26.18 18L17.71 3.86C17.5267 3.54637 17.2662 3.27968 16.9477 3.08203C16.6292 2.88438 16.2636 2.76079 15.8875 2.72214C15.5114 2.68349 15.1346 2.73121 14.7838 2.86184C14.4329 2.99247 14.1184 3.2023 13.87 3.47L13.87 3.47C13.62 3.74 13.44 4.06 13.35 4.4L12.79 7L11.21 7L10.65 4.4C10.56 4.06 10.38 3.74 10.13 3.47C9.88163 3.2023 9.56714 2.99247 9.21627 2.86184C8.8654 2.73121 8.48862 2.68349 8.11252 2.72214C7.73642 2.76079 7.37081 2.88438 7.0523 3.08203C6.7338 3.27968 6.4733 3.54637 6.29 3.86L10.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        重要提醒
                    </h4>
                    <ul>
                        <li>恢复操作将覆盖当前所有数据，请谨慎操作</li>
                        <li>系统会在恢复前自动备份当前数据，可在备份历史中查看</li>
                        <li>恢复过程使用事务，若失败将自动回滚，不影响原数据</li>
                        <li>恢复期间请勿关闭页面或刷新浏览器</li>
                    </ul>
                </div>

                <div class="form-actions" style="margin-top: var(--spacing-lg)">
                    <button class="btn btn-danger" onclick="startRestore()" id="startRestoreBtn">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21M3 12H8M3 12L7 8M7 17L12 12L15 15L18 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        开始恢复
                    </button>
                </div>

                <div class="restore-log-panel" id="restoreLogPanel"></div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="renameModal">
        <div class="modal">
            <div class="modal-header">
                <h3>重命名备份</h3>
                <button class="modal-close" onclick="closeModal('renameModal')">
                    <svg viewBox="0 0 24 24" fill="none" style="width:18px;height:18px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>备份名称</label>
                    <input type="text" id="renameInput" placeholder="请输入新的备份名称">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('renameModal')">取消</button>
                <button class="btn btn-primary" onclick="confirmRename()">确定</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="remarkModal">
        <div class="modal">
            <div class="modal-header">
                <h3>编辑备注</h3>
                <button class="modal-close" onclick="closeModal('remarkModal')">
                    <svg viewBox="0 0 24 24" fill="none" style="width:18px;height:18px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>备注内容</label>
                    <textarea id="remarkInput" rows="5" placeholder="请输入备注内容..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('remarkModal')">取消</button>
                <button class="btn btn-primary" onclick="confirmRemark()">保存</button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="confirmModal">
        <div class="modal">
            <div class="modal-header">
                <h3>二次确认 - 数据恢复</h3>
                <button class="modal-close" onclick="closeModal('confirmModal')">
                    <svg viewBox="0 0 24 24" fill="none" style="width:18px;height:18px" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="warn-box" style="margin-top:0">
                    <h4>警告：此操作不可逆！</h4>
                    <ul>
                        <li id="confirmTargetText">将恢复备份：<strong>-</strong></li>
                        <li>系统会在恢复前自动备份当前数据</li>
                        <li>恢复失败将自动回滚，不影响原数据</li>
                    </ul>
                </div>
                <div class="form-group" style="margin-top: var(--spacing-lg)">
                    <div class="confirm-text-hint">请输入 <span class="confirm-code">CONFIRM</span> 以确认执行恢复操作：</div>
                    <input type="text" id="confirmInput" placeholder="请输入 CONFIRM" oninput="checkConfirmInput()">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('confirmModal')">取消</button>
                <button class="btn btn-danger" id="confirmExecBtn" onclick="executeRestore()" disabled>确认恢复</button>
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
        var currentRenameId = 0;
        var currentRemarkId = 0;
        var restoreContext = null;
        var allBackupItems = [];

        function loadBackups(page) {
            currentPage = page || 1;
            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'list', page: currentPage, per_page: 10 })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) { showToast(res.message, 'error'); return; }
                var data = res.data;
                allBackupItems = data.items;
                var tbody = document.getElementById('backupTableBody');
                var noResults = document.getElementById('noResults');
                tbody.innerHTML = '';

                if (data.items.length === 0) {
                    noResults.style.display = '';
                } else {
                    noResults.style.display = 'none';
                    var typeLabels = { manual: '手动备份', auto_pre_restore: '恢复前自动备份' };
                    var statusLabels = { success: '成功', failed: '失败', processing: '处理中' };
                    data.items.forEach(function(item) {
                        var remarkDisplay = item.remark ? escapeHtml(item.remark) : '<span style="color:var(--text-muted)">-</span>';
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td>#' + item.id + '</td>' +
                            '<td class="notice-title-cell">' + escapeHtml(item.display_name) +
                                '<div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px">' + escapeHtml(item.filename) + '</div>' +
                            '</td>' +
                            '<td><span class="backup-type-badge ' + item.backup_type + '">' + (typeLabels[item.backup_type] || item.backup_type) + '</span></td>' +
                            '<td>' + item.file_size_formatted + '</td>' +
                            '<td><span class="backup-status-badge ' + item.status + '">' + (statusLabels[item.status] || item.status) + '</span></td>' +
                            '<td><div class="remark-cell" onclick="openRemarkModal(' + item.id + ', \'' + (item.remark ? escapeAttr(item.remark) : '') + '\')" title="点击编辑">' + remarkDisplay + '</div></td>' +
                            '<td>' + formatDate(item.created_at) + '</td>' +
                            '<td class="action-buttons">' +
                                '<button class="btn-icon-action edit" title="下载" onclick="downloadBackup(' + item.id + ')">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15M17 10L12 15L7 10M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                                '</button>' +
                                '<button class="btn-icon-action edit" title="重命名" onclick="openRenameModal(' + item.id + ', \'' + escapeAttr(item.display_name) + '\')">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 14V20H10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                                '</button>' +
                                '<button class="btn-icon-action edit" title="编辑备注" onclick="openRemarkModal(' + item.id + ', \'' + (item.remark ? escapeAttr(item.remark) : '') + '\')">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                                '</button>' +
                                '<button class="btn-icon-action delete" title="删除" onclick="deleteBackup(' + item.id + ')">' +
                                    '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6H5H21M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19ZM10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                                '</button>' +
                            '</td>';
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('resultsInfo').innerHTML =
                    '共找到 <strong>' + data.total + '</strong> 条备份，当前第 <strong>' + data.page + '</strong> / <strong>' + data.total_pages + '</strong> 页';
                renderPagination(data.page, data.total_pages);
                refreshRestoreBackupSelect();
            });
        }

        function refreshRestoreBackupSelect() {
            var sel = document.getElementById('restoreBackupSelect');
            var currentVal = sel.value;
            sel.innerHTML = '<option value="">请选择要恢复的备份...</option>';
            allBackupItems.forEach(function(item) {
                if (item.status === 'success') {
                    var opt = document.createElement('option');
                    opt.value = item.id;
                    opt.textContent = '#' + item.id + ' - ' + item.display_name + ' (' + item.file_size_formatted + ')';
                    sel.appendChild(opt);
                }
            });
            if (currentVal) sel.value = currentVal;
        }

        function renderPagination(currentPage, totalPages) {
            var wrap = document.getElementById('paginationWrap');
            wrap.innerHTML = '';
            if (totalPages <= 1) return;
            if (currentPage > 1) {
                var prev = document.createElement('a');
                prev.className = 'page-link';
                prev.href = 'javascript:void(0)';
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>上一页';
                prev.onclick = function() { loadBackups(currentPage - 1); };
                wrap.appendChild(prev);
            }
            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, currentPage + 2);
            if (start > 1) {
                wrap.appendChild(createPageNum(1, currentPage));
                if (start > 2) {
                    var e1 = document.createElement('span');
                    e1.className = 'page-ellipsis';
                    e1.textContent = '...';
                    wrap.appendChild(e1);
                }
            }
            for (var i = start; i <= end; i++) wrap.appendChild(createPageNum(i, currentPage));
            if (end < totalPages) {
                if (end < totalPages - 1) {
                    var e2 = document.createElement('span');
                    e2.className = 'page-ellipsis';
                    e2.textContent = '...';
                    wrap.appendChild(e2);
                }
                wrap.appendChild(createPageNum(totalPages, currentPage));
            }
            if (currentPage < totalPages) {
                var next = document.createElement('a');
                next.className = 'page-link';
                next.href = 'javascript:void(0)';
                next.innerHTML = '下一页<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                next.onclick = function() { loadBackups(currentPage + 1); };
                wrap.appendChild(next);
            }
        }
        function createPageNum(page, current) {
            var a = document.createElement('a');
            a.className = 'page-number' + (page === current ? ' active' : '');
            a.textContent = page;
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadBackups(page); };
            return a;
        }

        function createBackup() {
            var btn = document.getElementById('createBackupBtn');
            var prog = document.getElementById('backupProgress');
            var logs = document.getElementById('backupLogs');
            btn.disabled = true;
            prog.classList.add('show');
            logs.classList.add('show');
            logs.innerHTML = '';
            document.getElementById('progressText').textContent = '正在创建备份...';

            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'create' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                if (res.logs) {
                    res.logs.forEach(function(l) { appendBackupLog(l); });
                }
                if (res.success) {
                    document.getElementById('progressText').textContent = '备份完成！';
                    showToast('备份创建成功', 'success');
                    loadBackups(currentPage);
                    setTimeout(function() { prog.classList.remove('show'); logs.classList.remove('show'); }, 3000);
                } else {
                    document.getElementById('progressText').textContent = '备份失败';
                    showToast(res.message || '备份失败', 'error');
                }
            })
            .catch(function(e) {
                btn.disabled = false;
                document.getElementById('progressText').textContent = '备份失败';
                appendBackupLog('[ERROR] ' + e.message);
                showToast('网络错误', 'error');
            });
        }
        function appendBackupLog(line) {
            var logs = document.getElementById('backupLogs');
            var cls = 'log-info';
            if (line.indexOf('[SUCCESS]') >= 0) cls = 'log-success';
            else if (line.indexOf('[WARN]') >= 0) cls = 'log-warn';
            else if (line.indexOf('[ERROR]') >= 0) cls = 'log-error';
            var div = document.createElement('div');
            div.className = cls;
            div.textContent = line;
            logs.appendChild(div);
            logs.scrollTop = logs.scrollHeight;
        }

        function downloadBackup(id) {
            window.location.href = 'api_backup.php?action=download&id=' + id;
        }

        function deleteBackup(id) {
            if (!confirm('确定要删除该备份吗？此操作不可恢复。')) return;
            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) { showToast('删除成功', 'success'); loadBackups(currentPage); }
                else showToast(res.message || '删除失败', 'error');
            });
        }

        function openRenameModal(id, name) {
            currentRenameId = id;
            document.getElementById('renameInput').value = name;
            openModal('renameModal');
            setTimeout(function() { document.getElementById('renameInput').focus(); }, 100);
        }
        function confirmRename() {
            var newName = document.getElementById('renameInput').value.trim();
            if (!newName) { showToast('名称不能为空', 'error'); return; }
            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'rename', id: currentRenameId, display_name: newName })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) { showToast('重命名成功', 'success'); closeModal('renameModal'); loadBackups(currentPage); }
                else showToast(res.message || '操作失败', 'error');
            });
        }

        function openRemarkModal(id, remark) {
            currentRemarkId = id;
            document.getElementById('remarkInput').value = remark || '';
            openModal('remarkModal');
            setTimeout(function() { document.getElementById('remarkInput').focus(); }, 100);
        }
        function confirmRemark() {
            var remark = document.getElementById('remarkInput').value;
            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'remark', id: currentRemarkId, remark: remark })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) { showToast('备注更新成功', 'success'); closeModal('remarkModal'); loadBackups(currentPage); }
                else showToast(res.message || '操作失败', 'error');
            });
        }

        function switchRestoreTab(tab) {
            document.querySelectorAll('.restore-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.restore-tab-content').forEach(function(c) { c.classList.remove('active'); });
            document.querySelector('.restore-tab[data-tab="' + tab + '"]').classList.add('active');
            document.getElementById('tab-' + tab).classList.add('active');
        }

        function startRestore() {
            var activeTab = document.querySelector('.restore-tab.active').dataset.tab;
            if (activeTab === 'history') {
                var bid = document.getElementById('restoreBackupSelect').value;
                if (!bid) { showToast('请选择要恢复的备份', 'error'); return; }
                var item = allBackupItems.find(function(x) { return x.id == bid; });
                var targetText = '#' + bid + ' - ' + (item ? item.display_name : '');
                restoreContext = { mode: 'history', id: parseInt(bid), uploadFile: null };
                document.getElementById('confirmTargetText').innerHTML = '将恢复备份：<strong>' + escapeHtml(targetText) + '</strong>';
            } else {
                var file = document.getElementById('restoreSqlFile').files[0];
                if (!file) { showToast('请选择SQL文件', 'error'); return; }
                if (!file.name.toLowerCase().endsWith('.sql')) { showToast('仅支持 .sql 文件', 'error'); return; }
                restoreContext = { mode: 'upload', id: null, uploadFile: file };
                document.getElementById('confirmTargetText').innerHTML = '将恢复上传文件：<strong>' + escapeHtml(file.name) + '</strong>';
            }
            document.getElementById('confirmInput').value = '';
            checkConfirmInput();
            openModal('confirmModal');
        }
        function checkConfirmInput() {
            var val = document.getElementById('confirmInput').value.trim();
            document.getElementById('confirmExecBtn').disabled = val !== 'CONFIRM';
        }

        function executeRestore() {
            closeModal('confirmModal');
            if (restoreContext.mode === 'upload') {
                executeUploadAndRestore();
            } else {
                executeRestoreById();
            }
        }

        function executeUploadAndRestore() {
            var btn = document.getElementById('startRestoreBtn');
            var panel = document.getElementById('restoreLogPanel');
            btn.disabled = true;
            panel.classList.add('show');
            panel.innerHTML = '';
            appendRestoreLog('info', '正在上传备份文件...');

            var fd = new FormData();
            fd.append('sql_file', restoreContext.uploadFile);
            fd.append('action', 'upload');

            fetch('api_backup.php', { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) {
                    appendRestoreLog('error', res.message || '上传失败');
                    btn.disabled = false;
                    showToast('上传失败', 'error');
                    return;
                }
                appendRestoreLog('success', '上传成功，备份ID: ' + res.data.id);
                restoreContext.id = res.data.id;
                loadBackups(1);
                executeRestoreById();
            })
            .catch(function(e) {
                appendRestoreLog('error', '网络错误: ' + e.message);
                btn.disabled = false;
            });
        }

        function executeRestoreById() {
            var btn = document.getElementById('startRestoreBtn');
            var panel = document.getElementById('restoreLogPanel');
            panel.classList.add('show');

            if (!!window.EventSource) {
                appendRestoreLog('info', '使用流式恢复模式...');
                executeRestoreStream();
                return;
            }

            appendRestoreLog('info', '使用标准恢复模式...');
            fetch('api_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'restore', id: restoreContext.id, confirm_text: 'CONFIRM' })
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                btn.disabled = false;
                if (res.logs) {
                    res.logs.forEach(function(l) {
                        var cls = 'info';
                        if (l.indexOf('[SUCCESS]') >= 0) cls = 'success';
                        else if (l.indexOf('[WARN]') >= 0) cls = 'warn';
                        else if (l.indexOf('[ERROR]') >= 0) cls = 'error';
                        var txt = l.replace(/^\[(INFO|SUCCESS|WARN|ERROR)\]\s*/, '');
                        appendRestoreLog(cls, txt);
                    });
                }
                if (res.success) {
                    showToast('恢复成功', 'success');
                    loadBackups(currentPage);
                } else {
                    showToast(res.message || '恢复失败', 'error');
                }
            })
            .catch(function(e) {
                btn.disabled = false;
                appendRestoreLog('error', '网络错误: ' + e.message);
            });
        }

        function executeRestoreStream() {
            var btn = document.getElementById('startRestoreBtn');
            btn.disabled = true;

            var payload = JSON.stringify({ action: 'restore_stream', id: restoreContext.id, confirm_text: 'CONFIRM' });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api_backup.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            var lastPos = 0;
            xhr.onprogress = function() {
                var chunk = xhr.responseText.substring(lastPos);
                lastPos = xhr.responseText.length;
                processSSEChunk(chunk);
            };
            xhr.onload = function() {
                btn.disabled = false;
                var chunk = xhr.responseText.substring(lastPos);
                processSSEChunk(chunk);
                if (restoreDone) {
                    if (restoreSuccess) { showToast('恢复成功', 'success'); loadBackups(currentPage); }
                    else showToast(restoreErrorMsg || '恢复失败', 'error');
                }
            };
            xhr.onerror = function() {
                btn.disabled = false;
                appendRestoreLog('error', '网络连接错误');
                showToast('网络错误', 'error');
            };
            xhr.send(payload);
        }

        var restoreDone = false;
        var restoreSuccess = false;
        var restoreErrorMsg = '';

        function processSSEChunk(chunk) {
            var lines = chunk.split('\n');
            var buffer = '';
            var lastEvent = 'message';
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i];
                if (line === '') {
                    if (buffer.length > 0) {
                        if (lastEvent === 'done') {
                            try {
                                var data = JSON.parse(buffer.replace(/^data:\s*/, ''));
                                restoreDone = true;
                                restoreSuccess = !!data.success;
                                restoreErrorMsg = data.message || '';
                            } catch(e) {}
                        } else {
                            try {
                                var m = buffer.match(/^data:\s*(\{[\s\S]*\})/);
                                if (m) {
                                    var parsed = JSON.parse(m[1]);
                                    appendRestoreLog(parsed.level || 'info', parsed.message || '');
                                }
                            } catch(e) {}
                        }
                        buffer = '';
                        lastEvent = 'message';
                    }
                } else if (line.indexOf('event:') === 0) {
                    lastEvent = line.substring(6).trim();
                } else {
                    buffer += (buffer.length > 0 ? '\n' : '') + line;
                }
            }
        }

        function appendRestoreLog(level, msg) {
            var panel = document.getElementById('restoreLogPanel');
            var div = document.createElement('div');
            var ts = new Date();
            var tss = ts.getFullYear() + '-' + pad2(ts.getMonth()+1) + '-' + pad2(ts.getDate()) + ' ' + pad2(ts.getHours()) + ':' + pad2(ts.getMinutes()) + ':' + pad2(ts.getSeconds());
            div.innerHTML = '<span class="log-ts">[' + tss + ']</span><span class="log-' + level + '">' + escapeHtml(msg) + '</span>';
            panel.appendChild(div);
            panel.scrollTop = panel.scrollHeight;
        }
        function pad2(n) { return String(n).padStart(2, '0'); }

        function openModal(id) { document.getElementById(id).classList.add('show'); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        document.querySelectorAll('.modal-overlay').forEach(function(m) {
            m.addEventListener('click', function(e) { if (e.target === m) m.classList.remove('show'); });
        });

        function showToast(msg, type) {
            type = type || 'info';
            var icons = {
                success: '<svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px" xmlns="http://www.w3.org/2000/svg"><path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                error: '<svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                info: '<svg viewBox="0 0 24 24" fill="none" style="width:20px;height:20px" xmlns="http://www.w3.org/2000/svg"><path d="M12 16V12M12 8H12.01M10.29 3.86L1.82 18C1.64407 18.302 1.54921 18.6512 1.54309 19.008C1.53697 19.3648 1.62012 19.718 1.78678 20.0426C1.95344 20.3672 2.19875 20.6545 2.50252 20.8842C2.80629 21.1139 3.16082 21.279 3.53889 21.3672C3.91696 21.4553 4.30917 21.4639 4.69126 21.3918C5.07335 21.3197 5.43619 21.1694 5.75 20.95L22.25 20.95C22.5638 21.1694 22.9267 21.3197 23.3087 21.3918C23.6908 21.4639 24.083 21.4553 24.4611 21.3672C24.8392 21.279 25.1937 21.1139 25.4975 20.8842C25.8013 20.6545 26.0466 20.3672 26.2132 20.0426C26.3799 19.718 26.463 19.3648 26.4569 19.008C26.4508 18.6512 26.3559 18.302 26.18 18L17.71 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            };
            var t = document.createElement('div');
            t.className = 'toast ' + type;
            t.innerHTML = (icons[type] || '') + '<span>' + escapeHtml(msg) + '</span>';
            document.body.appendChild(t);
            setTimeout(function() {
                t.style.opacity = '0';
                t.style.transform = 'translateX(100%)';
                t.style.transition = 'all 0.3s ease';
                setTimeout(function() { t.remove(); }, 300);
            }, 3000);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function escapeAttr(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
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

        loadBackups(1);
    </script>
</body>
</html>
