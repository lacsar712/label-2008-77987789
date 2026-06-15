<?php

require_once __DIR__ . '/DatabaseTestCase.php';

class FuzzySearchPerformanceTest extends DatabaseTestCase
{
    private $recordCount;
    private $timeLimitSeconds;
    private $isPerformanceTest;

    public function run(): void
    {
        $this->recordCount = (int)(getenv('PERF_TEST_RECORDS') ?: 100000);
        $this->timeLimitSeconds = (float)(getenv('PERF_TIME_LIMIT') ?: 5.0);
        $this->isPerformanceTest = getenv('RUN_PERF_TESTS') === '1' || $this->recordCount <= 10000;

        if (!$this->isPerformanceTest) {
            echo "\n=== FuzzySearchPerformanceTest ===\n";
            echo "SKIPPED: Set RUN_PERF_TESTS=1 to enable performance tests\n";
            return;
        }

        $this->setUp();
        echo "\nSeeding {$this->recordCount} test records...\n";
        $seedStart = microtime(true);
        $this->seedTestData();
        $seedTime = microtime(true) - $seedStart;
        echo "Seeding completed in " . round($seedTime, 2) . " seconds\n";

        $this->runTest('record_count_verification', [$this, 'testRecordCountVerification']);
        $this->runTest('fuzzy_search_title_performance', [$this, 'testFuzzySearchTitlePerformance']);
        $this->runTest('fuzzy_search_content_performance', [$this, 'testFuzzySearchContentPerformance']);
        $this->runTest('fuzzy_search_author_performance', [$this, 'testFuzzySearchAuthorPerformance']);
        $this->runTest('fuzzy_search_category_performance', [$this, 'testFuzzySearchCategoryPerformance']);
        $this->runTest('combined_search_performance', [$this, 'testCombinedSearchPerformance']);
        $this->runTest('search_with_pagination_performance', [$this, 'testSearchWithPaginationPerformance']);
        $this->runTest('search_result_count_accuracy', [$this, 'testSearchResultCountAccuracy']);
        $this->runTest('empty_search_performance', [$this, 'testEmptySearchPerformance']);
        $this->runTest('no_match_search_performance', [$this, 'testNoMatchSearchPerformance']);
        $this->runTest('search_order_by_performance', [$this, 'testSearchOrderByPerformance']);

        $this->tearDown();
    }

