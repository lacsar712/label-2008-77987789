<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureChatTables();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线答疑 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .chat-wrapper {
            display: flex;
            gap: var(--spacing-lg);
            height: calc(100vh - 200px);
            min-height: 500px;
        }
        .chat-sidebar {
            width: 260px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        .chat-sidebar-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .chat-sidebar-header {
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .chat-sidebar-header h4 {
            font-size: 0.875rem;
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .chat-sidebar-header .online-dot {
            width: 8px;
            height: 8px;
            background: var(--success-color);
            border-radius: 50%;
            animation: pulse-dot 2s ease-in-out infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .chat-room-list {
            padding: var(--spacing-sm);
        }
        .chat-room-item {
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        .chat-room-item:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        .chat-room-item.active {
            background: rgba(99, 102, 241, 0.15);
            color: var(--primary-color);
        }
        .chat-room-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }
        .online-list {
            padding: var(--spacing-sm);
            max-height: 300px;
            overflow-y: auto;
        }
        .online-user {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: 6px var(--spacing-md);
            border-radius: var(--radius-sm);
            font-size: 0.8125rem;
            color: var(--text-secondary);
        }
        .online-user.admin-user {
            color: var(--warning-color);
            font-weight: 600;
        }
        .online-user .user-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--success-color);
            flex-shrink: 0;
        }
        .online-user.admin-user .user-dot {
            background: var(--warning-color);
        }
        .online-count-badge {
            margin-left: auto;
            background: var(--bg-tertiary);
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .chat-header {
            padding: var(--spacing-md) var(--spacing-lg);
            background: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-header-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .chat-header-info h3 {
            font-size: 1rem;
            color: var(--text-primary);
        }
        .chat-header-info .room-desc {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-left: var(--spacing-sm);
        }
        .chat-header-admin {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        .admin-toggle {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: 0.8125rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }
        .admin-toggle:hover {
            background: var(--bg-primary);
        }
        .admin-toggle input[type="checkbox"] {
            accent-color: var(--primary-color);
        }
        .admin-token-input {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.8125rem;
            width: 120px;
            display: none;
        }
        .admin-token-input.visible {
            display: inline-block;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: var(--spacing-lg);
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        .chat-msg {
            display: flex;
            gap: var(--spacing-md);
            max-width: 80%;
            animation: msgFadeIn 0.3s ease;
        }
        @keyframes msgFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .chat-msg.self {
            margin-left: auto;
            flex-direction: row-reverse;
        }
        .chat-msg.system {
            max-width: 100%;
            justify-content: center;
        }
        .msg-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
            color: white;
        }
        .msg-avatar.admin-avatar {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        .msg-avatar.user-avatar {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
        }
        .chat-msg.self .msg-avatar {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        .msg-body {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .chat-msg.self .msg-body {
            align-items: flex-end;
        }
        .msg-nickname {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .msg-nickname .admin-badge {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
            padding: 1px 6px;
            border-radius: 999px;
            font-size: 0.625rem;
            font-weight: 700;
        }
        .msg-content {
            background: var(--bg-tertiary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-lg);
            font-size: 0.875rem;
            line-height: 1.6;
            color: var(--text-primary);
            word-break: break-word;
        }
        .chat-msg.self .msg-content {
            background: rgba(99, 102, 241, 0.2);
        }
        .chat-msg.admin-msg .msg-content {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .msg-content .mention-highlight {
            background: rgba(99, 102, 241, 0.25);
            color: var(--primary-light);
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: 500;
        }
        .msg-content img {
            max-width: 240px;
            max-height: 240px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .msg-content img:hover {
            transform: scale(1.02);
        }
        .msg-system {
            font-size: 0.8125rem;
            color: var(--text-muted);
            text-align: center;
            padding: 4px 0;
        }
        .msg-time {
            font-size: 0.6875rem;
            color: var(--text-muted);
        }
        .chat-input-area {
            padding: var(--spacing-md) var(--spacing-lg);
            border-top: 1px solid var(--border-color);
            background: var(--bg-tertiary);
        }
        .chat-input-row {
            display: flex;
            gap: var(--spacing-sm);
            align-items: flex-end;
        }
        .chat-input-tools {
            display: flex;
            gap: var(--spacing-xs);
            margin-bottom: var(--spacing-sm);
        }
        .chat-tool-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .chat-tool-btn:hover {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .chat-tool-btn svg {
            width: 18px;
            height: 18px;
        }
        .chat-tool-btn.active {
            color: var(--primary-color);
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        .chat-input {
            flex: 1;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-family: inherit;
            resize: none;
            min-height: 40px;
            max-height: 120px;
            line-height: 1.5;
        }
        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .chat-send-btn {
            padding: var(--spacing-sm) var(--spacing-lg);
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-family: inherit;
        }
        .chat-send-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        .chat-send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .emoji-panel {
            position: absolute;
            bottom: 100%;
            left: 0;
            margin-bottom: 8px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-md);
            box-shadow: var(--shadow-lg);
            display: none;
            width: 320px;
            z-index: 10;
        }
        .emoji-panel.visible {
            display: block;
        }
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
        }
        .emoji-item {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: all 0.15s ease;
        }
        .emoji-item:hover {
            background: var(--bg-tertiary);
            transform: scale(1.2);
        }
        .nickname-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }
        .nickname-modal-overlay.hidden {
            display: none;
        }
        .nickname-modal {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-2xl);
            width: 400px;
            max-width: 90vw;
            box-shadow: var(--shadow-xl);
        }
        .nickname-modal h3 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            text-align: center;
        }
        .nickname-modal p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }
        .nickname-modal input {
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-family: inherit;
            margin-bottom: var(--spacing-lg);
        }
        .nickname-modal input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .nickname-modal .btn-enter {
            width: 100%;
            padding: var(--spacing-md);
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s ease;
        }
        .nickname-modal .btn-enter:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .image-preview-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .image-preview-overlay.visible {
            display: flex;
        }
        .image-preview-overlay img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: var(--radius-lg);
        }
        .chat-connecting {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .chat-connecting svg {
            width: 48px;
            height: 48px;
            margin-bottom: var(--spacing-md);
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .upload-preview {
            display: flex;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
            flex-wrap: wrap;
        }
        .upload-preview-item {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .upload-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .upload-preview-remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 18px;
            height: 18px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 768px) {
            .chat-wrapper {
                flex-direction: column;
                height: auto;
                min-height: calc(100vh - 200px);
            }
            .chat-sidebar {
                width: 100%;
                flex-direction: row;
                gap: var(--spacing-sm);
            }
            .chat-sidebar-card {
                flex: 1;
            }
            .online-list {
                max-height: 100px;
            }
            .chat-main {
                min-height: 400px;
            }
            .chat-msg {
                max-width: 90%;
            }
            .emoji-panel {
                width: 260px;
            }
            .emoji-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 21 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1>公告信息管理系统</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">首页</a></li>
                <li><a href="add_notice.php">添加公告</a></li>
                <li><a href="search_notice.php">查询公告</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="chat.php" class="active">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div id="nicknameModal" class="nickname-modal-overlay">
                <div class="nickname-modal">
                    <h3>🚀 加入在线答疑</h3>
                    <p>请输入您的昵称，开始在线交流</p>
                    <input type="text" id="nicknameInput" placeholder="请输入昵称（2-20字符）" maxlength="20" autocomplete="off">
                    <button class="btn-enter" onclick="submitNickname()">进入聊天</button>
                </div>
            </div>

            <div class="chat-wrapper">
                <div class="chat-sidebar">
                    <div class="chat-sidebar-card">
                        <div class="chat-sidebar-header">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;color:var(--primary-color)"><path d="M4 6H20M4 12H20M4 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <h4>聊天房间</h4>
                        </div>
                        <div class="chat-room-list" id="roomList"></div>
                    </div>
                    <div class="chat-sidebar-card">
                        <div class="chat-sidebar-header">
                            <div class="online-dot"></div>
                            <h4>在线用户</h4>
                            <span class="online-count-badge" id="onlineCount">0</span>
                        </div>
                        <div class="online-list" id="onlineList"></div>
                    </div>
                </div>

                <div class="chat-main">
                    <div class="chat-header">
                        <div class="chat-header-info">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:20px;height:20px;color:var(--primary-color)"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <h3 id="currentRoomName">公共答疑大厅</h3>
                            <span class="room-desc" id="currentRoomDesc"></span>
                        </div>
                        <div class="chat-header-admin">
                            <label class="admin-toggle">
                                <input type="checkbox" id="adminToggle" onchange="toggleAdminMode()">
                                管理员模式
                            </label>
                            <input type="text" class="admin-token-input" id="adminTokenInput" placeholder="管理员Token">
                        </div>
                    </div>

                    <div class="chat-messages" id="chatMessages">
                        <div class="chat-connecting" id="chatConnecting">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <p>正在连接聊天服务...</p>
                        </div>
                    </div>

                    <div class="chat-input-area">
                        <div class="chat-input-tools">
                            <div class="chat-tool-btn" id="emojiBtn" onclick="toggleEmojiPanel()" title="表情">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 14C8 14 9.5 16 12 16C14.5 16 16 14 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9H9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M15 9H15.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <div class="emoji-panel" id="emojiPanel"></div>
                            </div>
                            <div class="chat-tool-btn" onclick="triggerImageUpload()" title="图片">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 15L16 10L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <input type="file" id="imageInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="handleImageSelect(event)">
                        </div>
                        <div class="upload-preview" id="uploadPreview"></div>
                        <div class="chat-input-row">
                            <textarea class="chat-input" id="chatInput" placeholder="输入消息，回车发送，Shift+回车换行..." rows="1" onkeydown="handleInputKeydown(event)" oninput="autoResizeInput()"></textarea>
                            <button class="chat-send-btn" id="sendBtn" onclick="sendMessage()">发送</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="image-preview-overlay" id="imagePreview" onclick="closeImagePreview()">
        <img id="previewImage" src="" alt="preview">
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
    (function() {
        var EMOJIS = ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','🥰','😘','😗','😙','😚','🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','🙁','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','🥵','🥶','😳','🤪','😵','😠','😡','🤬','😷','🤒','🤕','🤢','🤮','🥴','😇','🥳','🥺','🤠','🤡','🤥','🤫','🤭','🧐','🤓','💪','👍','👎','👏','🙌','🤝','❤️','🔥','⭐','✅','❌','🎉','🎊','💬','💡','📝','📌','📎','🔗','🚀','✨','🌟','💯','🏆','🎯'];

        var state = {
            nickname: localStorage.getItem('chat_nickname') || '',
            currentRoomId: null,
            lastId: 0,
            rooms: [],
            isAdmin: false,
            adminToken: '',
            pollTimer: null,
            heartbeatTimer: null,
            pendingImage: null
        };

        window.chatState = state;

        initEmojiPanel();

        if (state.nickname) {
            document.getElementById('nicknameModal').classList.add('hidden');
            initChat();
        } else {
            document.getElementById('nicknameInput').focus();
        }

        document.getElementById('nicknameInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitNickname();
            }
        });

        window.submitNickname = function() {
            var nickname = document.getElementById('nicknameInput').value.trim();
            if (nickname.length < 2 || nickname.length > 20) {
                alert('昵称需要2-20个字符');
                return;
            }
            state.nickname = nickname;
            localStorage.setItem('chat_nickname', nickname);
            document.getElementById('nicknameModal').classList.add('hidden');
            initChat();
        };

        function initChat() {
            loadRooms(function() {
                if (state.rooms.length > 0) {
                    var defaultRoom = null;
                    for (var i = 0; i < state.rooms.length; i++) {
                        if (state.rooms[i].is_default === '1' || state.rooms[i].is_default === 1) {
                            defaultRoom = state.rooms[i];
                            break;
                        }
                    }
                    if (!defaultRoom) defaultRoom = state.rooms[0];
                    switchRoom(defaultRoom.id);
                }
            });
            startOnlinePolling();
        }

        function loadRooms(callback) {
            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'rooms'})
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    state.rooms = data.data;
                    renderRoomList();
                    if (callback) callback();
                }
            });
        }

        function renderRoomList() {
            var html = '';
            state.rooms.forEach(function(room) {
                var isActive = room.id == state.currentRoomId ? ' active' : '';
                html += '<div class="chat-room-item' + isActive + '" onclick="switchRoom(' + room.id + ')">';
                html += '  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '  ' + escapeHtml(room.name);
                html += '</div>';
            });
            document.getElementById('roomList').innerHTML = html;
        }

        window.switchRoom = function(roomId) {
            if (state.currentRoomId && state.currentRoomId != roomId) {
                leaveRoom(state.currentRoomId, function() {
                    joinRoom(roomId);
                });
            } else if (!state.currentRoomId) {
                joinRoom(roomId);
            } else {
                state.currentRoomId = roomId;
                renderRoomList();
                loadOnlineUsers();
            }
        };

        function joinRoom(roomId) {
            var postData = {
                action: 'join',
                room_id: roomId,
                nickname: state.nickname
            };
            if (state.isAdmin && state.adminToken) {
                postData.admin_token = state.adminToken;
            }
            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(postData)
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    state.currentRoomId = roomId;
                    state.lastId = 0;
                    var room = null;
                    for (var i = 0; i < state.rooms.length; i++) {
                        if (state.rooms[i].id == roomId) { room = state.rooms[i]; break; }
                    }
                    if (room) {
                        document.getElementById('currentRoomName').textContent = room.name;
                        document.getElementById('currentRoomDesc').textContent = room.description || '';
                    }
                    renderRoomList();
                    loadMessages();
                    loadOnlineUsers();
                    startPolling();
                    startHeartbeat();
                    document.getElementById('chatConnecting').style.display = 'none';
                }
            });
        }

        function leaveRoom(roomId, callback) {
            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'leave', room_id: roomId})
            }).then(function(res) { return res.json(); })
            .then(function() {
                if (callback) callback();
            });
        }

        function loadMessages() {
            var postData = {
                action: 'messages',
                room_id: state.currentRoomId,
                last_id: state.lastId,
                limit: 50
            };
            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(postData)
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && data.data.messages.length > 0) {
                    renderMessages(data.data.messages, true);
                    state.lastId = data.data.last_id;
                }
            });
        }

        function renderMessages(messages, append) {
            var container = document.getElementById('chatMessages');
            var wasAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 50;
            var html = '';

            messages.forEach(function(msg) {
                if (msg.message_type === 'system') {
                    html += '<div class="chat-msg system">';
                    html += '  <div class="msg-system">' + escapeHtml(msg.content) + '</div>';
                    html += '</div>';
                } else {
                    var isSelf = false;
                    var isAdminMsg = msg.is_admin == 1 || msg.is_admin === '1';
                    var msgClass = 'chat-msg';
                    if (isSelf) msgClass += ' self';
                    if (isAdminMsg) msgClass += ' admin-msg';

                    html += '<div class="' + msgClass + '">';

                    var avatarClass = isAdminMsg ? 'msg-avatar admin-avatar' : 'msg-avatar user-avatar';
                    if (isSelf) avatarClass = 'msg-avatar';
                    var initial = msg.nickname ? msg.nickname.charAt(0).toUpperCase() : '?';
                    html += '  <div class="' + avatarClass + '">' + escapeHtml(initial) + '</div>';
                    html += '  <div class="msg-body">';
                    html += '    <div class="msg-nickname">';
                    html += '      ' + escapeHtml(msg.nickname);
                    if (isAdminMsg) {
                        html += '      <span class="admin-badge">管理员</span>';
                    }
                    html += '    </div>';

                    var contentHtml = '';
                    if (msg.message_type === 'image') {
                        contentHtml = '<img src="' + escapeHtml(msg.content) + '" onclick="previewImage(\'' + escapeHtml(msg.content) + '\')" alt="图片消息">';
                    } else {
                        contentHtml = formatTextContent(msg.content, msg.mention_nickname);
                    }

                    html += '    <div class="msg-content">' + contentHtml + '</div>';
                    html += '    <div class="msg-time">' + formatTime(msg.created_at) + '</div>';
                    html += '  </div>';
                    html += '</div>';
                }
            });

            if (append) {
                var existingMsgs = container.querySelectorAll('.chat-msg');
                var lastExisting = existingMsgs.length > 0 ? existingMsgs[existingMsgs.length - 1] : null;
                container.insertAdjacentHTML('beforeend', html);
            } else {
                var connecting = document.getElementById('chatConnecting');
                if (connecting) connecting.style.display = 'none';
                container.innerHTML = html;
            }

            if (wasAtBottom || append) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function formatTextContent(content, mentionNickname) {
            var text = escapeHtml(content);
            if (mentionNickname) {
                var mention = '@' + escapeHtml(mentionNickname);
                text = text.replace(mention, '<span class="mention-highlight">' + mention + '</span>');
            }
            return text;
        }

        function startPolling() {
            if (state.pollTimer) clearInterval(state.pollTimer);
            state.pollTimer = setInterval(function() {
                if (!state.currentRoomId) return;
                var postData = {
                    action: 'messages',
                    room_id: state.currentRoomId,
                    last_id: state.lastId,
                    limit: 50
                };
                fetch('api_chat.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(postData)
                }).then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success && data.data.messages.length > 0) {
                        renderMessages(data.data.messages, true);
                        state.lastId = data.data.last_id;
                    }
                });
            }, 3000);
        }

        function startHeartbeat() {
            if (state.heartbeatTimer) clearInterval(state.heartbeatTimer);
            state.heartbeatTimer = setInterval(function() {
                if (!state.currentRoomId) return;
                var postData = {
                    action: 'heartbeat',
                    room_id: state.currentRoomId
                };
                if (state.isAdmin && state.adminToken) {
                    postData.admin_token = state.adminToken;
                }
                fetch('api_chat.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(postData)
                });
            }, 30000);
        }

        var onlineTimer = null;
        function startOnlinePolling() {
            loadOnlineUsers();
            onlineTimer = setInterval(loadOnlineUsers, 10000);
        }

        function loadOnlineUsers() {
            if (!state.currentRoomId) return;
            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'online', room_id: state.currentRoomId})
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('onlineCount').textContent = data.data.count;
                    var html = '';
                    data.data.users.forEach(function(user) {
                        var isAdmin = user.is_admin == 1 || user.is_admin === '1';
                        var cls = isAdmin ? 'online-user admin-user' : 'online-user';
                        html += '<div class="' + cls + '">';
                        html += '  <span class="user-dot"></span>';
                        html += '  ' + escapeHtml(user.nickname);
                        if (isAdmin) html += ' <span style="font-size:0.625rem;color:var(--warning-color)">[管理]</span>';
                        html += '</div>';
                    });
                    document.getElementById('onlineList').innerHTML = html;
                }
            });
        }

        window.sendMessage = function() {
            var input = document.getElementById('chatInput');
            var text = input.value.trim();
            if (!text && !state.pendingImage) return;
            if (!state.currentRoomId) return;

            if (state.pendingImage) {
                uploadAndSendImage(state.pendingImage);
                state.pendingImage = null;
                document.getElementById('uploadPreview').innerHTML = '';
                input.value = '';
                return;
            }

            var mentionNickname = null;
            var mentionMatch = text.match(/^@(\S+)\s/);
            if (mentionMatch) {
                mentionNickname = mentionMatch[1];
            }

            var postData = {
                action: 'send',
                room_id: state.currentRoomId,
                nickname: state.nickname,
                content: text,
                mention_nickname: mentionNickname
            };
            if (state.isAdmin && state.adminToken) {
                postData.admin_token = state.adminToken;
            }

            fetch('api_chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(postData)
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    input.value = '';
                    autoResizeInput();
                    loadMessages();
                }
            });
        };

        function uploadAndSendImage(file) {
            var formData = new FormData();
            formData.append('action', 'upload_image');
            formData.append('room_id', state.currentRoomId);
            formData.append('nickname', state.nickname);
            formData.append('image', file);
            if (state.isAdmin && state.adminToken) {
                formData.append('admin_token', state.adminToken);
            }

            fetch('api_chat.php', {
                method: 'POST',
                body: formData
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    loadMessages();
                }
            });
        }

        window.handleInputKeydown = function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        };

        window.autoResizeInput = function() {
            var input = document.getElementById('chatInput');
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 120) + 'px';
        };

        window.toggleEmojiPanel = function() {
            var panel = document.getElementById('emojiPanel');
            var btn = document.getElementById('emojiBtn');
            panel.classList.toggle('visible');
            btn.classList.toggle('active');
        };

        function initEmojiPanel() {
            var html = '<div class="emoji-grid">';
            EMOJIS.forEach(function(emoji) {
                html += '<span class="emoji-item" onclick="insertEmoji(\'' + emoji + '\')">' + emoji + '</span>';
            });
            html += '</div>';
            document.getElementById('emojiPanel').innerHTML = html;
        }

        window.insertEmoji = function(emoji) {
            var input = document.getElementById('chatInput');
            var start = input.selectionStart;
            var end = input.selectionEnd;
            var text = input.value;
            input.value = text.substring(0, start) + emoji + text.substring(end);
            input.selectionStart = input.selectionEnd = start + emoji.length;
            input.focus();
            autoResizeInput();
            toggleEmojiPanel();
        };

        window.triggerImageUpload = function() {
            document.getElementById('imageInput').click();
        };

        window.handleImageSelect = function(e) {
            var file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                alert('图片大小不能超过5MB');
                return;
            }
            state.pendingImage = file;

            var reader = new FileReader();
            reader.onload = function(ev) {
                var preview = document.getElementById('uploadPreview');
                preview.innerHTML = '<div class="upload-preview-item">' +
                    '<img src="' + ev.target.result + '" alt="preview">' +
                    '<button class="upload-preview-remove" onclick="removePendingImage()">✕</button>' +
                    '</div>';
            };
            reader.readAsDataURL(file);
            e.target.value = '';
        };

        window.removePendingImage = function() {
            state.pendingImage = null;
            document.getElementById('uploadPreview').innerHTML = '';
        };

        window.toggleAdminMode = function() {
            var checked = document.getElementById('adminToggle').checked;
            var tokenInput = document.getElementById('adminTokenInput');
            state.isAdmin = checked;
            if (checked) {
                tokenInput.classList.add('visible');
                tokenInput.focus();
            } else {
                tokenInput.classList.remove('visible');
                state.adminToken = '';
                tokenInput.value = '';
            }
        };

        document.getElementById('adminTokenInput').addEventListener('input', function(e) {
            state.adminToken = e.target.value.trim();
        });

        window.previewImage = function(src) {
            document.getElementById('previewImage').src = src;
            document.getElementById('imagePreview').classList.add('visible');
        };

        window.closeImagePreview = function() {
            document.getElementById('imagePreview').classList.remove('visible');
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImagePreview();
                var emojiPanel = document.getElementById('emojiPanel');
                if (emojiPanel.classList.contains('visible')) {
                    toggleEmojiPanel();
                }
            }
        });

        document.addEventListener('click', function(e) {
            var emojiBtn = document.getElementById('emojiBtn');
            var emojiPanel = document.getElementById('emojiPanel');
            if (!emojiBtn.contains(e.target)) {
                emojiPanel.classList.remove('visible');
                emojiBtn.classList.remove('active');
            }
        });

        window.addEventListener('beforeunload', function() {
            if (state.currentRoomId) {
                navigator.sendBeacon('api_chat.php', JSON.stringify({
                    action: 'leave',
                    room_id: state.currentRoomId
                }));
            }
        });

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatTime(dateStr) {
            if (!dateStr) return '';
            var d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr.substring(0, 16);
            var now = new Date();
            var diff = (now - d) / 1000;
            if (diff < 60) return '刚刚';
            if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
            if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
            return dateStr.substring(5, 16);
        }
    })();
    </script>
</body>
</html>
