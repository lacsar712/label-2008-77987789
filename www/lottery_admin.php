<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureLotteryTables();

$conn = getConnection();
$stmt = $conn->prepare("SELECT id, title FROM notices WHERE status = 'published' ORDER BY publish_date DESC");
$stmt->execute();
$notices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽奖活动管理 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .lottery-list-item {
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
        .lottery-list-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .lottery-info { flex: 1; }
        .lottery-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        .lottery-desc {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-sm);
        }
        .lottery-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: 0.75rem;
            color: var(--text-muted);
            flex-wrap: wrap;
        }
        .lottery-actions {
            display: flex;
            gap: var(--spacing-sm);
            align-items: center;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.draft { background: rgba(156, 163, 175, 0.15); color: #9ca3af; }
        .status-badge.active { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-badge.paused { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .status-badge.finished { background: rgba(99, 102, 241, 0.15); color: var(--primary-light); }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6); z-index: 999; display: none;
            align-items: center; justify-content: center; padding: var(--spacing-lg);
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--bg-secondary); border-radius: var(--radius-xl);
            width: 100%; max-width: 800px; max-height: 90vh; overflow-y: auto;
            box-shadow: var(--shadow-xl);
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            padding: var(--spacing-xl); border-bottom: 1px solid var(--border-color);
            position: sticky; top: 0; background: var(--bg-secondary); z-index: 1;
        }
        .modal-header h2 { font-size: 1.5rem; color: var(--text-primary); }
        .modal-close {
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            background: var(--bg-tertiary); border: 1px solid var(--border-color);
            border-radius: var(--radius-md); color: var(--text-secondary); cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal-close:hover { background: var(--error-color); border-color: var(--error-color); color: white; }
        .modal-body { padding: var(--spacing-xl); }
        .modal-footer {
            display: flex; justify-content: flex-end; gap: var(--spacing-md);
            padding: var(--spacing-xl); border-top: 1px solid var(--border-color);
            position: sticky; bottom: 0; background: var(--bg-secondary);
        }
        .form-group { margin-bottom: var(--spacing-lg); }
        .form-group label {
            display: block; color: var(--text-primary); font-weight: 500;
            margin-bottom: var(--spacing-sm); font-size: 0.875rem;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color);
            color: var(--text-primary); padding: var(--spacing-md);
            border-radius: var(--radius-md); font-size: 1rem; font-family: inherit;
            transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none; border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); }
        .prizes-section { margin-top: var(--spacing-2xl); }
        .section-title {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        .section-title h3 { font-size: 1.25rem; color: var(--text-primary); }
        .prize-item {
            background: var(--bg-tertiary); border: 1px solid var(--border-color);
            border-radius: var(--radius-lg); padding: var(--spacing-lg);
            margin-bottom: var(--spacing-md); display: flex; gap: var(--spacing-md);
            align-items: center;
        }
        .prize-item input { flex: 1; }
        .prize-count-input { max-width: 120px; }
        .remove-prize-btn {
            width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
            background: var(--bg-secondary); border: 1px solid var(--border-color);
            border-radius: var(--radius-md); color: var(--text-secondary); cursor: pointer;
            transition: all 0.2s ease; flex-shrink: 0;
        }
        .remove-prize-btn:hover { border-color: var(--error-color); color: var(--error-color); }
        .add-prize-btn {
            width: 100%; padding: var(--spacing-sm); background: transparent;
            border: 1px dashed var(--border-color); border-radius: var(--radius-md);
            color: var(--text-muted); cursor: pointer; transition: all 0.2s ease; font-size: 0.875rem;
        }
        .add-prize-btn:hover { border-color: var(--primary-color); color: var(--primary-color); }
        .empty-state { text-align: center; padding: var(--spacing-2xl); color: var(--text-muted); }
        .empty-state svg { width: 64px; height: 64px; margin-bottom: var(--spacing-md); opacity: 0.5; }
        .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: var(--bg-tertiary); transition: .3s; border-radius: 26px;
            border: 1px solid var(--border-color);
        }
        .slider:before {
            position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
            background-color: var(--text-muted); transition: .3s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary-color); border-color: var(--primary-color); }
        input:checked + .slider:before { transform: translateX(22px); background-color: white; }
        .stats-badge {
            display: inline-flex; align-items: center; gap: 4px; padding: 0.2rem 0.6rem;
            background: rgba(99, 102, 241, 0.1); color: var(--primary-light);
            border-radius: var(--radius-sm); font-size: 0.75rem; font-weight: 600;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .lottery-list-item { flex-direction: column; align-items: flex-start; gap: var(--spacing-md); }
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
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="lottery_admin.php" class="active">抽奖管理</a></li>
                <li><a href="lottery_list.php">活动列表</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="search-container">
                <div class="section-header" style="margin-bottom: var(--spacing-lg);">
                    <h2>抽奖活动管理</h2>
                    <button class="btn btn-primary" onclick="openEditor()">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        新建活动
                    </button>
                </div>
                <form class="search-form" onsubmit="return false;">
                    <div class="search-fields">
                        <div class="search-field">
                            <label for="searchKeyword">关键词</label>
                            <input type="text" id="searchKeyword" placeholder="搜索活动名称">
                        </div>
                        <div class="search-field">
                            <label for="statusFilter">状态</label>
                            <select id="statusFilter">
                                <option value="">全部</option>
                                <option value="draft">草稿</option>
                                <option value="active">进行中</option>
                                <option value="paused">已暂停</option>
                                <option value="finished">已开奖</option>
                            </select>
                        </div>
                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn btn-primary" onclick="loadLotteries(1)">
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
                <p id="resultsInfo">共找到 <strong>0</strong> 个活动，当前第 <strong>1</strong> / <strong>1</strong> 页</p>
            </div>

            <div id="lotteryList"></div>
            <div class="pagination" id="pagination"></div>
        </div>
    </div>

    <div class="modal-overlay" id="editorModal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">新建抽奖活动</h2>
                <button class="modal-close" onclick="closeEditor()">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="lotteryName">活动名称 <span class="required">*</span></label>
                    <input type="text" id="lotteryName" placeholder="请输入活动名称">
                </div>
                <div class="form-group">
                    <label for="noticeId">关联公告</label>
                    <select id="noticeId">
                        <option value="">不关联</option>
                        <?php foreach ($notices as $n): ?>
                            <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="startTime">参与开始时间 <span class="required">*</span></label>
                        <input type="datetime-local" id="startTime">
                    </div>
                    <div class="form-group">
                        <label for="endTime">参与结束时间 <span class="required">*</span></label>
                        <input type="datetime-local" id="endTime">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="drawTime">开奖时间 <span class="required">*</span></label>
                        <input type="datetime-local" id="drawTime">
                    </div>
                    <div class="form-group">
                        <label for="status">活动状态</label>
                        <select id="status">
                            <option value="draft">草稿</option>
                            <option value="active">启用</option>
                            <option value="paused">暂停</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="conditionText">参与条件</label>
                    <textarea id="conditionText" rows="2" placeholder="例如：关注公众号、转发活动等"></textarea>
                </div>

                <div class="prizes-section">
                    <div class="section-title">
                        <h3>奖品列表</h3>
                        <button class="btn btn-secondary" onclick="addPrize()">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            添加奖品
                        </button>
                    </div>
                    <div id="prizesContainer"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeEditor()">取消</button>
                <button class="btn btn-primary" onclick="saveLottery()">保存</button>
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
        var prizes = [];

        var statusLabels = { draft: '草稿', active: '进行中', paused: '已暂停', finished: '已开奖' };

        function loadLotteries(page) {
            page = page || 1;
            currentPage = page;
            var keyword = document.getElementById('searchKeyword').value.trim();
            var status = document.getElementById('statusFilter').value;
            var params = { page: page, per_page: 10 };
            if (keyword) params.keyword = keyword;
            if (status) params.status = status;

            fetch('api_lotteries.php?' + new URLSearchParams(params))
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) { alert(res.message || '加载失败'); return; }
                    renderList(res.data.list);
                    renderPagination(res.data.pagination);
                });
        }

        function renderList(list) {
            var container = document.getElementById('lotteryList');
            if (!list || list.length === 0) {
                container.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无活动，点击"新建活动"创建</p></div>';
                return;
            }

            var html = '';
            list.forEach(function(l) {
                var prizeCount = 0;
                l.prizes.forEach(function(p) { prizeCount += parseInt(p.count || 0); });
                html += '<div class="lottery-list-item">' +
                    '<div class="lottery-info">' +
                        '<div class="lottery-title">' + escapeHtml(l.name) +
                            ' <span class="status-badge ' + l.status + '">' + statusLabels[l.status] + '</span>' +
                            (l.is_drawn ? ' <span class="status-badge finished">已开奖</span>' : '') +
                        '</div>' +
                        '<div class="lottery-desc">' +
                            (l.notice_title ? '关联公告：' + escapeHtml(l.notice_title) + '，' : '') +
                            '奖品 ' + prizeCount + ' 份，' +
                            (l.condition_text ? escapeHtml(l.condition_text) : '无参与条件') +
                        '</div>' +
                        '<div class="lottery-meta">' +
                            '<span class="stats-badge">' + l.participant_count + ' 人参与</span>' +
                            '<span>开始：' + formatDate(l.start_time) + '</span>' +
                            '<span>结束：' + formatDate(l.end_time) + '</span>' +
                            '<span>开奖：' + formatDate(l.draw_time) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="lottery-actions">' +
                        (l.status !== 'finished' && !l.is_drawn ?
                            '<label class="switch" title="' + (l.status === 'active' ? '点击暂停' : '点击启用') + '">' +
                                '<input type="checkbox" ' + (l.status === 'active' ? 'checked' : '') + ' onchange="toggleStatus(' + l.id + ', this)">' +
                                '<span class="slider"></span>' +
                            '</label>' : '') +
                        '<button class="btn btn-secondary" onclick="viewPage(' + l.id + ')">' +
                            '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '查看' +
                        '</button>' +
                        (l.is_drawn ? '' :
                        '<button class="btn btn-secondary" onclick="editLottery(' + l.id + ')">' +
                            '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                            '编辑' +
                        '</button>') +
                        '<button class="btn btn-secondary" onclick="deleteLottery(' + l.id + ')" style="border-color: var(--error-color); color: var(--error-color);">' +
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
                '共找到 <strong>' + pagination.total + '</strong> 个活动，当前第 <strong>' + pagination.page + '</strong> / <strong>' + pagination.total_pages + '</strong> 页';

            var container = document.getElementById('pagination');
            container.innerHTML = '';
            if (totalPages <= 1) return;

            if (currentPage > 1) {
                var prev = document.createElement('a');
                prev.className = 'page-link';
                prev.href = 'javascript:void(0)';
                prev.innerHTML = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>上一页';
                prev.onclick = function() { loadLotteries(currentPage - 1); };
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
            for (var i = start; i <= end; i++) container.appendChild(createPageNum(i));
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
                next.onclick = function() { loadLotteries(currentPage + 1); };
                container.appendChild(next);
            }
        }

        function createPageNum(page) {
            var a = document.createElement('a');
            a.className = 'page-number' + (page === currentPage ? ' active' : '');
            a.textContent = page;
            a.href = 'javascript:void(0)';
            a.onclick = function() { loadLotteries(page); };
            return a;
        }

        function openEditor() {
            editingId = null;
            prizes = [];
            document.getElementById('modalTitle').textContent = '新建抽奖活动';
            document.getElementById('lotteryName').value = '';
            document.getElementById('noticeId').value = '';
            document.getElementById('startTime').value = '';
            document.getElementById('endTime').value = '';
            document.getElementById('drawTime').value = '';
            document.getElementById('conditionText').value = '';
            document.getElementById('status').value = 'draft';
            renderPrizes();
            document.getElementById('editorModal').classList.add('active');
        }

        function closeEditor() {
            document.getElementById('editorModal').classList.remove('active');
        }

        function editLottery(id) {
            fetch('api_lotteries.php?action=detail&id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) { alert(res.message || '加载失败'); return; }
                    var d = res.data;
                    editingId = id;
                    document.getElementById('modalTitle').textContent = '编辑抽奖活动';
                    document.getElementById('lotteryName').value = d.name;
                    document.getElementById('noticeId').value = d.notice_id || '';
                    document.getElementById('startTime').value = d.start_time ? d.start_time.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('endTime').value = d.end_time ? d.end_time.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('drawTime').value = d.draw_time ? d.draw_time.replace(' ', 'T').substring(0, 16) : '';
                    document.getElementById('conditionText').value = d.condition_text || '';
                    document.getElementById('status').value = d.status;
                    prizes = d.prizes ? d.prizes.map(function(p) { return { name: p.name, count: p.count }; }) : [];
                    renderPrizes();
                    document.getElementById('editorModal').classList.add('active');
                });
        }

        function addPrize() {
            prizes.push({ name: '', count: 1 });
            renderPrizes();
        }

        function removePrize(index) {
            if (prizes.length <= 1) { alert('至少保留一个奖品'); return; }
            prizes.splice(index, 1);
            renderPrizes();
        }

        function updatePrize(index, field, value) {
            if (field === 'count') value = parseInt(value) || 0;
            prizes[index][field] = value;
        }

        function renderPrizes() {
            var container = document.getElementById('prizesContainer');
            if (prizes.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>暂无奖品，点击"添加奖品"按钮添加</p></div>';
                return;
            }
            var html = '';
            prizes.forEach(function(p, i) {
                html += '<div class="prize-item">' +
                    '<input type="text" placeholder="奖品名称" value="' + escapeHtml(p.name || '') + '" onchange="updatePrize(' + i + ', \'name\', this.value)">' +
                    '<input type="number" class="prize-count-input" placeholder="数量" min="1" value="' + (p.count || 1) + '" onchange="updatePrize(' + i + ', \'count\', this.value)">' +
                    '<button class="remove-prize-btn" onclick="removePrize(' + i + ')" title="删除">' +
                        '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                    '</button>' +
                '</div>';
            });
            html += '<button class="add-prize-btn" onclick="addPrize()">+ 添加奖品</button>';
            container.innerHTML = html;
        }

        function saveLottery() {
            var name = document.getElementById('lotteryName').value.trim();
            if (!name) { alert('请填写活动名称'); return; }
            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;
            var drawTime = document.getElementById('drawTime').value;
            if (!startTime || !endTime || !drawTime) { alert('请填写完整的时间信息'); return; }

            var validPrizes = prizes.filter(function(p) { return p.name && p.name.trim() && parseInt(p.count) > 0; });
            if (validPrizes.length === 0) { alert('请至少配置一个有效奖品（名称和数量）'); return; }

            var payload = {
                action: editingId ? 'update' : 'create',
                name: name,
                notice_id: document.getElementById('noticeId').value || null,
                start_time: startTime,
                end_time: endTime,
                draw_time: drawTime,
                condition_text: document.getElementById('conditionText').value.trim() || null,
                status: document.getElementById('status').value,
                prizes: validPrizes.map(function(p) { return { name: p.name.trim(), count: parseInt(p.count) }; })
            };
            if (editingId) payload.id = editingId;

            fetch('api_lotteries.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    alert(res.message);
                    closeEditor();
                    loadLotteries(currentPage);
                } else {
                    alert(res.message || '保存失败');
                }
            });
        }

        function toggleStatus(id, checkbox) {
            fetch('api_lotteries.php?action=toggle_status&id=' + id)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) {
                        alert(res.message || '操作失败');
                        checkbox.checked = !checkbox.checked;
                    } else {
                        loadLotteries(currentPage);
                    }
                });
        }

        function deleteLottery(id) {
            if (!confirm('确定删除该活动吗？')) return;
            fetch('api_lotteries.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            }).then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    alert(res.message);
                    loadLotteries(currentPage);
                } else {
                    alert(res.message || '删除失败');
                }
            });
        }

        function viewPage(id) {
            window.location.href = 'lottery_detail.php?id=' + id;
        }

        function resetFilters() {
            document.getElementById('searchKeyword').value = '';
            document.getElementById('statusFilter').value = '';
            loadLotteries(1);
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

        loadLotteries(1);
    </script>
</body>
</html>
