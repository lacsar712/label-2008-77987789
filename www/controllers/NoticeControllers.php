<?php

require_once __DIR__ . '/../services/NoticeService.php';

abstract class BaseController
{
    protected mysqli $conn;
    protected NoticeService $noticeService;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->noticeService = new NoticeService($conn);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

class SearchNoticeController extends BaseController
{
    public function handle(): array
    {
        $data = [
            'success_message' => null,
            'error_message' => null,
            'criteria' => NoticeSearchCriteria::fromGlobals(),
            'result' => null,
        ];

        if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $deleteResult = $this->noticeService->deleteById(intval($_GET['delete']));
            if ($deleteResult['success']) {
                $data['success_message'] = $deleteResult['message'];
            } else {
                $data['error_message'] = $deleteResult['message'];
            }
        }

        try {
            $data['result'] = $this->noticeService->searchNotices($data['criteria']);
        } catch (Exception $e) {
            $data['error_message'] = '查询失败：' . $e->getMessage();
            $data['result'] = new PaginatedResult([], 0, $data['criteria']->page, $data['criteria']->perPage);
        }

        return $data;
    }
}

class HomeController extends BaseController
{
    public function handle(): array
    {
        $criteria = new NoticeSearchCriteria();
        $criteria->status = 'published';
        $criteria->perPage = 6;
        $criteria->page = 1;
        $criteria->sortBy = 'publish_date';
        $criteria->sortDir = 'DESC';

        $latestNotices = $this->noticeService->searchNotices($criteria);

        return [
            'stats' => $this->noticeService->getDashboardStats(),
            'latest_notices' => $latestNotices->items,
        ];
    }
}
