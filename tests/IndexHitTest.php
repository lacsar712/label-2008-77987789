<?php

require_once __DIR__ . '/DatabaseTestCase.php';

class IndexHitTest extends DatabaseTestCase
{
    public function run(): void
    {
        $this->setUp();
        $this->seedTestData();

        $this->runTest('index_publish_date_exists', [$this, 'testIndexPublishDateExists']);
        $this->runTest('index_status_exists', [$this, 'testIndexStatusExists']);
        $this->runTest('index_category_exists', [$this, 'testIndexCategoryExists']);
        $this->runTest('index_survey_id_exists', [$this, 'testIndexSurveyIdExists']);
        $this->runTest('composite_index_status_publish_date_exists', [$this, 'testCompositeIndexStatusPublishDateExists']);
        $this->runTest('composite_index_priority_publish_date_exists', [$this, 'testCompositeIndexPriorityPublishDateExists']);
        $this->runTest('composite_index_author_publish_date_exists', [$this, 'testCompositeIndexAuthorPublishDateExists']);
        $this->runTest('primary_key_index', [$this, 'testPrimaryKeyIndex']);
        $this->runTest('explain_publish_date_where_uses_index', [$this, 'testExplainPublishDateWhereUsesIndex']);
        $this->runTest('explain_status_where_uses_index', [$this, 'testExplainStatusWhereUsesIndex']);
        $this->runTest('explain_publish_date_order_by_uses_index', [$this, 'testExplainPublishDateOrderByUsesIndex']);
        $this->runTest('explain_composite_status_publish_date', [$this, 'testExplainCompositeStatusPublishDate']);
        $this->runTest('explain_composite_priority_publish_date', [$this, 'testExplainCompositePriorityPublishDate']);
        $this->runTest('explain_composite_author_publish_date', [$this, 'testExplainCompositeAuthorPublishDate']);
        $this->runTest('explain_category_where_uses_index', [$this, 'testExplainCategoryWhereUsesIndex']);
        $this->runTest('explain_id_where_uses_primary', [$this, 'testExplainIdWhereUsesPrimary']);

        $this->tearDown();
    }

    private function seedTestData(): void
    {
        $statuses = ['published', 'draft'];
        $priorities = ['high', 'medium', 'low'];
        $categories = ['系统公告', '运维通知', '产品更新', '安全通知', '人事通知'];
        $authors = ['系统管理员', '技术部', '产品部', '安全部', '人事部', '客服部', '培训部'];

        for ($i = 0; $i < 100; $i++) {
            $status = $statuses[$i % 2];
            $priority = $priorities[$i % 3];
            $category = $categories[$i % 5];
            $author = $authors[$i % 7];
            $daysAgo = $i;

            $this->insertNotice([
                'title' => "Test Notice #$i",
                'content' => "Content for notice #$i. This is a test notice with some content.",
                'author' => $author,
                'category' => $category,
                'status' => $status,
                'priority' => $priority,
                'views' => $i * 10,
                'publish_date' => date('Y-m-d H:i:s', strtotime("-$daysAgo days"))
            ]);
        }
    }

