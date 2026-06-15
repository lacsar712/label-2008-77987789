<?php

require_once __DIR__ . '/../repositories/NoticeRepository.php';

class NoticeService
{
    private NoticeRepository $repository;
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->repository = new NoticeRepository($conn);
    }

    public function getRepository(): NoticeRepository
    {
        return $this->repository;
    }

    public function searchNotices(NoticeSearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->search($criteria);
    }

    public function getLatestPublished(int $limit = 6): array
    {
        return $this->repository->findLatestPublished($limit);
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function deleteById(int $id): array
    {
        if ($notice = $this->repository->findById($id)) {
            $success = $this->repository->deleteById($id);
            return [
                'success' => $success,
                'message' => $success ? '公告删除成功！' : '删除失败：数据库错误',
                'notice' => $notice,
            ];
        }
        return [
            'success' => false,
            'message' => '删除失败：公告不存在',
            'notice' => null,
        ];
    }

    public function getDashboardStats(): array
    {
        return $this->repository->getStats();
    }

    public function ensureIndexes(): void
    {
        $this->repository->ensureCompositeIndexes();
    }

    public function formatContentExcerpt(?string $content, int $length = 80): string
    {
        if ($content === null || $content === '') {
            return '';
        }
        $cleaned = htmlspecialchars($content);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        if (mb_strlen($cleaned, 'UTF-8') <= $length) {
            return $cleaned;
        }
        return mb_substr($cleaned, 0, $length, 'UTF-8') . '...';
    }

    public function formatDate(?string $date, string $format = 'Y-m-d H:i'): string
    {
        if ($date === null || $date === '') {
            return '-';
        }
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }
        return date($format, $timestamp);
    }

    public function getPriorityText(string $priority): string
    {
        return match ($priority) {
            'high' => '高',
            'medium' => '中',
            'low' => '低',
            default => $priority,
        };
    }

    public function getPriorityBadgeClass(string $priority): string
    {
        return 'priority-badge priority-' . $priority;
    }

    public function getAuthorAvatarUrl(string $author): string
    {
        $hash = md5(strtolower(trim($author)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=32";
    }
}
