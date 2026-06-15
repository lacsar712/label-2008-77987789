<?php

class PaginationComponent
{
    private PaginatedResult $result;
    private NoticeSearchCriteria $criteria;
    private string $baseUrl;

    public function __construct(PaginatedResult $result, NoticeSearchCriteria $criteria, string $baseUrl = '')
    {
        $this->result = $result;
        $this->criteria = $criteria;
        $this->baseUrl = $baseUrl;
    }

    public function renderInfo(): void
    {
        if ($this->result->total === 0) {
            echo '<div class="results-info"><p>共找到 <strong>0</strong> 条公告</p></div>';
            return;
        }
        ?>
        <div class="results-info">
            <p>共找到 <strong><?php echo $this->result->total; ?></strong> 条公告，
                当前第 <strong><?php echo $this->result->page; ?></strong> /
                <strong><?php echo max(1, $this->result->totalPages); ?></strong> 页
                （显示 <?php echo $this->result->getStartOffset(); ?> -
                <?php echo $this->result->getEndOffset(); ?> 条）
            </p>
        </div>
        <?php
    }

    public function render(): void
    {
        if ($this->result->totalPages <= 1) {
            return;
        }

        $prevIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $nextIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        ?>
        <div class="pagination">
            <?php if ($this->result->hasPrevPage()): ?>
                <a href="<?php echo $this->buildUrl($this->result->page - 1); ?>" class="page-link">
                    <?php echo $prevIcon; ?>
                    上一页
                </a>
            <?php endif; ?>

            <?php
            $startPage = max(1, $this->result->page - 2);
            $endPage = min($this->result->totalPages, $this->result->page + 2);

            if ($startPage > 1): ?>
                <a href="<?php echo $this->buildUrl(1); ?>" class="page-number">1</a>
                <?php if ($startPage > 2): ?>
                    <span class="page-ellipsis">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="<?php echo $this->buildUrl($i); ?>"
                   class="page-number <?php echo $i === $this->result->page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($endPage < $this->result->totalPages): ?>
                <?php if ($endPage < $this->result->totalPages - 1): ?>
                    <span class="page-ellipsis">...</span>
                <?php endif; ?>
                <a href="<?php echo $this->buildUrl($this->result->totalPages); ?>" class="page-number">
                    <?php echo $this->result->totalPages; ?>
                </a>
            <?php endif; ?>

            <?php if ($this->result->hasNextPage()): ?>
                <a href="<?php echo $this->buildUrl($this->result->page + 1); ?>" class="page-link">
                    下一页
                    <?php echo $nextIcon; ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    private function buildUrl(int $page): string
    {
        $query = $this->criteria->toQueryString($page);
        $url = $this->baseUrl . '?';
        if ($query !== '') {
            $url .= $query;
        } else {
            $url .= 'page=' . $page;
        }
        return $url;
    }
}

class NoticeTableRowComponent
{
    private NoticeService $service;
    private NoticeSearchCriteria $criteria;

    public function __construct(NoticeService $service, NoticeSearchCriteria $criteria)
    {
        $this->service = $service;
        $this->criteria = $criteria;
    }

