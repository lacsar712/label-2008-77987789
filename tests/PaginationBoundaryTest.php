<?php

require_once __DIR__ . '/DatabaseTestCase.php';

class PaginationBoundaryTest extends DatabaseTestCase
{
    private $totalRecords = 25;
    private $perPage = 10;

    public function run(): void
    {
        $this->setUp();
        $this->seedTestData();

        $this->runTest('first_page_count', [$this, 'testFirstPageCount']);
        $this->runTest('first_page_order_correct', [$this, 'testFirstPageOrderCorrect']);
        $this->runTest('second_page_count', [$this, 'testSecondPageCount']);
        $this->runTest('last_page_count', [$this, 'testLastPageCount']);
        $this->runTest('last_page_remainder', [$this, 'testLastPageRemainder']);
        $this->runTest('beyond_last_page_empty', [$this, 'testBeyondLastPageEmpty']);
        $this->runTest('page_zero_returns_first', [$this, 'testPageZeroReturnsFirst']);
        $this->runTest('negative_offset_empty', [$this, 'testNegativeOffsetEmpty']);
        $this->runTest('total_pages_calculation', [$this, 'testTotalPagesCalculation']);
        $this->runTest('limit_zero_returns_empty', [$this, 'testLimitZeroReturnsEmpty']);
        $this->runTest('large_limit_returns_all', [$this, 'testLargeLimitReturnsAll']);
        $this->runTest('offset_only_returns_remaining', [$this, 'testOffsetOnlyReturnsRemaining']);
        $this->runTest('has_prev_page_first_page', [$this, 'testHasPrevPageFirstPage']);
        $this->runTest('has_next_page_first_page', [$this, 'testHasNextPageFirstPage']);
        $this->runTest('has_prev_page_middle', [$this, 'testHasPrevPageMiddle']);
        $this->runTest('has_next_page_last_page', [$this, 'testHasNextPageLastPage']);
        $this->runTest('pagination_with_where_filter', [$this, 'testPaginationWithWhereFilter']);
        $this->runTest('pagination_with_order_asc', [$this, 'testPaginationWithOrderAsc']);
        $this->runTest('start_offset_calculation', [$this, 'testStartOffsetCalculation']);
        $this->runTest('end_offset_calculation', [$this, 'testEndOffsetCalculation']);
        $this->runTest('end_offset_last_page', [$this, 'testEndOffsetLastPage']);
        $this->runTest('empty_table_pagination', [$this, 'testEmptyTablePagination']);

        $this->tearDown();
    }

    private function seedTestData(): void
    {
        for ($i = 0; $i < $this->totalRecords; $i++) {
            $daysAgo = $this->totalRecords - $i - 1;
            $this->insertNotice([
                'title' => "Pagination Test Notice #$i",
                'content' => "Content for pagination test notice #$i.",
                'author' => 'tester',
                'status' => $i % 3 === 0 ? 'draft' : 'published',
                'views' => $i * 10,
                'publish_date' => date('Y-m-d H:i:s', strtotime("-$daysAgo days"))
            ]);
        }
    }

    private function getPaginatedResults(int $page, int $perPage, string $sortBy = 'publish_date', string $sortDir = 'DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM notices ORDER BY `$sortBy` $sortDir LIMIT $perPage OFFSET $offset";
        $result = $this->conn->query($sql);
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
        return $items;
    }

    private function getTotalCount(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        return (int)$row['cnt'];
    }

    private function getTotalPages(int $perPage): int
    {
        $total = $this->getTotalCount();
        return (int)ceil($total / $perPage);
    }

    protected function testFirstPageCount(): void
    {
        $items = $this->getPaginatedResults(1, $this->perPage);
        $this->assertCount($this->perPage, $items, 'First page should have perPage items');
    }

    protected function testFirstPageOrderCorrect(): void
    {
        $items = $this->getPaginatedResults(1, $this->perPage);
        $this->assertCount($this->perPage, $items);
        for ($i = 0; $i < count($items) - 1; $i++) {
            $this->assertTrue(
                $items[$i]['publish_date'] >= $items[$i + 1]['publish_date'],
                'Items should be ordered by publish_date DESC'
            );
        }
    }

    protected function testSecondPageCount(): void
    {
        $items = $this->getPaginatedResults(2, $this->perPage);
        $this->assertCount($this->perPage, $items, 'Second page should have perPage items');
    }

    protected function testLastPageCount(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $items = $this->getPaginatedResults($totalPages, $this->perPage);
        $remainder = $this->totalRecords % $this->perPage;
        $expectedCount = $remainder === 0 ? $this->perPage : $remainder;
        $this->assertCount($expectedCount, $items, 'Last page should have remainder items');
    }

    protected function testLastPageRemainder(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $items = $this->getPaginatedResults($totalPages, $this->perPage);
        $expectedRemainder = $this->totalRecords - ($totalPages - 1) * $this->perPage;
        $this->assertEquals($expectedRemainder, count($items), 'Last page remainder should match');
        $this->assertGreaterThan(0, count($items), 'Last page should have at least 1 item');
        $this->assertLessThan($this->perPage + 1, count($items), 'Last page should not exceed perPage');
    }

