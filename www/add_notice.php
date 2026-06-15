<?php header('Content-Type: text/html; charset=UTF-8'); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? '编辑公告' : '添加公告'; ?> - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php
    require_once 'config.php';
    ensureSurveyTables();
    
    $edit_mode = false;
    $notice = null;
    
    if (isset($_GET['id'])) {
        $edit_mode = true;
        $id = intval($_GET['id']);
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notice = $result->fetch_assoc();
        $stmt->close();
        closeConnection($conn);
        
        if (!$notice) {
            header("Location: search_notice.php");
            exit();
        }
    }
    
    $conn = getConnection();
    $enabled_surveys = [];
    $result = $conn->query("SELECT id, title FROM surveys WHERE is_enabled = 1 AND (start_time IS NULL OR start_time <= NOW()) AND (end_time IS NULL OR end_time >= NOW()) ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $enabled_surveys[] = [
            'id' => intval($row['id']),
            'title' => htmlspecialchars($row['title'])
        ];
    }
    closeConnection($conn);
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $author = sanitize($_POST['author']);
        $category = sanitize($_POST['category'] ?? '');
        $priority = sanitize($_POST['priority']);
        $status = sanitize($_POST['status']);
        $survey_id = isset($_POST['survey_id']) && !empty($_POST['survey_id']) ? intval($_POST['survey_id']) : null;
        
        $conn = getConnection();
        $noticeId = 0;
        $wasPublishedBefore = false;
        $triggerPush = false;

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = intval($_POST['id']);
            $noticeId = $id;
            $chk = $conn->prepare("SELECT status FROM notices WHERE id = ?");
            $chk->bind_param("i", $id);
            $chk->execute();
            $old = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($old) {
                $wasPublishedBefore = $old['status'] === 'published';
            }

            $stmt = $conn->prepare("UPDATE notices SET title=?, content=?, author=?, category=?, priority=?, status=?, survey_id=? WHERE id=?");
            $stmt->bind_param("ssssssii", $title, $content, $author, $category, $priority, $status, $survey_id, $id);
            
            if ($stmt->execute()) {
                $success_message = "公告更新成功！";
                if (!$wasPublishedBefore && $status === 'published') {
                    $triggerPush = true;
                }
            } else {
                $error_message = "更新失败: " . $conn->error;
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO notices (title, content, author, category, priority, status, survey_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $title, $content, $author, $category, $priority, $status, $survey_id);
            
            if ($stmt->execute()) {
                $noticeId = $conn->insert_id;
                $success_message = "公告添加成功！";
                if ($status === 'published') {
                    $triggerPush = true;
                }
            } else {
                $error_message = "添加失败: " . $conn->error;
            }
            $stmt->close();
        }
        
        closeConnection($conn);

        if ($triggerPush && $noticeId > 0) {
            ensureSubscriptionTables();
            $pushCount = generatePushRecordsForNotice($noticeId);
            if ($pushCount > 0) {
                $success_message .= " 已为 $pushCount 个订阅生成推送记录。";
            }
        }
    }
    ?>
    
    <!-- 导航栏 -->
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
                <li><a href="add_notice.php" class="active">添加公告</a></li>
                <li><a href="search_notice.php">查询公告</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="rating_admin.php">评价管理</a></li>
                <li><a href="rating_summary.php">评价汇总</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="lottery_list.php">抽奖活动</a></li>
                <li><a href="lottery_admin.php">抽奖管理</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- 主要内容 -->
        <div class="main-content">
            <div class="form-container">
                <div class="form-header">
                    <h2><?php echo $edit_mode ? '编辑公告' : '添加公告'; ?></h2>
                    <p><?php echo $edit_mode ? '修改公告信息' : '发布新的公告信息'; ?></p>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $success_message; ?>
                    <a href="search_notice.php" class="alert-link">查看所有公告</a>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="notice-form">
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">公告标题 <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo $edit_mode ? htmlspecialchars($notice['title']) : ''; ?>"
                               placeholder="请输入公告标题">
                    </div>

                    <div class="form-group">
                        <label for="content">公告内容 <span class="required">*</span></label>
                        <textarea id="content" name="content" rows="10" required 
                                  placeholder="请输入公告内容"><?php echo $edit_mode ? htmlspecialchars($notice['content']) : ''; ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">发布人 <span class="required">*</span></label>
                            <input type="text" id="author" name="author" required 
                                   value="<?php echo $edit_mode ? htmlspecialchars($notice['author']) : ''; ?>"
                                   placeholder="请输入发布人姓名">
                        </div>

                        <div class="form-group">
                            <label for="category">分类</label>
                            <input type="text" id="category" name="category"
                                   value="<?php echo $edit_mode ? htmlspecialchars($notice['category'] ?? '') : ''; ?>"
                                   placeholder="如：系统公告、运维通知等">
                        </div>

                        <div class="form-group">
                            <label for="priority">优先级 <span class="required">*</span></label>
                            <select id="priority" name="priority" required>
                                <option value="low" <?php echo ($edit_mode && $notice['priority'] == 'low') ? 'selected' : ''; ?>>低</option>
                                <option value="medium" <?php echo ($edit_mode && $notice['priority'] == 'medium') ? 'selected' : ''; ?> <?php echo !$edit_mode ? 'selected' : ''; ?>>中</option>
                                <option value="high" <?php echo ($edit_mode && $notice['priority'] == 'high') ? 'selected' : ''; ?>>高</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">状态 <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="published" <?php echo ($edit_mode && $notice['status'] == 'published') ? 'selected' : ''; ?> <?php echo !$edit_mode ? 'selected' : ''; ?>>已发布</option>
                                <option value="draft" <?php echo ($edit_mode && $notice['status'] == 'draft') ? 'selected' : ''; ?>>草稿</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="survey_id">关联问卷</label>
                            <select id="survey_id" name="survey_id">
                                <option value="">不关联问卷</option>
                                <?php foreach ($enabled_surveys as $survey): ?>
                                    <option value="<?php echo $survey['id']; ?>" <?php echo ($edit_mode && isset($notice['survey_id']) && $notice['survey_id'] == $survey['id']) ? 'selected' : ''; ?>>
                                        <?php echo $survey['title']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo $edit_mode ? '更新公告' : '发布公告'; ?>
                        </button>
                        <a href="search_notice.php" class="btn btn-secondary">
                            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 19L3 12M3 12L10 5M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            返回列表
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