    private function seedTestData(): void
    {
        $statuses = ['published', 'draft'];
        $priorities = ['high', 'medium', 'low'];
        $categories = ['系统公告', '运维通知', '产品更新', '安全通知', '人事通知', '培训通知', '调查问卷'];
        $authors = ['系统管理员', '技术部', '产品部', '安全部', '人事部', '客服部', '培训部', '市场部', '财务部'];
        $titleKeywords = ['通知', '公告', '更新', '提醒', '安排', '上线', '维护', '安全', '培训', '调查'];
        $contentWords = ['系统', '用户', '功能', '服务', '数据', '安全', '更新', '维护', '优化', '通知',
            '公告', '提醒', '安排', '上线', '测试', '反馈', '问题', '解决', '改进', '升级'];

        $batchSize = 1000;
        $totalBatches = (int)ceil($this->recordCount / $batchSize);

        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $values = [];
            $batchCount = min($batchSize, $this->recordCount - $batch * $batchSize);

            for ($i = 0; $i < $batchCount; $i++) {
                $id = $batch * $batchSize + $i;
                $titleKeyword = $titleKeywords[$id % count($titleKeywords)];
                $category = $categories[$id % count($categories)];
                $author = $authors[$id % count($authors)];
                $status = $statuses[$id % count($statuses)];
                $priority = $priorities[$id % count($priorities)];
                $daysAgo = $id % 365;

                $title = "{$titleKeyword}#{$id} - 测试公告标题 {$author}";
                $contentWordsShuffled = array_slice($contentWords, 0, 10);
                shuffle($contentWordsShuffled);
                $content = "这是第{$id}号测试公告的内容。" . implode('、', $contentWordsShuffled) .
                    "。请各位用户留意相关安排。分类：{$category}，优先级：{$priority}。";

                $values[] = "(" .
                    "'" . $this->conn->real_escape_string($title) . "', " .
                    "'" . $this->conn->real_escape_string($content) . "', " .
                    "'" . $this->conn->real_escape_string($author) . "', " .
                    "'" . $this->conn->real_escape_string($category) . "', " .
                    "'" . date('Y-m-d H:i:s', strtotime("-$daysAgo days")) . "', " .
                    "'$status', " .
                    "'$priority', " .
                    ($id % 100) .
                    ")";
            }

            $sql = "INSERT INTO notices (title, content, author, category, publish_date, status, priority, views) VALUES " . implode(', ', $values);
            $this->conn->query($sql);

            if ($batch % 10 === 0) {
                echo "  Seeded " . ($batch * $batchSize + $batchCount) . " / {$this->recordCount} records\n";
            }
        }
    }

    protected function testRecordCountVerification(): void
    {
        $count = $this->getNoticeCount();
        $this->assertEquals($this->recordCount, $count, "Should have {$this->recordCount} records");
    }

    protected function testFuzzySearchTitlePerformance(): void
    {
        $keyword = '通知';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE title LIKE '%{$keyword}%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $count, 'Title search should return results');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Title fuzzy search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Title search: " . round($elapsed * 1000, 2) . "ms, $count results\n";
    }

    protected function testFuzzySearchContentPerformance(): void
    {
        $keyword = '安全';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE content LIKE '%{$keyword}%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $count, 'Content search should return results');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Content fuzzy search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Content search: " . round($elapsed * 1000, 2) . "ms, $count results\n";
    }

    protected function testFuzzySearchAuthorPerformance(): void
    {
        $keyword = '技术部';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE author LIKE '%{$keyword}%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $count, 'Author search should return results');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Author fuzzy search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Author search: " . round($elapsed * 1000, 2) . "ms, $count results\n";
    }

    protected function testFuzzySearchCategoryPerformance(): void
    {
        $keyword = '系统';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE category LIKE '%{$keyword}%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertGreaterThan(0, $count, 'Category search should return results');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Category fuzzy search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Category search: " . round($elapsed * 1000, 2) . "ms, $count results\n";
    }

    protected function testCombinedSearchPerformance(): void
    {
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices 
             WHERE title LIKE '%通知%' 
               AND author LIKE '%技术%'
               AND status = 'published'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertLessThan(
            $this->timeLimitSeconds * 1.5,
            $elapsed,
            "Combined search should complete within " . ($this->timeLimitSeconds * 1.5) . "s, took " . round($elapsed, 3) . "s"
        );

        echo "    Combined search: " . round($elapsed * 1000, 2) . "ms, $count results\n";
    }

    protected function testSearchWithPaginationPerformance(): void
    {
        $keyword = '公告';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE * FROM notices 
             WHERE title LIKE '%{$keyword}%' 
             ORDER BY publish_date DESC 
             LIMIT 10 OFFSET 0"
        );
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertCount(10, $items, 'Pagination search should return 10 items');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Search with pagination should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Search + pagination: " . round($elapsed * 1000, 2) . "ms\n";
    }

    protected function testSearchResultCountAccuracy(): void
    {
        $keyword = '技术部';

        $result = $this->conn->query(
            "SELECT COUNT(*) as cnt FROM notices WHERE author = '技术部'"
        );
        $row = $result->fetch_assoc();
        $exactCount = (int)$row['cnt'];
        $result->free();

        $result = $this->conn->query(
            "SELECT COUNT(*) as cnt FROM notices WHERE author LIKE '%技术部%'"
        );
        $row = $result->fetch_assoc();
        $fuzzyCount = (int)$row['cnt'];
        $result->free();

        $this->assertEquals($exactCount, $fuzzyCount, 'Fuzzy search for exact match should return same count');
    }

    protected function testEmptySearchPerformance(): void
    {
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE title LIKE '%%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertEquals($this->recordCount, $count, 'Empty search should return all records');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "Empty search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    Empty search: " . round($elapsed * 1000, 2) . "ms\n";
    }

    protected function testNoMatchSearchPerformance(): void
    {
        $keyword = '不存在的关键词xyz123';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE COUNT(*) as cnt FROM notices WHERE title LIKE '%{$keyword}%'"
        );
        $row = $result->fetch_assoc();
        $count = (int)$row['cnt'];
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertEquals(0, $count, 'No-match search should return 0 results');
        $this->assertLessThan(
            $this->timeLimitSeconds,
            $elapsed,
            "No-match search should complete within {$this->timeLimitSeconds}s, took " . round($elapsed, 3) . "s"
        );

        echo "    No-match search: " . round($elapsed * 1000, 2) . "ms\n";
    }

    protected function testSearchOrderByPerformance(): void
    {
        $keyword = '通知';
        $startTime = microtime(true);

        $result = $this->conn->query(
            "SELECT SQL_NO_CACHE * FROM notices 
             WHERE title LIKE '%{$keyword}%' 
             ORDER BY publish_date DESC 
             LIMIT 20"
        );
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();

        $elapsed = microtime(true) - $startTime;

        $this->assertGreaterThan(0, count($items), 'Search with order by should return results');
        $this->assertLessThan(
            $this->timeLimitSeconds * 1.2,
            $elapsed,
            "Search with ORDER BY should complete within " . ($this->timeLimitSeconds * 1.2) . "s, took " . round($elapsed, 3) . "s"
        );

        for ($i = 0; $i < count($items) - 1; $i++) {
            $this->assertTrue(
                $items[$i]['publish_date'] >= $items[$i + 1]['publish_date'],
                'Results should be ordered by publish_date DESC'
            );
        }

        echo "    Search + ORDER BY: " . round($elapsed * 1000, 2) . "ms, " . count($items) . " results\n";
    }
}