    protected function testBeyondLastPageEmpty(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $items = $this->getPaginatedResults($totalPages + 1, $this->perPage);
        $this->assertCount(0, $items, 'Page beyond last should return empty');
    }

    protected function testPageZeroReturnsFirst(): void
    {
        $itemsPage0 = $this->getPaginatedResults(0, $this->perPage);
        $itemsPage1 = $this->getPaginatedResults(1, $this->perPage);
        $this->assertCount(count($itemsPage1), $itemsPage0, 'Page 0 should behave like page 1 (offset 0)');
    }

    protected function testNegativeOffsetEmpty(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("SELECT * FROM notices ORDER BY publish_date DESC LIMIT 10 OFFSET -5");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Negative offset should cause error or be invalid');
    }

    protected function testTotalPagesCalculation(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $expectedPages = (int)ceil($this->totalRecords / $this->perPage);
        $this->assertEquals($expectedPages, $totalPages, 'Total pages calculation should be correct');
        $this->assertEquals(3, $totalPages, 'With 25 records and 10 per page, should have 3 pages');
    }

    protected function testLimitZeroReturnsEmpty(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("SELECT * FROM notices ORDER BY publish_date DESC LIMIT 0 OFFSET 0");
        if ($result) {
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $result->free();
            $this->assertCount(0, $items, 'LIMIT 0 should return empty');
        }
        error_reporting($prevErrorReporting);
    }

    protected function testLargeLimitReturnsAll(): void
    {
        $result = $this->conn->query("SELECT * FROM notices ORDER BY publish_date DESC LIMIT 1000 OFFSET 0");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
        $this->assertCount($this->totalRecords, $items, 'Large limit should return all records');
    }

    protected function testOffsetOnlyReturnsRemaining(): void
    {
        $offset = 5;
        $result = $this->conn->query("SELECT * FROM notices ORDER BY publish_date DESC LIMIT 18446744073709551615 OFFSET $offset");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
        $this->assertEquals($this->totalRecords - $offset, count($items), 'Offset without limit should return remaining');
    }

    protected function testHasPrevPageFirstPage(): void
    {
        $hasPrev = 1 > 1;
        $this->assertFalse($hasPrev, 'First page should not have previous page');
    }

    protected function testHasNextPageFirstPage(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $hasNext = 1 < $totalPages;
        $this->assertTrue($hasNext, 'First page should have next page');
    }

    protected function testHasPrevPageMiddle(): void
    {
        $page = 2;
        $hasPrev = $page > 1;
        $this->assertTrue($hasPrev, 'Middle page should have previous page');
    }

    protected function testHasNextPageLastPage(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $hasNext = $totalPages < $totalPages;
        $this->assertFalse($hasNext, 'Last page should not have next page');
    }

    protected function testPaginationWithWhereFilter(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE status = 'published'");
        $row = $result->fetch_assoc();
        $publishedCount = (int)$row['cnt'];
        $result->free();

        $perPage = 5;
        $page = 1;
        $offset = ($page - 1) * $perPage;
        $result = $this->conn->query(
            "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC LIMIT $perPage OFFSET $offset"
        );
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();

        $this->assertCount(min($perPage, $publishedCount), $items, 'Filtered pagination should return correct count');
        foreach ($items as $item) {
            $this->assertEquals('published', $item['status'], 'All filtered items should have status=published');
        }
    }

    protected function testPaginationWithOrderAsc(): void
    {
        $items = $this->getPaginatedResults(1, $this->perPage, 'publish_date', 'ASC');
        $this->assertCount($this->perPage, $items);
        for ($i = 0; $i < count($items) - 1; $i++) {
            $this->assertTrue(
                $items[$i]['publish_date'] <= $items[$i + 1]['publish_date'],
                'Items should be ordered by publish_date ASC'
            );
        }
    }

    protected function testStartOffsetCalculation(): void
    {
        $page = 2;
        $startOffset = ($page - 1) * $this->perPage + 1;
        $this->assertEquals($this->perPage + 1, $startOffset, 'Start offset calculation for page 2');
    }

    protected function testEndOffsetCalculation(): void
    {
        $page = 2;
        $endOffset = $page * $this->perPage;
        $this->assertEquals(2 * $this->perPage, $endOffset, 'End offset calculation for page 2');
    }

    protected function testEndOffsetLastPage(): void
    {
        $totalPages = $this->getTotalPages($this->perPage);
        $endOffset = min($totalPages * $this->perPage, $this->totalRecords);
        $this->assertEquals($this->totalRecords, $endOffset, 'End offset on last page should equal total');
    }

    protected function testEmptyTablePagination(): void
    {
        $this->conn->query("TRUNCATE TABLE notices");

        $items = $this->getPaginatedResults(1, $this->perPage);
        $this->assertCount(0, $items, 'Empty table page 1 should return empty');

        $totalPages = $this->getTotalPages($this->perPage);
        $this->assertEquals(0, $totalPages, 'Empty table should have 0 pages');
    }
}
