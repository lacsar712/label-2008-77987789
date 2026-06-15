<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensurePrintTemplates();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>打印模板管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Noto Sans SC', sans-serif; }

        .layout {
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 24px;
            padding: 24px 0;
        }
        @media (max-width: 968px) {
            .layout { grid-template-columns: 1fr; }
        }

        .panel {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }
        .panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-tertiary);
        }
        .panel-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-title svg { width: 18px; height: 18px; color: var(--primary-color); }
        .panel-body { padding: 16px; }

        .btn-icon {
            width: 32px; height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-icon:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(59,130,246,0.05);
        }
        .btn-icon svg { width: 16px; height: 16px; }

        .tpl-list { list-style: none; padding: 0; margin: 0; }
        .tpl-item {
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 8px;
        }
        .tpl-item:hover {
            background: var(--bg-tertiary);
        }
        .tpl-item.active {
            background: linear-gradient(135deg, rgba(59,130,246,0.1), rgba(139,92,246,0.1));
            border-color: var(--primary-color);
        }
        .tpl-item-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .tpl-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tpl-type-tag {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background: var(--bg-tertiary);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            font-weight: 500;
        }
        .tpl-type-tag.type-minimal { background: rgba(59,130,246,0.1); color: #3b82f6; border-color: transparent; }
        .tpl-type-tag.type-official { background: rgba(30,64,175,0.1); color: #1e40af; border-color: transparent; }
        .tpl-type-tag.type-card { background: rgba(139,92,246,0.1); color: #8b5cf6; border-color: transparent; }
        .default-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--success-color), #10b981);
            color: white;
            font-weight: 500;
        }
        .tpl-desc {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
        }
        .tpl-desc span { display: inline-block; margin-right: 12px; }
        .tpl-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .form-section {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }
        .form-section:last-child { border-bottom: none; }
        .form-section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-section-title svg { width: 16px; height: 16px; color: var(--primary-color); }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-grid.full { grid-template-columns: 1fr; }
        @media (max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .form-label .req { color: var(--error-color); margin-left: 2px; }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-tertiary);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-textarea { resize: vertical; min-height: 72px; }
        .form-input[type="color"] {
            padding: 2px 4px;
            height: 38px;
            cursor: pointer;
        }
        .form-input[type="file"] {
            padding: 6px;
            cursor: pointer;
        }

        .logo-uploader {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-preview {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            border: 2px dashed var(--border-color);
            background: var(--bg-tertiary);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        .logo-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .logo-preview-placeholder {
            font-size: 11px;
            color: var(--text-muted);
            text-align: center;
            padding: 6px;
        }

        .checkbox-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .checkbox-row:hover {
            border-color: var(--primary-color);
        }
        .checkbox-row input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }
        .checkbox-row label {
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            flex: 1;
        }

        .style-field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        @media (max-width: 640px) {
            .style-field-row { grid-template-columns: 1fr; }
        }

        .type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 4px;
        }
        @media (max-width: 640px) {
            .type-selector { grid-template-columns: 1fr; }
        }
        .type-option {
            padding: 12px 10px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            background: var(--bg-tertiary);
        }
        .type-option:hover {
            border-color: var(--primary-color);
        }
        .type-option.active {
            border-color: var(--primary-color);
            background: rgba(59,130,246,0.1);
        }
        .type-option-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .type-option-name {
            font-size: 12px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .action-bar {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            padding-top: 8px;
        }
        .action-bar .btn {
            padding: 8px 18px;
        }
        .action-bar .spacer { flex: 1; }
        .btn-danger {
            background: rgba(239,68,68,0.1);
            color: var(--error-color);
            border: 1px solid rgba(239,68,68,0.3);
        }
        .btn-danger:hover {
            background: rgba(239,68,68,0.2);
            border-color: var(--error-color);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        .btn-ghost:hover {
            background: var(--bg-tertiary);
            border-color: var(--text-muted);
        }

        .toast {
            position: fixed;
            top: 80px;
            right: 24px;
            z-index: 9999;
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            color: white;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
            max-width: 360px;
        }
        .toast.success { background: linear-gradient(135deg, #10b981, #059669); }
        .toast.error { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .toast.info { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-muted);
        }
        .empty-state svg {
            width: 64px; height: 64px;
            opacity: 0.3;
            margin-bottom: 12px;
        }
        .empty-state p { margin: 0; font-size: 14px; }
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
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="print_template_admin.php" class="active">打印模板</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <a href="search_notice.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                返回公告管理
            </a>

            <div class="layout">
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            模板列表
                        </div>
                        <button class="btn-icon" onclick="createTemplate()" title="新建模板">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>
                    </div>
                    <div class="panel-body">
                        <ul class="tpl-list" id="tplList">
                            <li class="empty-state">加载中...</li>
                        </ul>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            <span id="editorTitle">模板编辑</span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button class="btn-icon" onclick="previewTemplate()" title="预览效果" style="width:36px;height:36px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <form id="tplForm" onsubmit="return saveTemplate(event)">
                            <div class="form-section">
                                <div class="form-section-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7h-9M14 17H5M17 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM7 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/></svg>
                                    基础信息
                                </div>
                                <div class="form-grid">
                                    <div class="form-group full">
                                        <label class="form-label">模板名称<span class="req">*</span></label>
                                        <input type="text" class="form-input" id="f_name" placeholder="请输入模板名称" required>
                                    </div>
                                    <div class="form-group full">
                                        <label class="form-label">模板风格</label>
                                        <div class="type-selector" id="typeSelector">
                                            <div class="type-option" data-type="minimal" onclick="selectType('minimal')">
                                                <div class="type-option-icon">📄</div>
                                                <div class="type-option-name">极简风</div>
                                            </div>
                                            <div class="type-option" data-type="official" onclick="selectType('official')">
                                                <div class="type-option-icon">📋</div>
                                                <div class="type-option-name">正式公文</div>
                                            </div>
                                            <div class="type-option" data-type="card" onclick="selectType('card')">
                                                <div class="type-option-icon">🎴</div>
                                                <div class="type-option-name">卡片风</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1m-6 0h6"/></svg>
                                    页眉页脚
                                </div>
                                <div class="form-grid">
                                    <div class="form-group full">
                                        <label class="form-label">Logo 图片</label>
                                        <div class="logo-uploader">
                                            <div class="logo-preview" id="logoPreview">
                                                <div class="logo-preview-placeholder">无 Logo</div>
                                            </div>
                                            <div style="flex:1;">
                                                <input type="file" class="form-input" id="logoFile" accept="image/*" onchange="uploadLogo(this)">
                                                <input type="hidden" id="f_logo_url">
                                                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">支持 JPG/PNG/SVG，最大 2MB</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group full">
                                        <label class="form-label">页眉文字</label>
                                        <input type="text" class="form-input" id="f_header_text" placeholder="显示在页眉的文字，如公司名称">
                                    </div>
                                    <div class="form-group full">
                                        <label class="form-label">页脚文字</label>
                                        <input type="text" class="form-input" id="f_footer_text" placeholder="显示在页脚的文字，如版权信息">
                                    </div>
                                    <div class="form-group full">
                                        <div class="checkbox-row">
                                            <input type="checkbox" id="f_is_default">
                                            <label for="f_is_default">设为默认模板（打印时优先使用）</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                    样式配置
                                </div>
                                <div class="style-field-row">
                                    <div class="form-group">
                                        <label class="form-label">字体</label>
                                        <select class="form-select" id="s_fontFamily">
                                            <option value="Noto Sans SC, sans-serif">思源黑体</option>
                                            <option value="SimSun, Noto Sans SC, serif">宋体</option>
                                            <option value="SimHei, Noto Sans SC, sans-serif">黑体</option>
                                            <option value="KaiTi, SimSun, serif">楷体</option>
                                            <option value="Microsoft YaHei, sans-serif">微软雅黑</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">字号</label>
                                        <select class="form-select" id="s_fontSize">
                                            <option value="12px">12px (小)</option>
                                            <option value="14px">14px (标准)</option>
                                            <option value="16px">16px (大)</option>
                                            <option value="18px">18px (特大)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">文字颜色</label>
                                        <input type="color" class="form-input" id="s_textColor" value="#333333">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">背景颜色</label>
                                        <input type="color" class="form-input" id="s_backgroundColor" value="#ffffff">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">主色</label>
                                        <input type="color" class="form-input" id="s_primaryColor" value="#3b82f6">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">边框颜色</label>
                                        <input type="color" class="form-input" id="s_borderColor" value="#e5e7eb">
                                    </div>
                                </div>
                                <div class="style-field-row" style="margin-top:12px;" id="cardStyleFields">
                                    <div class="form-group">
                                        <label class="form-label">卡片背景色</label>
                                        <input type="color" class="form-input" id="s_cardBgColor" value="#ffffff">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">圆角大小</label>
                                        <select class="form-select" id="s_borderRadius">
                                            <option value="0px">0px (直角)</option>
                                            <option value="8px">8px (小圆角)</option>
                                            <option value="12px">12px (中圆角)</option>
                                            <option value="16px">16px (大圆角)</option>
                                            <option value="24px">24px (极大圆角)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">内边距</label>
                                        <select class="form-select" id="s_padding">
                                            <option value="20px">紧凑</option>
                                            <option value="32px">标准</option>
                                            <option value="40px 32px">宽松</option>
                                            <option value="48px">超大</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">阴影效果</label>
                                        <select class="form-select" id="s_shadow">
                                            <option value="none">无阴影</option>
                                            <option value="0 1px 3px rgba(0,0,0,0.1)">轻微</option>
                                            <option value="0 4px 6px -1px rgba(0,0,0,0.1)">标准</option>
                                            <option value="0 10px 15px -3px rgba(0,0,0,0.1)">明显</option>
                                            <option value="0 20px 25px -5px rgba(0,0,0,0.15)">强烈</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="action-bar">
                                <button type="button" class="btn btn-danger btn-ghost" id="deleteBtn" onclick="deleteTemplate()" style="display:none;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                    删除
                                </button>
                                <button type="button" class="btn btn-ghost" onclick="resetForm()">重置</button>
                                <button type="submit" class="btn btn-primary">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    保存模板
                                </button>
                            </div>
                        </form>
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
        var templates = [];
        var currentId = 0;
        var currentType = 'minimal';

        loadTemplates();

        function loadTemplates() {
            fetch('api_print_templates.php')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        templates = res.data;
                        renderList();
                        if (templates.length > 0) {
                            var d = templates.find(function(t) { return t.is_default; }) || templates[0];
                            selectTemplate(d.id);
                        }
                    } else {
                        showToast(res.message || '加载失败', 'error');
                    }
                })
                .catch(function(err) { showToast('网络错误', 'error'); });
        }

        function renderList() {
            var ul = document.getElementById('tplList');
            if (!templates.length) {
                ul.innerHTML = '<li class="empty-state"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>暂无模板，点击右上角新建</p></li>';
                return;
            }
            var typeNames = { minimal: '极简风', official: '正式公文', card: '卡片风' };
            var html = '';
            templates.forEach(function(t) {
                html += '<li class="tpl-item' + (t.id === currentId ? ' active' : '') + '" onclick="selectTemplate(' + t.id + ')">';
                html += '  <div class="tpl-item-head">';
                html += '    <span class="tpl-name">';
                html += '      ' + escapeHtml(t.name);
                html += '      <span class="tpl-type-tag type-' + t.template_type + '">' + typeNames[t.template_type] + '</span>';
                html += '    </span>';
                html += '    ' + (t.is_default ? '<span class="default-badge">默认</span>' : '');
                html += '  </div>';
                html += '  <div class="tpl-desc">';
                html += '    ' + (t.header_text ? '<span>📌 ' + escapeHtml(t.header_text.substring(0, 20)) + '</span>' : '');
                html += '    ' + (t.footer_text ? '<span>📎 ' + escapeHtml(t.footer_text.substring(0, 20)) + '</span>' : '');
                html += '  </div>';
                html += '  <div class="tpl-meta">ID: ' + t.id + ' · 更新于 ' + t.updated_at.substring(0, 16) + '</div>';
                html += '</li>';
            });
            ul.innerHTML = html;
        }

        window.selectTemplate = function(id) {
            currentId = id;
            var t = templates.find(function(x) { return x.id === id; });
            if (!t) return;
            document.getElementById('editorTitle').textContent = '编辑：' + t.name;
            document.getElementById('deleteBtn').style.display = 'inline-flex';
            document.getElementById('f_name').value = t.name;
            document.getElementById('f_header_text').value = t.header_text || '';
            document.getElementById('f_footer_text').value = t.footer_text || '';
            document.getElementById('f_logo_url').value = t.logo_url || '';
            document.getElementById('f_is_default').checked = !!t.is_default;
            selectType(t.template_type, false);

            var s = t.style || {};
            if (s.fontFamily) document.getElementById('s_fontFamily').value = s.fontFamily;
            if (s.fontSize) document.getElementById('s_fontSize').value = s.fontSize;
            if (s.textColor) document.getElementById('s_textColor').value = s.textColor;
            if (s.backgroundColor) document.getElementById('s_backgroundColor').value = s.backgroundColor;
            if (s.primaryColor) document.getElementById('s_primaryColor').value = s.primaryColor;
            if (s.borderColor) document.getElementById('s_borderColor').value = s.borderColor;
            if (s.cardBgColor) document.getElementById('s_cardBgColor').value = s.cardBgColor;
            if (s.borderRadius) document.getElementById('s_borderRadius').value = s.borderRadius;
            if (s.padding) document.getElementById('s_padding').value = s.padding;
            if (s.shadow) document.getElementById('s_shadow').value = s.shadow;

            updateLogoPreview(t.logo_url);
            renderList();
        };

        window.createTemplate = function() {
            currentId = 0;
            currentType = 'minimal';
            document.getElementById('editorTitle').textContent = '新建模板';
            document.getElementById('deleteBtn').style.display = 'none';
            document.getElementById('tplForm').reset();
            selectType('minimal', false);
            document.getElementById('s_fontFamily').value = 'Noto Sans SC, sans-serif';
            document.getElementById('s_fontSize').value = '14px';
            document.getElementById('s_textColor').value = '#333333';
            document.getElementById('s_backgroundColor').value = '#ffffff';
            document.getElementById('s_primaryColor').value = '#3b82f6';
            document.getElementById('s_borderColor').value = '#e5e7eb';
            document.getElementById('s_cardBgColor').value = '#ffffff';
            document.getElementById('s_borderRadius').value = '12px';
            document.getElementById('s_padding').value = '32px';
            document.getElementById('s_shadow').value = '0 4px 6px -1px rgba(0,0,0,0.1)';
            document.getElementById('f_logo_url').value = '';
            updateLogoPreview('');
            renderList();
        };

        window.selectType = function(type, updateId) {
            currentType = type;
            document.querySelectorAll('#typeSelector .type-option').forEach(function(el) {
                el.classList.toggle('active', el.dataset.type === type);
            });
            var cardFields = document.getElementById('cardStyleFields');
            cardFields.style.display = (type === 'card') ? 'grid' : 'none';
        };

        window.resetForm = function() {
            if (currentId > 0) {
                selectTemplate(currentId);
            } else {
                createTemplate();
            }
        };

        window.saveTemplate = function(e) {
            e.preventDefault();
            var name = document.getElementById('f_name').value.trim();
            if (!name) { showToast('请输入模板名称', 'error'); return false; }

            var style = {
                fontFamily: document.getElementById('s_fontFamily').value,
                fontSize: document.getElementById('s_fontSize').value,
                textColor: document.getElementById('s_textColor').value,
                backgroundColor: document.getElementById('s_backgroundColor').value,
                primaryColor: document.getElementById('s_primaryColor').value,
                borderColor: document.getElementById('s_borderColor').value,
            };
            if (currentType === 'card') {
                style.cardBgColor = document.getElementById('s_cardBgColor').value;
                style.borderRadius = document.getElementById('s_borderRadius').value;
                style.padding = document.getElementById('s_padding').value;
                style.shadow = document.getElementById('s_shadow').value;
            }

            var payload = {
                action: currentId > 0 ? 'update' : 'create',
                name: name,
                header_text: document.getElementById('f_header_text').value.trim(),
                footer_text: document.getElementById('f_footer_text').value.trim(),
                logo_url: document.getElementById('f_logo_url').value.trim(),
                is_default: document.getElementById('f_is_default').checked ? 1 : 0,
                template_type: currentType,
                style: style
            };
            if (currentId > 0) payload.id = currentId;

            fetch('api_print_templates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    showToast(res.message || '保存成功', 'success');
                    loadTemplates();
                } else {
                    showToast(res.message || '保存失败', 'error');
                }
            }).catch(function(err) { showToast('网络错误', 'error'); });

            return false;
        };

        window.deleteTemplate = function() {
            if (!currentId) return;
            if (!confirm('确定要删除此模板吗？')) return;
            fetch('api_print_templates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: currentId })
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    showToast(res.message || '删除成功', 'success');
                    createTemplate();
                    loadTemplates();
                } else {
                    showToast(res.message || '删除失败', 'error');
                }
            }).catch(function(err) { showToast('网络错误', 'error'); });
        };

        window.uploadLogo = function(input) {
            if (!input.files || !input.files[0]) return;
            var file = input.files[0];
            if (file.size > 2 * 1024 * 1024) {
                showToast('文件不能超过 2MB', 'error');
                input.value = '';
                return;
            }
            var fd = new FormData();
            fd.append('logo', file);
            fetch('api_print_logo_upload.php', { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        document.getElementById('f_logo_url').value = res.url;
                        updateLogoPreview(res.url);
                        showToast('上传成功', 'success');
                    } else {
                        showToast(res.message || '上传失败', 'error');
                    }
                    input.value = '';
                })
                .catch(function(err) { showToast('上传失败', 'error'); input.value = ''; });
        };

        function updateLogoPreview(url) {
            var box = document.getElementById('logoPreview');
            if (url) {
                box.innerHTML = '<img src="' + escapeAttr(url) + '" alt="logo">';
            } else {
                box.innerHTML = '<div class="logo-preview-placeholder">无 Logo</div>';
            }
        }

        window.previewTemplate = function() {
            var noticeId = 1;
            var url = 'print_preview.php?notice_id=' + noticeId;
            if (currentId > 0) url += '&template_id=' + currentId;
            window.open(url, '_blank');
        };

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, function(c) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            });
        }
        function escapeAttr(str) { return escapeHtml(str); }

        function showToast(msg, type) {
            type = type || 'info';
            var t = document.createElement('div');
            t.className = 'toast ' + type;
            t.textContent = msg;
            document.body.appendChild(t);
            setTimeout(function() {
                t.style.opacity = '0';
                t.style.transform = 'translateX(120%)';
                t.style.transition = 'all 0.3s';
                setTimeout(function() { t.remove(); }, 300);
            }, 2500);
        }
    })();
    </script>
</body>
</html>
