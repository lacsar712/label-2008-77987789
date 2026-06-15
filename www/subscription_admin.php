<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订阅管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .email-search-row { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 20px; }
        .email-search-row .form-group { flex: 1; margin: 0; }
        .sub-toolbar { display: flex; gap: 10px; margin-bottom: 16px; align-items: center; flex-wrap: wrap; }
        .sub-toolbar .spacer { flex: 1; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; }
        .badge-paused { background: #fef3c7; color: #92400e; }
        .badge-active { background: #d1fae5; color: #065f46; }
        .type-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 12px; background: #e0e7ff; color: #3730a3; }
        .modal-mask { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 999; }
        .modal-mask.show { display: flex; }
        .modal { background: #fff; border-radius: 12px; width: 90%; max-width: 500px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal h3 { margin: 0 0 16px 0; font-size: 18px; }
        .modal .form-row { display: flex; flex-direction: column; gap: 12px; }
        .modal .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .sub-list-wrap { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .check-col { width: 40px; text-align: center; }
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
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="subscription_admin.php" class="active">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <h2>订阅管理</h2>
                <p style="margin-top:-8px;color:#6b7280;">按邮箱查询并管理订阅条件，支持分类/作者/关键词/优先级四种订阅类型</p>
                <div class="search-form">
                    <div class="email-search-row">
                        <div class="form-group">
                            <label for="email_input">订阅人邮箱</label>
                            <input type="email" id="email_input" placeholder="请输入订阅人邮箱，如 user@example.com">
                        </div>
                        <button class="btn btn-primary" onclick="loadSubscriptions()">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            查询订阅
                        </button>
                    </div>
                </div>
            </div>

            <div id="result_area" style="display:none;">
                <div class="sub-toolbar">
                    <div>
                        <strong id="current_email_label"></strong>
                        <span class="results-info" style="display:inline;margin-left:12px;">共 <strong id="total_count">0</strong> 条订阅</span>
                    </div>
                    <div class="spacer"></div>
                    <button class="btn btn-secondary" onclick="batchTogglePause(1)">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10 9V15M14 9V15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        批量暂停
                    </button>
                    <button class="btn btn-secondary" onclick="batchTogglePause(0)">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14.5 3.5L19.5 8.5L9 19H4V14L14.5 3.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        批量恢复
                    </button>
                    <button class="btn btn-primary" onclick="openModal()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新增订阅
                    </button>
                </div>

                <div class="sub-list-wrap">
                    <table class="notices-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th class="check-col"><input type="checkbox" id="check_all" onchange="toggleAll(this)"></th>
                                <th width="8%">ID</th>
                                <th width="16%">订阅类型</th>
                                <th width="28%">订阅值</th>
                                <th width="12%">状态</th>
                                <th width="14%">创建时间</th>
                                <th width="22%">操作</th>
                            </tr>
                        </thead>
                        <tbody id="sub_tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container"><p>&copy; 2024 公告信息管理系统. All rights reserved.</p></div>
    </footer>

    <div class="modal-mask" id="sub_modal">
        <div class="modal">
            <h3 id="modal_title">新增订阅</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>订阅类型 <span class="required">*</span></label>
                    <select id="f_sub_type">
                        <option value="category">分类（匹配公告分类字段）</option>
                        <option value="author">作者（匹配发布人字段）</option>
                        <option value="keyword">关键词（匹配标题或内容）</option>
                        <option value="priority">优先级（高/中/低精确匹配）</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>订阅值 <span class="required">*</span></label>
                    <input type="text" id="f_sub_value" placeholder="请输入订阅值，优先级请填 high/medium/low">
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" onclick="closeModal()">取消</button>
                <button class="btn btn-primary" onclick="saveSubscription()">保存</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const API = 'api_subscription.php';
        let currentEmail = '';
        let currentData = [];
        let editId = null;

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

        function loadSubscriptions() {
            const email = document.getElementById('email_input').value.trim();
            if (!email) { showToast('请输入邮箱', 'error'); return; }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showToast('邮箱格式不正确', 'error'); return; }
            currentEmail = email;
            api('list_by_email', {email}, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                currentData = res.data.items;
                renderList(currentData);
                document.getElementById('result_area').style.display = 'block';
                document.getElementById('current_email_label').textContent = email;
            });
        }

        function renderList(list) {
            const tbody = document.getElementById('sub_tbody');
            document.getElementById('total_count').textContent = list.length;
            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/></svg>
                    <p>该邮箱暂无订阅条件，点击右上角"新增订阅"添加</p>
                </div></td></tr>`;
                return;
            }
            const typeLabel = {category:'分类', author:'作者', keyword:'关键词', priority:'优先级'};
            tbody.innerHTML = list.map(s => `
                <tr>
                    <td class="check-col"><input type="checkbox" class="sub-check" value="${s.id}"></td>
                    <td>${s.id}</td>
                    <td><span class="type-tag">${typeLabel[s.sub_type] || s.sub_type}</span></td>
                    <td style="word-break:break-all;">${escapeHtml(s.sub_value)}</td>
                    <td>${s.is_paused ? '<span class="badge badge-paused">已暂停</span>' : '<span class="badge badge-active">生效中</span>'}</td>
                    <td>${s.created_at ? s.created_at.replace('T',' ').substring(0,16) : '-'}</td>
                    <td class="action-buttons">
                        <button class="btn-icon-action edit" title="编辑" onclick="openEdit(${s.id})">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                        <button class="btn-icon-action" title="${s.is_paused ? '恢复' : '暂停'}" onclick="togglePause(${s.id}, ${s.is_paused ? 0 : 1})" style="color:${s.is_paused ? '#10b981' : '#f59e0b'};">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">${s.is_paused
                                ? '<path d="M8 5V19L19 12L8 5Z" stroke="currentColor" stroke-width="2"/>'
                                : '<path d="M10 9V15M14 9V15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>'}
                            </svg>
                        </button>
                        <button class="btn-icon-action delete" title="删除" onclick="deleteSub(${s.id})">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2"/></svg>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

        function toggleAll(cb) {
            document.querySelectorAll('.sub-check').forEach(c => c.checked = cb.checked);
        }

        function getCheckedIds() {
            return Array.from(document.querySelectorAll('.sub-check:checked')).map(c => parseInt(c.value));
        }

        function batchTogglePause(isPaused) {
            if (!currentEmail) return;
            const ids = getCheckedIds();
            if (ids.length === 0 && !confirm('未勾选订阅，将对该邮箱下所有订阅执行' + (isPaused ? '暂停' : '恢复') + '操作，是否继续？')) return;
            api('batch_pause', {email: currentEmail, is_paused: isPaused, ids}, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast(res.message);
                loadSubscriptions();
            });
        }

        function togglePause(id, isPaused) {
            api('toggle_pause', {id, is_paused: isPaused}, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast(res.message);
                loadSubscriptions();
            });
        }

        function deleteSub(id) {
            if (!confirm('确定删除该订阅吗？相关推送记录也会被级联删除。')) return;
            api('delete', {id}, res => {
                if (!res.success) { showToast(res.message, 'error'); return; }
                showToast('删除成功');
                loadSubscriptions();
            });
        }

        function openModal() {
            editId = null;
            document.getElementById('modal_title').textContent = '新增订阅';
            document.getElementById('f_sub_type').value = 'category';
            document.getElementById('f_sub_value').value = '';
            document.getElementById('sub_modal').classList.add('show');
        }

        function openEdit(id) {
            const s = currentData.find(x => x.id === id);
            if (!s) return;
            editId = id;
            document.getElementById('modal_title').textContent = '编辑订阅';
            document.getElementById('f_sub_type').value = s.sub_type;
            document.getElementById('f_sub_value').value = s.sub_value;
            document.getElementById('sub_modal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('sub_modal').classList.remove('show');
        }

        function saveSubscription() {
            const sub_type = document.getElementById('f_sub_type').value;
            const sub_value = document.getElementById('f_sub_value').value.trim();
            if (!sub_value) { showToast('订阅值不能为空', 'error'); return; }
            if (sub_type === 'priority' && !['high','medium','low'].includes(sub_value)) {
                showToast('优先级订阅值必须为 high / medium / low', 'error'); return;
            }
            if (editId) {
                api('update', {id: editId, sub_type, sub_value}, res => {
                    if (!res.success) { showToast(res.message, 'error'); return; }
                    showToast('更新成功');
                    closeModal();
                    loadSubscriptions();
                });
            } else {
                api('create', {email: currentEmail, sub_type, sub_value}, res => {
                    if (!res.success) { showToast(res.message, 'error'); return; }
                    showToast('创建成功');
                    closeModal();
                    loadSubscriptions();
                });
            }
        }

        document.getElementById('email_input').addEventListener('keydown', e => {
            if (e.key === 'Enter') loadSubscriptions();
        });
    </script>
</body>
</html>
