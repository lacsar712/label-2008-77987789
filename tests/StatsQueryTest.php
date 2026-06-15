<?php

require_once __DIR__ . '/DatabaseTestCase.php';

class StatsQueryTest extends DatabaseTestCase
{
    private $totalNotices = 50;
    private $todayNotices = 15;
    private $totalViews = 0;

    public function run(): void
    {
        $this->setUp();
        $this->seedTestData();

        $this->runTest('count_total_notices', [$this, 'testCountTotalNotices']);
        $this->runTest('count_today_notices', [$this, 'testCountTodayNotices']);
        $this->runTest('sum_total_views', [$this, 'testSumTotalViews']);
        $this->runTest('count_published_notices', [$this, 'testCountPublishedNotices']);
        $this->runTest('count_draft_notices', [$this, 'testCountDraftNotices']);
        $this->runTest('count_by_priority_high', [$this, 'testCountByPriorityHigh']);
        $this->runTest('count_by_priority_medium', [$this, 'testCountByPriorityMedium']);
        $this->runTest('count_by_priority_low', [$this, 'testCountByPriorityLow']);
        $this->runTest('count_by_category', [$this, 'testCountByCategory']);
        $this->runTest('count_by_author', [$this, 'testCountByAuthor']);
        $this->runTest('average_views_per_notice', [$this, 'testAverageViewsPerNotice']);
        $this->runTest('max_views_notice', [$this, 'testMaxViewsNotice']);
        $this->runTest('min_views_notice', [$this, 'testMinViewsNotice']);
        $this->runTest('date_range_count', [$this, 'testDateRangeCount']);
        $this->runTest('group_by_status_count', [$this, 'testGroupByStatusCount']);
        $this->runTest('group_by_priority_count', [$this, 'testGroupByPriorityCount']);
        $this->runTest('empty_table_stats', [$this, 'testEmptyTableStats']);

        $this->tearDown();
    }

    private function seedTestData(): void
    {
        $statuses = ['published', 'draft'];
        $priorities = ['high', 'medium', 'low'];
        $categories = ['系统公告', '运维通知', '产品更新'];
        $authors = ['系统管理员', '技术部', '产品部', '安全部'];

        $todayCount = 0;
        for ($i = 0; $i < $this->totalNotices; $i++) {
            $status = $statuses[$i % 2];
            $priority = $priorities[$i % 3];
            $category = $categories[$i % 3];
            $author = $authors[$i % 4];
            $views = $i * 5;
            $this->totalViews += $views;

            if ($todayCount < $this->todayNotices) {
                $publishDate = date('Y-m-d H:i:s', strtotime("-" . ($i % 8) . " hours"));
                $todayCount++;
            } else {
                $publishDate = date('Y-m-d H:i:s', strtotime("-" . ($i + 1) . " days"));
            }

            $this->insertNotice([
                'title' => "Stats Test Notice #$i",
                'content' => "Content for stats test notice #$i.",
                'author' => $author,
                'category' => $category,
                'status' => $status,
                'priority' => $priority,
                'views' => $views,
                'publish_date' => $publishDate
            ]);
        }
    }

    protected function testCountTotalNotices(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as total FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals($this->totalNotices, (int)$row['total'], 'Total notice count should match');
    }

    protected function testCountTodayNotices(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as today FROM notices WHERE DATE(publish_date) = CURDATE()");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals($this->todayNotices, (int)$row['today'], 'Today notice count should match');
    }

    protected function testSumTotalViews(): void
    {
        $result = $this->conn->query("SELECT COALESCE(SUM(views), 0) as total_views FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals($this->totalViews, (int)$row['total_views'], 'Total views sum should match');
    }

    protected function testCountPublishedNotices(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE status = 'published'");
        $row = $result->fetch_assoc();
        $result->free();
        $expected = (int)ceil($this->totalNotices / 2);
        $this->assertEquals($expected, (int)$row['cnt'], 'Published notice count should be about half');
    }

    protected function testCountDraftNotices(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE status = 'draft'");
        $row = $result->fetch_assoc();
        $result->free();
        $expected = (int)floor($this->totalNotices / 2);
        $this->assertEquals($expected, (int)$row['cnt'], 'Draft notice count should be about half');
    }

    protected function testCountByPriorityHigh(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE priority = 'high'");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'High priority count should be > 0');
        $this->assertLessThan($this->totalNotices, (int)$row['cnt'], 'High priority count should be < total');
    }

    protected function testCountByPriorityMedium(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE priority = 'medium'");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Medium priority count should be > 0');
    }

    protected function testCountByPriorityLow(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE priority = 'low'");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Low priority count should be > 0');
    }

    protected function testCountByCategory(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE category = '系统公告'");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Category count should be > 0');
    }

    protected function testCountByAuthor(): void
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices WHERE author = '技术部'");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Author count should be > 0');
    }

    protected function testAverageViewsPerNotice(): void
    {
        $result = $this->conn->query("SELECT AVG(views) as avg_views FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $expectedAvg = $this->totalViews / $this->totalNotices;
        $this->assertTrue(
            abs((float)$row['avg_views'] - $expectedAvg) < 0.01,
            "Average views should be approximately $expectedAvg"
        );
    }

    protected function testMaxViewsNotice(): void
    {
        $result = $this->conn->query("SELECT MAX(views) as max_views FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $expectedMax = ($this->totalNotices - 1) * 5;
        $this->assertEquals($expectedMax, (int)$row['max_views'], 'Max views should match last record');
    }

    protected function testMinViewsNotice(): void
    {
        $result = $this->conn->query("SELECT MIN(views) as min_views FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals(0, (int)$row['min_views'], 'Min views should be 0 (first record)');
    }

    protected function testDateRangeCount(): void
    {
        $result = $this->conn->query(
            "SELECT COUNT(*) as cnt FROM notices WHERE publish_date BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND NOW()"
        );
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'Date range count should be > 0');
        $this->assertLessThan($this->totalNotices, (int)$row['cnt'], 'Date range count should be < total');
    }

    protected function testGroupByStatusCount(): void
    {
        $result = $this->conn->query("SELECT status, COUNT(*) as cnt FROM notices GROUP BY status");
        $totalFromGroup = 0;
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $totalFromGroup += (int)$row['cnt'];
        }
        $result->free();
        $this->assertEquals($this->totalNotices, $totalFromGroup, 'Group by status sum should equal total');
        $this->assertEquals(2, count($rows), 'Should have 2 status groups');
    }

    protected function testGroupByPriorityCount(): void
    {
        $result = $this->conn->query("SELECT priority, COUNT(*) as cnt FROM notices GROUP BY priority");
        $totalFromGroup = 0;
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
            $totalFromGroup += (int)$row['cnt'];
        }
        $result->free();
        $this->assertEquals($this->totalNotices, $totalFromGroup, 'Group by priority sum should equal total');
        $this->assertEquals(3, count($rows), 'Should have 3 priority groups');
    }

    protected function testEmptyTableStats(): void
    {
        $this->conn->query("TRUNCATE TABLE notices");

        $result = $this->conn->query("SELECT COUNT(*) as total FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals(0, (int)$row['total'], 'Empty table count should be 0');

        $result = $this->conn->query("SELECT COALESCE(SUM(views), 0) as total_views FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals(0, (int)$row['total_views'], 'Empty table total views should be 0');

        $result = $this->conn->query("SELECT COUNT(*) as today FROM notices WHERE DATE(publish_date) = CURDATE()");
        $row = $result->fetch_assoc();
        $result->free();
        $this->assertEquals(0, (int)$row['today'], 'Empty table today count should be 0');
    }
}
