<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>推送记录 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .filter-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .filter-row .form-group { flex: 1; min-width: 200px; margin: 0; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .status-generated { background: #dbeafe; color: #1e40af; }
        .status-sent { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .type-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 12px; background: #e0e7ff; color: #3730a3; margin-right: 4px; }
        .notice-link { color: #2563eb; text-decoration: none; font-weight: 500; }
        .notice-link:hover { text-decoration: underline; }
        .summary-cell { max-width: 380px; }
        .summary-text { color: #4b5563; font-size: 13px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .toast { position: fixed; top: 24px; right: 24px; padding: 12px 20px; border-radius: 8px; z-index: 1000; color: #fff; font-weight: 500; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); opacity: 0; transform: translateY(-20px); transition: all 0.3s; }
        .toast.show { opacity: 1; transform: translateY(0); }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        .empty-state { padding: 60px 20px; text-align: center; color: #9ca3af; }
        .empty-state svg { width: 56px; height: 56px; margin: 0 auto 12px; opacity: 0.5; }
    </style>
</head>
<body>
    <?php require_once 'config.php'; ensureSubscriptionTables(); ?>
    
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
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php" class="active">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <h2>推送记录</h2>
                <p style="margin-top:-8px;color:#6b7280;">查看公告命中订阅后生成的推送记录，可按订阅人邮箱和推送状态进行筛选</p>
                <div class="search-form">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="f_email">订阅人邮箱</label>
                            <input type="email" id="f_email" placeholder="订阅人邮箱，可留空">
                        </div>
                        <div class="form-group">
                            <label for="f_status">推送状态</label>
                            <select id="f_status">
                                <option value="">全部状态</option>
                                <option value="generated">已生成</option>
                                <option value="sent">已发送</option>
                                <option value="failed">失败</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" onclick="currentPage=1;loadRecords()">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            查询
                        </button>
                        <button class="btn btn-secondary" onclick="resetFilter()">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            重置
                        </button>
                    </div>
                </div>
            </div>

            <div class="results-info">
                <p>共找到 <strong id="total_num">0</strong> 条记录，当前第 <strong id="cur_page">1</strong> / <strong id="tot_pages">1</strong> 页</p>
            </div>

            <div class="notices-table-container">
                <table class="notices-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="12%">订阅人</th>
                            <th width="14%">命中条件</th>
                            <th width="18%">命中公告</th>
                            <th width="28%">推送摘要</th>
                            <th width="9%">状态</th>
                            <th width="9%">生成时间</th>
                            <th width="5%">操作</th>
                        </tr>
                    </thead>
                    <tbody id="record_tbody"></tbody>
                </table>
            </div>

            <div class="pagination" id="pagination_area"></div>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 公告信息管理系统. All rights reserved.</p></div>
    </footer>

    <div id="toast" class="toast"></div>

    <script>
        const API = 'api_subscription.php';
        const PAGE_SIZE = 10;
        let currentPage = 1;

        function api(action, payload, cb) {
            fetch(API, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(Object.assign({action}, payload))
            }).then(r => r.json()).then(cb).catch(err => showToast('请求失败: ' + err.message, 'error'));
        }

        function showToast(msg, type = 'success') {
            const el = document.getElementById('toast');
            el.className = 'toast ' + type + ' show';
            el.textContent = msg;
            setTimeout(() => el.classList.remove('show'), 2500);
        }

        function resetFilter() {
            document.getElementById('f_email').value = '';
            document.getElementById('f_status').value = '';
            currentPage = 1;
            loadRecords();
        }

        function loadRecords() {
            const email = document.getElementById('f_email').value.trim();
            const status = document.getElementById('f_status').value;
            api('push_list', {
                page: currentPage,
                per_page: PAGE_SIZE,
                email: email,
                push_status: status === '' ? null : status
            }, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                renderRecords(res.data);
            });
        }

        function renderRecords(data) {
            const { total, page, per_page, total_pages, items } = data;
            document.getElementById('total_num').textContent = total;
            document.getElementById('cur_page').textContent = page;
            document.getElementById('tot_pages').textContent = Math.max(1, total_pages);

            const tbody = document.getElementById('record_tbody');
            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8"><div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" stroke="currentColor" stroke-width="2"/></svg>
                    <p>暂无推送记录</p>
                </div></td></tr>`;
            } else {
                const typeLabel = {category:'分类', author:'作者', keyword:'关键词', priority:'优先级'};
                tbody.innerHTML = items.map(r => `
                    <tr>
                        <td>${r.id}</td>
                        <td>
                            <div style="font-size:13px;line-height:1.4;">${escapeHtml(r.email || '-')}</div>
                            <div style="font-size:11px;color:#9ca3af;margin-top:2px;">订阅ID: ${r.subscription_id}</div>
                        </td>
                        <td>
                            <span class="type-tag">${typeLabel[r.sub_type] || r.sub_type}</span>
                            <div style="font-size:12px;color:#4b5563;margin-top:4px;word-break:break-all;">${escapeHtml(r.sub_value || '-')}</div>
                        </td>
                        <td>
                            <a href="notice_detail.php?id=${r.notice_id}" class="notice-link" target="_blank">${escapeHtml(r.notice_title || '(已删除公告)')}</a>
                            <div style="font-size:11px;color:#9ca3af;margin-top:2px;">发布人: ${escapeHtml(r.notice_author || '-')}</div>
                        </td>
                        <td class="summary-cell">
                            <div class="summary-text" title="${escapeHtml(r.summary)}">${escapeHtml(r.summary || '-')}</div>
                        </td>
                        <td>
                            <span class="status-badge status-${r.push_status}">${r.push_status_label || r.push_status}</span>
                        </td>
                        <td style="font-size:12px;color:#6b7280;">${formatTime(r.created_at)}</td>
                        <td class="action-buttons">
                            <button class="btn-icon-action edit" title="重新发送" onclick="resend(${r.id})">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                `).join('');
            }

            renderPagination(page, total_pages);
        }

        function renderPagination(page, totalPages) {
            const area = document.getElementById('pagination_area');
            if (totalPages <= 1) { area.innerHTML = ''; return; }

            let html = '';
            if (page > 1) {
                html += `<a href="javascript:goPage(${page - 1})" class="page-link">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2"/></svg>
                    上一页
                </a>`;
            }
            const start = Math.max(1, page - 2);
            const end = Math.min(totalPages, page + 2);
            if (start > 1) {
                html += `<a href="javascript:goPage(1)" class="page-number">1</a>`;
                if (start > 2) html += `<span class="page-ellipsis">...</span>`;
            }
            for (let i = start; i <= end; i++) {
                html += `<a href="javascript:goPage(${i})" class="page-number ${i === page ? 'active' : ''}">${i}</a>`;
            }
            if (end < totalPages) {
                if (end < totalPages - 1) html += `<span class="page-ellipsis">...</span>`;
                html += `<a href="javascript:goPage(${totalPages})" class="page-number">${totalPages}</a>`;
            }
            if (page < totalPages) {
                html += `<a href="javascript:goPage(${page + 1})" class="page-link">
                    下一页
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2"/></svg>
                </a>`;
            }
            area.innerHTML = html;
        }

        function goPage(p) { currentPage = p; loadRecords(); }

        function resend(id) {
            if (!confirm('确定要重新发送该推送记录吗？将把状态重置为"已生成"。')) return;
            api('push_resend', {id}, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast(res.message);
                loadRecords();
            });
        }

        function escapeHtml(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

        function formatTime(t) {
            if (!t) return '-';
            return t.replace('T', ' ').substring(0, 16);
        }

        loadRecords();
    </script>
</body>
</html>
