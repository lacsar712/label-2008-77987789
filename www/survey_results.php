<?php header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureSurveyTables();

$selected_survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;

$conn = getConnection();
$stmt = $conn->prepare("SELECT id, title FROM surveys WHERE is_enabled = 1 ORDER BY created_at DESC");
$stmt->execute();
$enabled_surveys = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>问卷结果 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-xl);
            flex-wrap: wrap;
            gap: var(--spacing-md);
        }
        .page-header h2 {
            font-size: 1.75rem;
            color: var(--text-primary);
        }
        .survey-selector {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
        }
        .survey-selector select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 1rem;
            min-width: 250px;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            text-align: center;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        .stat-card-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: var(--spacing-xs);
        }
        .stat-card-label {
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .chart-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
            margin-bottom: var(--spacing-xl);
        }
        .chart-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        .chart-section-header h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
        }
        .chart-container {
            position: relative;
            height: 350px;
            margin-bottom: var(--spacing-lg);
        }
        .chart-container canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .question-chart {
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
        }
        .question-chart:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .question-title {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            font-weight: 500;
        }
        .question-meta {
            display: inline-flex;
            gap: var(--spacing-sm);
            margin-left: var(--spacing-sm);
        }
        .question-type-tag {
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 500;
        }
        .question-type-tag.single {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        .question-type-tag.multiple {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }
        .bar-chart-container {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .bar-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        .bar-label {
            min-width: 150px;
            color: var(--text-secondary);
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        .bar-track {
            flex: 1;
            height: 32px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            overflow: hidden;
            position: relative;
        }
        .bar-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: var(--radius-md);
            transition: width 0.6s ease;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: var(--spacing-sm);
        }
        .bar-text {
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .bar-count {
            min-width: 80px;
            text-align: right;
            color: var(--text-muted);
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        .trend-chart-container {
            height: 300px;
            position: relative;
        }
        .text-answers-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
        }
        .text-answers-header {
            margin-bottom: var(--spacing-lg);
        }
        .text-answers-header h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }
        .text-question-select {
            display: flex;
            gap: var(--spacing-md);
            align-items: center;
            flex-wrap: wrap;
        }
        .text-question-select select {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            min-width: 300px;
        }
        .answer-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        .answer-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            transition: all 0.3s ease;
        }
        .answer-item:hover {
            border-color: var(--primary-color);
        }
        .answer-content {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: var(--spacing-sm);
            word-break: break-word;
        }
        .answer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.8125rem;
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
        .loading {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--bg-tertiary);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto var(--spacing-md);
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-xl);
        }
        .page-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .page-btn:hover:not(:disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .page-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .survey-selector {
                width: 100%;
            }
            .survey-selector select {
                flex: 1;
                min-width: 0;
            }
            .bar-item {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-xs);
            }
            .bar-label {
                min-width: 0;
            }
            .bar-track {
                width: 100%;
            }
            .bar-count {
                text-align: left;
                min-width: 0;
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
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php" class="active">问卷结果</a></li>
                <li><a href="print_template_admin.php">打印模板</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2>问卷结果统计</h2>
                <div class="survey-selector">
                    <label for="surveySelect" style="color: var(--text-secondary); font-size: 0.875rem;">选择问卷：</label>
                    <select id="surveySelect">
                        <option value="">请选择问卷</option>
                        <?php foreach ($enabled_surveys as $survey): ?>
                            <option value="<?php echo $survey['id']; ?>" <?php echo $selected_survey_id == $survey['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($survey['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="statsContent">
                <?php if (!$selected_survey_id): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 17V7M9 17C9 18.6569 7.65685 20 6 20C4.34315 20 3 18.6569 3 17C3 15.3431 4.34315 14 6 14C7.65685 14 9 15.3431 9 17ZM9 7C9 8.65685 7.65685 10 6 10C4.34315 10 3 8.65685 3 7C3 5.34315 4.34315 4 6 4C7.65685 4 9 5.34315 9 7ZM15 17V7M15 17C15 18.6569 13.6569 20 12 20C10.3431 20 9 18.6569 9 17C9 15.3431 10.3431 14 12 14C13.6569 14 15 15.3431 15 17ZM15 7C15 8.65685 13.6569 10 12 10C10.3431 10 9 8.65685 9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7ZM21 17V7M21 17C21 18.6569 19.6569 20 18 20C16.3431 20 15 18.6569 15 17C15 15.3431 16.3431 14 18 14C19.6569 14 21 15.3431 21 17ZM21 7C21 8.65685 19.6569 10 18 10C16.3431 10 15 8.65685 15 7C15 5.34315 16.3431 4 18 4C19.6569 4 21 5.34315 21 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <p>请从上方下拉框选择一个问卷查看统计结果</p>
                    </div>
                <?php endif; ?>
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
        var selectedSurveyId = <?php echo $selected_survey_id; ?>;
        var currentStatsData = null;
        var currentTextQuestionId = null;
        var currentTextPage = 1;

        document.getElementById('surveySelect').addEventListener('change', function(e) {
            var surveyId = e.target.value;
            if (surveyId) {
                window.location.href = 'survey_results.php?survey_id=' + surveyId;
            } else {
                window.location.href = 'survey_results.php';
            }
        });

        if (selectedSurveyId > 0) {
            loadStats(selectedSurveyId);
        }

        function loadStats(surveyId) {
            var container = document.getElementById('statsContent');
            container.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>加载统计数据中...</p></div>';

            fetch('api_survey_stats.php?action=summary&survey_id=' + surveyId)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        currentStatsData = data.data;
                        renderStats(data.data);
                    } else {
                        container.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.58 18.42 1.58 18.94 1.82 19.36C2.06 19.78 2.52 20.03 3 20.03H21C21.48 20.03 21.94 19.78 22.18 19.36C22.42 18.94 22.42 18.42 22.18 18L13.71 3.86C13.47 3.44 13.01 3.19 12.53 3.19C12.05 3.19 11.59 3.44 11.35 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>' + (data.message || '加载失败') + '</p></div>';
                    }
                })
                .catch(function() {
                    container.innerHTML = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.58 18.42 1.58 18.94 1.82 19.36C2.06 19.78 2.52 20.03 3 20.03H21C21.48 20.03 21.94 19.78 22.18 19.36C22.42 18.94 22.42 18.42 22.18 18L13.71 3.86C13.47 3.44 13.01 3.19 12.53 3.19C12.05 3.19 11.59 3.44 11.35 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>网络错误，请稍后重试</p></div>';
                });
        }

        function renderStats(data) {
            var container = document.getElementById('statsContent');
            var html = '';

            html += '<div class="stats-cards">';
            html += '  <div class="stat-card">';
            html += '    <div class="stat-card-number">' + data.total_submissions + '</div>';
            html += '    <div class="stat-card-label">总提交数</div>';
            html += '  </div>';
            html += '  <div class="stat-card">';
            html += '    <div class="stat-card-number">' + data.questions.length + '</div>';
            html += '    <div class="stat-card-label">题目总数</div>';
            html += '  </div>';
            var choiceCount = data.questions.filter(function(q) { return q.question_type !== 'text'; }).length;
            html += '  <div class="stat-card">';
            html += '    <div class="stat-card-number">' + choiceCount + '</div>';
            html += '    <div class="stat-card-label">选择题数</div>';
            html += '  </div>';
            var textCount = data.questions.filter(function(q) { return q.question_type === 'text'; }).length;
            html += '  <div class="stat-card">';
            html += '    <div class="stat-card-number">' + textCount + '</div>';
            html += '    <div class="stat-card-label">简答题数</div>';
            html += '  </div>';
            html += '</div>';

            var choiceQuestions = data.questions.filter(function(q) { return q.question_type !== 'text' && q.stats; });
            if (choiceQuestions.length > 0) {
                html += '<div class="chart-section">';
                html += '  <div class="chart-section-header">';
                html += '    <h3>选项占比统计</h3>';
                html += '  </div>';
                choiceQuestions.forEach(function(q) {
                    var typeText = q.question_type === 'single' ? '单选' : '多选';
                    var typeClass = q.question_type;
                    html += '  <div class="question-chart">';
                    html += '    <div class="question-title">';
                    html += '      ' + htmlEscape(q.question_text);
                    html += '      <span class="question-meta">';
                    html += '        <span class="question-type-tag ' + typeClass + '">' + typeText + '</span>';
                    html += '        <span style="color: var(--text-muted); font-size: 0.75rem;">' + (q.stats ? q.stats.total_responses : 0) + ' 人作答</span>';
                    html += '      </span>';
                    html += '    </div>';
                    html += '    <div class="bar-chart-container">';
                    if (q.stats && q.stats.options) {
                        q.stats.options.forEach(function(opt) {
                            html += '    <div class="bar-item">';
                            html += '      <div class="bar-label">' + htmlEscape(opt.option_text) + '</div>';
                            html += '      <div class="bar-track">';
                            html += '        <div class="bar-fill" style="width: ' + opt.percentage + '%;">';
                            if (opt.percentage >= 15) {
                                html += '          <span class="bar-text">' + opt.percentage + '%</span>';
                            }
                            html += '        </div>';
                            html += '      </div>';
                            if (opt.percentage < 15) {
                                html += '      <div style="min-width: 50px; color: var(--text-muted); font-size: 0.75rem;">' + opt.percentage + '%</div>';
                            }
                            html += '      <div class="bar-count">' + opt.count + ' 人</div>';
                            html += '    </div>';
                        });
                    }
                    html += '    </div>';
                    html += '  </div>';
                });
                html += '</div>';
            }

            if (data.trend && data.trend.length > 0) {
                html += '<div class="chart-section">';
                html += '  <div class="chart-section-header">';
                html += '    <h3>提交数变化趋势</h3>';
                html += '  </div>';
                html += '  <div class="trend-chart-container">';
                html += '    <canvas id="trendChart"></canvas>';
                html += '  </div>';
                html += '</div>';
            }

            var textQuestions = data.questions.filter(function(q) { return q.question_type === 'text'; });
            if (textQuestions.length > 0) {
                html += '<div class="text-answers-section" id="textAnswersSection">';
                html += '  <div class="text-answers-header">';
                html += '    <h3>简答题答案</h3>';
                html += '    <div class="text-question-select">';
                html += '      <label for="textQuestionSelect" style="color: var(--text-secondary); font-size: 0.875rem;">选择问题：</label>';
                html += '      <select id="textQuestionSelect">';
                textQuestions.forEach(function(q) {
                    html += '        <option value="' + q.id + '">' + htmlEscape(q.question_text) + ' (' + (q.stats ? q.stats.total_responses : 0) + ' 条)</option>';
                });
                html += '      </select>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div id="textAnswersList"></div>';
                html += '</div>';
            }

            container.innerHTML = html;

            if (data.trend && data.trend.length > 0) {
                setTimeout(function() { drawTrendChart(data.trend); }, 100);
            }

            if (textQuestions.length > 0) {
                currentTextQuestionId = textQuestions[0].id;
                loadTextAnswers();

                document.getElementById('textQuestionSelect').addEventListener('change', function(e) {
                    currentTextQuestionId = parseInt(e.target.value);
                    currentTextPage = 1;
                    loadTextAnswers();
                });
            }
        }

        function drawTrendChart(trendData) {
            var canvas = document.getElementById('trendChart');
            if (!canvas) return;

            var ctx = canvas.getContext('2d');
            var container = canvas.parentElement;
            canvas.width = container.clientWidth;
            canvas.height = container.clientHeight;

            var padding = { top: 30, right: 30, bottom: 50, left: 50 };
            var chartWidth = canvas.width - padding.left - padding.right;
            var chartHeight = canvas.height - padding.top - padding.bottom;

            var maxCount = Math.max.apply(null, trendData.map(function(d) { return d.count; }));
            maxCount = Math.max(maxCount, 1);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            ctx.strokeStyle = 'var(--border-color)';
            ctx.lineWidth = 1;
            var gridLines = 5;
            for (var i = 0; i <= gridLines; i++) {
                var y = padding.top + (chartHeight / gridLines) * i;
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(canvas.width - padding.right, y);
                ctx.stroke();

                ctx.fillStyle = 'var(--text-muted)';
                ctx.font = '12px "Noto Sans SC"';
                ctx.textAlign = 'right';
                var value = Math.round(maxCount - (maxCount / gridLines) * i);
                ctx.fillText(value, padding.left - 10, y + 4);
            }

            var gradient = ctx.createLinearGradient(0, padding.top, 0, canvas.height - padding.bottom);
            gradient.addColorStop(0, 'rgba(99, 102, 241, 0.3)');
            gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

            ctx.beginPath();
            trendData.forEach(function(d, i) {
                var x = padding.left + (chartWidth / (trendData.length - 1 || 1)) * i;
                var y = padding.top + chartHeight - (d.count / maxCount) * chartHeight;
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.lineTo(canvas.width - padding.right, canvas.height - padding.bottom);
            ctx.lineTo(padding.left, canvas.height - padding.bottom);
            ctx.closePath();
            ctx.fillStyle = gradient;
            ctx.fill();

            ctx.beginPath();
            ctx.strokeStyle = 'var(--primary-color)';
            ctx.lineWidth = 3;
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            trendData.forEach(function(d, i) {
                var x = padding.left + (chartWidth / (trendData.length - 1 || 1)) * i;
                var y = padding.top + chartHeight - (d.count / maxCount) * chartHeight;
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            trendData.forEach(function(d, i) {
                var x = padding.left + (chartWidth / (trendData.length - 1 || 1)) * i;
                var y = padding.top + chartHeight - (d.count / maxCount) * chartHeight;

                ctx.beginPath();
                ctx.arc(x, y, 5, 0, Math.PI * 2);
                ctx.fillStyle = 'var(--bg-secondary)';
                ctx.fill();
                ctx.strokeStyle = 'var(--primary-color)';
                ctx.lineWidth = 2;
                ctx.stroke();

                ctx.fillStyle = 'var(--text-secondary)';
                ctx.font = '11px "Noto Sans SC"';
                ctx.textAlign = 'center';
                ctx.fillText(d.date.substring(5), x, canvas.height - padding.bottom + 20);
            });
        }

        function loadTextAnswers() {
            var container = document.getElementById('textAnswersList');
            if (!container) return;

            container.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>加载答案中...</p></div>';

            fetch('api_survey_stats.php?action=text_answers&survey_id=' + selectedSurveyId + '&question_id=' + currentTextQuestionId + '&page=' + currentTextPage + '&per_page=10')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderTextAnswers(data.data);
                    } else {
                        container.innerHTML = '<div class="empty-state"><p>' + (data.message || '加载失败') + '</p></div>';
                    }
                })
                .catch(function() {
                    container.innerHTML = '<div class="empty-state"><p>网络错误，请稍后重试</p></div>';
                });
        }

        function renderTextAnswers(data) {
            var container = document.getElementById('textAnswersList');
            if (!container) return;

            var html = '';

            if (!data.list || data.list.length === 0) {
                html = '<div class="empty-state"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无答案</p></div>';
                container.innerHTML = html;
                return;
            }

            html += '<div class="answer-list">';
            data.list.forEach(function(answer) {
                html += '<div class="answer-item">';
                html += '  <div class="answer-content">' + htmlEscape(answer.answer_text) + '</div>';
                html += '  <div class="answer-meta">';
                html += '    <span>提交时间：' + answer.created_at + '</span>';
                html += '  </div>';
                html += '</div>';
            });
            html += '</div>';

            if (data.pagination.total_pages > 1) {
                html += '<div class="pagination">';
                html += '<button class="page-btn" onclick="goToPage(' + (currentTextPage - 1) + ')" ' + (currentTextPage <= 1 ? 'disabled' : '') + '>上一页</button>';
                var start = Math.max(1, currentTextPage - 2);
                var end = Math.min(data.pagination.total_pages, currentTextPage + 2);
                if (start > 1) {
                    html += '<button class="page-btn" onclick="goToPage(1)">1</button>';
                    if (start > 2) html += '<span style="color: var(--text-muted); padding: 0 var(--spacing-sm);">...</span>';
                }
                for (var i = start; i <= end; i++) {
                    html += '<button class="page-btn ' + (i === currentTextPage ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</button>';
                }
                if (end < data.pagination.total_pages) {
                    if (end < data.pagination.total_pages - 1) html += '<span style="color: var(--text-muted); padding: 0 var(--spacing-sm);">...</span>';
                    html += '<button class="page-btn" onclick="goToPage(' + data.pagination.total_pages + ')">' + data.pagination.total_pages + '</button>';
                }
                html += '<button class="page-btn" onclick="goToPage(' + (currentTextPage + 1) + ')" ' + (currentTextPage >= data.pagination.total_pages ? 'disabled' : '') + '>下一页</button>';
                html += '</div>';
            }

            container.innerHTML = html;
        }

        window.goToPage = function(page) {
            currentTextPage = page;
            loadTextAnswers();
        };

        function htmlEscape(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.addEventListener('resize', function() {
            if (currentStatsData && currentStatsData.trend && currentStatsData.trend.length > 0) {
                drawTrendChart(currentStatsData.trend);
            }
        });
    })();
    </script>
</body>
</html>
