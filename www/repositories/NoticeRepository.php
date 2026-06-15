<?php

require_once __DIR__ . '/../core/QueryBuilder.php';

class NoticeSearchCriteria
{
    public ?string $title = null;
    public ?string $author = null;
    public ?string $priority = null;
    public ?string $status = null;
    public ?string $category = null;
    public int $page = 1;
    public int $perPage = 8;
    public string $sortBy = 'publish_date';
    public string $sortDir = 'DESC';

    public static function fromGlobals(): self
    {
        $criteria = new self();
        $criteria->title = isset($_GET['search_title']) ? sanitize($_GET['search_title']) : null;
        $criteria->author = isset($_GET['search_author']) ? sanitize($_GET['search_author']) : null;
        $criteria->priority = isset($_GET['search_priority']) && !empty($_GET['search_priority']) ? sanitize($_GET['search_priority']) : null;
        $criteria->status = isset($_GET['search_status']) && !empty($_GET['search_status']) ? sanitize($_GET['search_status']) : null;
        $criteria->category = isset($_GET['search_category']) && !empty($_GET['search_category']) ? sanitize($_GET['search_category']) : null;
        $criteria->page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $criteria->perPage = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 8;
        $criteria->sortBy = isset($_GET['sort_by']) ? sanitize($_GET['sort_by']) : 'publish_date';
        $criteria->sortDir = isset($_GET['sort_dir']) && strtoupper($_GET['sort_dir']) === 'ASC' ? 'ASC' : 'DESC';
        return $criteria;
    }

    public function toQueryString(int $overridePage = null): string
    {
        $params = [];
        if ($overridePage !== null) {
            $params['page'] = $overridePage;
        } elseif ($this->page > 1) {
            $params['page'] = $this->page;
        }
        if ($this->title !== null && $this->title !== '') {
            $params['search_title'] = $this->title;
        }
        if ($this->author !== null && $this->author !== '') {
            $params['search_author'] = $this->author;
        }
        if ($this->priority !== null) {
            $params['search_priority'] = $this->priority;
        }
        if ($this->status !== null) {
            $params['search_status'] = $this->status;
        }
        if ($this->category !== null) {
            $params['search_category'] = $this->category;
        }
        if ($this->perPage !== 8) {
            $params['per_page'] = $this->perPage;
        }
        return http_build_query($params);
    }
}

class PaginatedResult
{
    public array $items;
    public int $total;
    public int $page;
    public int $perPage;
    public int $totalPages;

    public function __construct(array $items, int $total, int $page, int $perPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = (int) ceil($total / $perPage);
    }

    public function hasPrevPage(): bool
    {
        return $this->page > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages;
    }

    public function getStartOffset(): int
    {
        return ($this->page - 1) * $this->perPage + 1;
    }

    public function getEndOffset(): int
    {
        return min($this->page * $this->perPage, $this->total);
    }
}

class NoticeRepository
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->conn, 'notices');
    }

    private function applySearchCriteria(QueryBuilder $qb, NoticeSearchCriteria $criteria): void
    {
        if ($criteria->title !== null && $criteria->title !== '') {
            $qb->whereLike('title', $criteria->title);
        }
        if ($criteria->author !== null && $criteria->author !== '') {
            $qb->whereLike('author', $criteria->author);
        }
        if ($criteria->priority !== null) {
            $qb->where('priority', '=', $criteria->priority, 's');
        }
        if ($criteria->status !== null) {
            $qb->where('status', '=', $criteria->status, 's');
        }
        if ($criteria->category !== null) {
            $qb->whereLike('category', $criteria->category);
        }
    }

    public function search(NoticeSearchCriteria $criteria): PaginatedResult
    {
        $qb = $this->createQueryBuilder();
        $this->applySearchCriteria($qb, $criteria);

        $total = $qb->count();

        $qb2 = $this->createQueryBuilder();
        $this->applySearchCriteria($qb2, $criteria);
        $qb2->orderBy($criteria->sortBy, $criteria->sortDir)
            ->paginate($criteria->page, $criteria->perPage);

        $items = $qb2->fetchAll();

        return new PaginatedResult($items, $total, $criteria->page, $criteria->perPage);
    }

    public function findLatestPublished(int $limit = 6): array
    {
        $qb = $this->createQueryBuilder();
        $qb->where('status', '=', 'published', 's')
            ->orderBy('publish_date', 'DESC')
            ->limit($limit);
        return $qb->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $qb = $this->createQueryBuilder();
        $qb->where('id', '=', $id, 'i')
            ->limit(1);
        return $qb->fetch();
    }

    public function deleteById(int $id): bool
    {
        $qb = $this->createQueryBuilder();
        $qb->where('id', '=', $id, 'i');
        return $qb->delete();
    }

    public function getStats(): array
    {
        $totalQb = $this->createQueryBuilder();
        $total = $totalQb->count();

        $todayQb = $this->createQueryBuilder();
        $today = $this->conn->query("SELECT COUNT(*) as today FROM notices WHERE DATE(publish_date) = CURDATE()")
            ->fetch_assoc()['today'];

        $viewsResult = $this->conn->query("SELECT COALESCE(SUM(views), 0) as total_views FROM notices");
        $totalViews = (int) $viewsResult->fetch_assoc()['total_views'];

        return [
            'total' => $total,
            'today' => (int) $today,
            'total_views' => $totalViews,
        ];
    }

    public function ensureCompositeIndexes(): void
    {
        $check = $this->conn->query("SHOW INDEX FROM notices WHERE Key_name = 'idx_status_publish_date'");
        if ($check && $check->num_rows === 0) {
            $this->conn->query("CREATE INDEX idx_status_publish_date ON notices (status, publish_date)");
        }

        $check2 = $this->conn->query("SHOW INDEX FROM notices WHERE Key_name = 'idx_priority_publish_date'");
        if ($check2 && $check2->num_rows === 0) {
            $this->conn->query("CREATE INDEX idx_priority_publish_date ON notices (priority, publish_date)");
        }

        $check3 = $this->conn->query("SHOW INDEX FROM notices WHERE Key_name = 'idx_author_publish_date'");
        if ($check3 && $check3->num_rows === 0) {
            $this->conn->query("CREATE INDEX idx_author_publish_date ON notices (author, publish_date)");
        }
    }
}
