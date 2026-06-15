<?php

class NavbarComponent
{
    private string $activePage;
    private array $menuItems;

    public function __construct(string $activePage)
    {
        $this->activePage = $activePage;
        $this->menuItems = [
            ['index.php', '首页'],
            ['add_notice.php', '添加公告'],
            ['search_notice.php', '查询公告'],
            ['notice_calendar.php', '公告日历'],
            ['qa_center.php', '问答中心'],
            ['chat.php', '在线答疑'],
            ['feedback.php', '意见反馈'],
            ['feedback_query.php', '工单查询'],
            ['feedback_admin.php', '反馈管理'],
            ['survey_admin.php', '问卷管理'],
            ['survey_results.php', '问卷结果'],
            ['rating_admin.php', '评价管理'],
            ['rating_summary.php', '评价汇总'],
            ['subscription_admin.php', '订阅管理'],
            ['push_records.php', '推送记录'],
            ['lottery_list.php', '抽奖活动'],
            ['lottery_admin.php', '抽奖管理'],
            ['system_backup.php', '系统备份'],
        ];
    }

    public function render(): void
    {
        $logoSvg = '<svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        ?>
        <nav class="navbar">
            <div class="container">
                <div class="nav-brand">
                    <?php echo $logoSvg; ?>
                    <h1>公告信息管理系统</h1>
                </div>
                <ul class="nav-menu">
                    <?php foreach ($this->menuItems as [$url, $label]): ?>
                        <li><a href="<?php echo htmlspecialchars($url); ?>"
                               class="<?php echo $this->isActive($url) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($label); ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
        <?php
    }

    public function isActive(string $url): bool
    {
        return strpos($this->activePage, $url) !== false;
    }
}

class FooterComponent
{
    public function render(): void
    {
        ?>
        <footer class="footer">
            <div class="container">
                <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
            </div>
        </footer>
        <?php
    }
}

class AlertComponent
{
    public static function renderSuccess(?string $message): void
    {
        if ($message === null) return;
        $icon = '<svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '<div class="alert alert-success">' . $icon . htmlspecialchars($message) . '</div>';
    }

    public static function renderError(?string $message): void
    {
        if ($message === null) return;
        $icon = '<svg class="alert-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12M12 16H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '<div class="alert alert-error">' . $icon . htmlspecialchars($message) . '</div>';
    }
}

class SearchFormComponent
{
    private NoticeSearchCriteria $criteria;
    private string $action;

    public function __construct(NoticeSearchCriteria $criteria, string $action = '')
    {
        $this->criteria = $criteria;
        $this->action = $action;
    }

    public function render(): void
    {
        $searchIcon = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $resetIcon = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4V9H4.58152M19.9381 11C19.446 7.05369 16.0796 4 12 4C8.64262 4 5.76829 6.06817 4.58152 9M4.58152 9H9M20 20V15H19.4185M19.4185 15C18.2317 17.9318 15.3574 20 12 20C7.92038 20 4.55399 16.9463 4.06189 13M19.4185 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        ?>
        <div class="search-container">
            <h2>查询公告</h2>
            <form method="GET" action="<?php echo htmlspecialchars($this->action); ?>" class="search-form">
                <div class="search-fields">
                    <div class="search-field">
                        <label for="search_title">标题</label>
                        <input type="text" id="search_title" name="search_title"
                               value="<?php echo htmlspecialchars($this->criteria->title ?? ''); ?>"
                               placeholder="搜索标题...">
                    </div>
                    <div class="search-field">
                        <label for="search_author">发布人</label>
                        <input type="text" id="search_author" name="search_author"
                               value="<?php echo htmlspecialchars($this->criteria->author ?? ''); ?>"
                               placeholder="搜索发布人...">
                    </div>
                    <div class="search-field">
                        <label for="search_priority">优先级</label>
                        <select id="search_priority" name="search_priority">
                            <option value="">全部</option>
                            <?php foreach (['high' => '高', 'medium' => '中', 'low' => '低'] as $val => $label): ?>
                                <option value="<?php echo $val; ?>"
                                    <?php echo ($this->criteria->priority ?? '') === $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $searchIcon; ?>
                        搜索
                    </button>
                    <a href="search_notice.php" class="btn btn-secondary">
                        <?php echo $resetIcon; ?>
                        重置
                    </a>
                </div>
            </form>
        </div>
        <?php
    }
}