    public function renderRow(array $notice): void
    {
        $editIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 4H4C3.46957 4 2.96086 4.21071 2.58579 4.58579C2.21071 4.96086 2 5.46957 2 6V20C2 20.5304 2.21071 21.0391 2.58579 21.4142C2.96086 21.7893 3.46957 22 4 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V13M18.5 2.5C18.8978 2.1022 19.4374 1.87868 20 1.87868C20.5626 1.87868 21.1022 2.1022 21.5 2.5C21.8978 2.8978 22.1213 3.43739 22.1213 4C22.1213 4.56261 21.8978 5.1022 21.5 5.5L12 15L8 16L9 12L18.5 2.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $deleteIcon = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 7L18.1327 19.1425C18.0579 20.1891 17.187 21 16.1378 21H7.86224C6.81296 21 5.94208 20.1891 5.86732 19.1425L5 7M10 11V17M14 11V17M15 7V4C15 3.44772 14.5523 3 14 3H10C9.44772 3 9 3.44772 9 4V7M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $deleteQuery = $this->criteria->toQueryString();
        $deleteUrl = '?delete=' . $notice['id'];
        if ($deleteQuery !== '') {
            $deleteUrl .= '&' . $deleteQuery;
        }
        ?>
        <tr>
            <td><?php echo $notice['id']; ?></td>
            <td class="notice-title-cell">
                <a href="notice_detail.php?id=<?php echo $notice['id']; ?>"
                   style="color:var(--text-primary);font-weight:500;text-decoration:none;transition:color 0.2s;"
                   onmouseover="this.style.color='var(--primary-color)'"
                   onmouseout="this.style.color='var(--text-primary)'">
                    <?php echo htmlspecialchars($notice['title']); ?>
                </a>
            </td>
            <td class="notice-content-cell">
                <?php echo $this->service->formatContentExcerpt($notice['content'], 60); ?>
            </td>
            <td><?php echo htmlspecialchars($notice['author']); ?></td>
            <td>
                <span class="<?php echo $this->service->getPriorityBadgeClass($notice['priority']); ?>">
                    <?php echo $this->service->getPriorityText($notice['priority']); ?>
                </span>
            </td>
            <td><?php echo $this->service->formatDate($notice['publish_date']); ?></td>
            <td class="action-buttons">
                <a href="add_notice.php?id=<?php echo $notice['id']; ?>"
                   class="btn-icon-action edit" title="编辑">
                    <?php echo $editIcon; ?>
                </a>
                <a href="<?php echo htmlspecialchars($deleteUrl); ?>"
                   class="btn-icon-action delete"
                   title="删除"
                   onclick="return confirm('确定要删除这条公告吗？');">
                    <?php echo $deleteIcon; ?>
                </a>
            </td>
        </tr>
        <?php
    }

    public function renderTable(array $notices): void
    {
        if (empty($notices)) {
            $this->renderEmpty();
            return;
        }
        ?>
        <div class="notices-table-container">
            <table class="notices-table">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="25%">标题</th>
                        <th width="30%">内容摘要</th>
                        <th width="10%">发布人</th>
                        <th width="8%">优先级</th>
                        <th width="12%">发布时间</th>
                        <th width="10%">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notices as $notice): ?>
                        <?php $this->renderRow($notice); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function renderEmpty(): void
    {
        $emptyIcon = '<svg class="no-results-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '<div class="no-results">' . $emptyIcon . '<p>没有找到符合条件的公告</p></div>';
    }
}

class NoticeCardComponent
{
    private NoticeService $service;

    public function __construct(NoticeService $service)
    {
        $this->service = $service;
    }

    public function render(array $notice): void
    {
        $authorIcon = '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        $viewsIcon = '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        ?>
        <div class="notice-card"
             onclick="window.location.href='notice_detail.php?id=<?php echo $notice['id']; ?>'"
             style="cursor:pointer;">
            <div class="notice-header">
                <span class="<?php echo $this->service->getPriorityBadgeClass($notice['priority']); ?>">
                    <?php echo $this->service->getPriorityText($notice['priority']); ?>
                </span>
                <span class="notice-date">
                    <?php echo $this->service->formatDate($notice['publish_date'], 'Y-m-d'); ?>
                </span>
            </div>
            <h4 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h4>
            <p class="notice-excerpt">
                <?php echo $this->service->formatContentExcerpt($notice['content'], 80); ?>
            </p>
            <div class="notice-footer">
                <span class="notice-author">
                    <?php echo $authorIcon; ?>
                    <?php echo htmlspecialchars($notice['author']); ?>
                </span>
                <span class="notice-views">
                    <?php echo $viewsIcon; ?>
                    <?php echo $notice['views'] ?? 0; ?>
                </span>
            </div>
        </div>
        <?php
    }

    public function renderGrid(array $notices): void
    {
        if (empty($notices)) {
            echo '<p class="no-data">暂无公告信息</p>';
            return;
        }
        foreach ($notices as $notice) {
            $this->render($notice);
        }
    }
}