    protected function testIndexPublishDateExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_publish_date']), 'idx_publish_date index should exist');
        $this->assertEquals(['publish_date'], $indexes['idx_publish_date']['columns'], 'idx_publish_date should have publish_date column');
    }

    protected function testIndexStatusExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_status']), 'idx_status index should exist');
        $this->assertEquals(['status'], $indexes['idx_status']['columns'], 'idx_status should have status column');
    }

    protected function testIndexCategoryExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_category']), 'idx_category index should exist');
        $this->assertEquals(['category'], $indexes['idx_category']['columns'], 'idx_category should have category column');
    }

    protected function testIndexSurveyIdExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_survey_id']), 'idx_survey_id index should exist');
        $this->assertEquals(['survey_id'], $indexes['idx_survey_id']['columns'], 'idx_survey_id should have survey_id column');
    }

    protected function testCompositeIndexStatusPublishDateExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_status_publish_date']), 'idx_status_publish_date composite index should exist');
        $this->assertEquals(['status', 'publish_date'], $indexes['idx_status_publish_date']['columns'], 'idx_status_publish_date should have status, publish_date columns');
    }

    protected function testCompositeIndexPriorityPublishDateExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_priority_publish_date']), 'idx_priority_publish_date composite index should exist');
        $this->assertEquals(['priority', 'publish_date'], $indexes['idx_priority_publish_date']['columns'], 'idx_priority_publish_date should have priority, publish_date columns');
    }

    protected function testCompositeIndexAuthorPublishDateExists(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['idx_author_publish_date']), 'idx_author_publish_date composite index should exist');
        $this->assertEquals(['author', 'publish_date'], $indexes['idx_author_publish_date']['columns'], 'idx_author_publish_date should have author, publish_date columns');
    }

    protected function testPrimaryKeyIndex(): void
    {
        $indexes = $this->getTableIndexes();
        $this->assertTrue(isset($indexes['PRIMARY']), 'PRIMARY index should exist');
        $this->assertTrue($indexes['PRIMARY']['unique'], 'PRIMARY index should be unique');
        $this->assertEquals(['id'], $indexes['PRIMARY']['columns'], 'PRIMARY index should have id column');
    }

    protected function testExplainPublishDateWhereUsesIndex(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE publish_date >= '2024-01-01 00:00:00'"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $key = $explain[0]['key'] ?? '';
        $this->assertContains('idx_publish_date', $possibleKeys, 'idx_publish_date should be in possible_keys');
        $this->assertNotEmpty($key, 'Query should use some index');
    }

    protected function testExplainStatusWhereUsesIndex(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE status = 'published'"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $this->assertContains('idx_status', $possibleKeys, 'idx_status should be in possible_keys');
        $key = $explain[0]['key'] ?? '';
        $this->assertNotEmpty($key, 'Query with status filter should use an index');
    }

    protected function testExplainPublishDateOrderByUsesIndex(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices ORDER BY publish_date DESC LIMIT 10"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $extra = $explain[0]['Extra'] ?? '';
        $key = $explain[0]['key'] ?? '';
        $this->assertTrue(
            $key === 'idx_publish_date' || strpos($extra, 'Using index') !== false || strpos($extra, 'filesort') === false,
            "ORDER BY publish_date should benefit from index or avoid filesort. Key: $key, Extra: $extra"
        );
    }

    protected function testExplainCompositeStatusPublishDate(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE status = 'published' ORDER BY publish_date DESC LIMIT 10"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $this->assertContains('idx_status_publish_date', $possibleKeys, 'idx_status_publish_date should be in possible_keys');
    }

    protected function testExplainCompositePriorityPublishDate(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE priority = 'high' ORDER BY publish_date DESC LIMIT 10"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $this->assertContains('idx_priority_publish_date', $possibleKeys, 'idx_priority_publish_date should be in possible_keys');
    }

    protected function testExplainCompositeAuthorPublishDate(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE author = '技术部' ORDER BY publish_date DESC LIMIT 10"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $this->assertContains('idx_author_publish_date', $possibleKeys, 'idx_author_publish_date should be in possible_keys');
    }

    protected function testExplainCategoryWhereUsesIndex(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE category = '系统公告'"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $possibleKeys = $explain[0]['possible_keys'] ?? '';
        $this->assertContains('idx_category', $possibleKeys, 'idx_category should be in possible_keys');
    }

    protected function testExplainIdWhereUsesPrimary(): void
    {
        $explain = $this->explainQuery(
            "SELECT * FROM notices WHERE id = 50"
        );
        $this->assertNotEmpty($explain, 'EXPLAIN should return result');
        $key = $explain[0]['key'] ?? '';
        $this->assertEquals('PRIMARY', $key, 'Query by id should use PRIMARY index');
    }
}
