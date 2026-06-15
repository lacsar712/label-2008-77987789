<?php

require_once __DIR__ . '/DatabaseTestCase.php';

class SchemaConstraintTest extends DatabaseTestCase
{
    private $schema;

    public function run(): void
    {
        $this->setUp();
        $this->schema = $this->getTableSchema();

        $this->runTest('table_exists', [$this, 'testTableExists']);
        $this->runTest('not_null_title', [$this, 'testNotNullTitle']);
        $this->runTest('not_null_content', [$this, 'testNotNullContent']);
        $this->runTest('not_null_author', [$this, 'testNotNullAuthor']);
        $this->runTest('enum_status_values', [$this, 'testEnumStatusValues']);
        $this->runTest('enum_priority_values', [$this, 'testEnumPriorityValues']);
        $this->runTest('default_category', [$this, 'testDefaultCategory']);
        $this->runTest('default_status', [$this, 'testDefaultStatus']);
        $this->runTest('default_priority', [$this, 'testDefaultPriority']);
        $this->runTest('default_views', [$this, 'testDefaultViews']);
        $this->runTest('default_publish_date', [$this, 'testDefaultPublishDate']);
        $this->runTest('default_survey_id', [$this, 'testDefaultSurveyId']);
        $this->runTest('charset_utf8mb4', [$this, 'testCharsetUtf8mb4']);
        $this->runTest('collation_unicode_ci', [$this, 'testCollationUnicodeCi']);
        $this->runTest('engine_innodb', [$this, 'testEngineInnodb']);
        $this->runTest('insert_not_null_title_should_fail', [$this, 'testInsertNotNullTitleShouldFail']);
        $this->runTest('insert_not_null_content_should_fail', [$this, 'testInsertNotNullContentShouldFail']);
        $this->runTest('insert_not_null_author_should_fail', [$this, 'testInsertNotNullAuthorShouldFail']);
        $this->runTest('insert_invalid_enum_status_should_fail', [$this, 'testInsertInvalidEnumStatusShouldFail']);
        $this->runTest('insert_invalid_enum_priority_should_fail', [$this, 'testInsertInvalidEnumPriorityShouldFail']);
        $this->runTest('update_date_auto_update', [$this, 'testUpdateDateAutoUpdate']);

        $this->tearDown();
    }

    protected function testTableExists(): void
    {
        $result = $this->conn->query("SHOW TABLES LIKE 'notices'");
        $this->assertEquals(1, $result->num_rows, 'notices table should exist');
        $result->free();
    }

    protected function testNotNullTitle(): void
    {
        $this->assertEquals('NO', $this->schema['title']['Null'], 'title should be NOT NULL');
    }

    protected function testNotNullContent(): void
    {
        $this->assertEquals('NO', $this->schema['content']['Null'], 'content should be NOT NULL');
    }

    protected function testNotNullAuthor(): void
    {
        $this->assertEquals('NO', $this->schema['author']['Null'], 'author should be NOT NULL');
    }

    protected function testEnumStatusValues(): void
    {
        $this->assertContains('enum', strtolower($this->schema['status']['Type']), 'status should be ENUM type');
        $this->assertContains('published', $this->schema['status']['Type'], 'status should contain published');
        $this->assertContains('draft', $this->schema['status']['Type'], 'status should contain draft');
    }

    protected function testEnumPriorityValues(): void
    {
        $this->assertContains('enum', strtolower($this->schema['priority']['Type']), 'priority should be ENUM type');
        $this->assertContains('high', $this->schema['priority']['Type'], 'priority should contain high');
        $this->assertContains('medium', $this->schema['priority']['Type'], 'priority should contain medium');
        $this->assertContains('low', $this->schema['priority']['Type'], 'priority should contain low');
    }

    protected function testDefaultCategory(): void
    {
        $this->assertEquals('', $this->schema['category']['Default'], 'category default should be empty string');
    }

    protected function testDefaultStatus(): void
    {
        $this->assertEquals('published', $this->schema['status']['Default'], 'status default should be published');
    }

    protected function testDefaultPriority(): void
    {
        $this->assertEquals('medium', $this->schema['priority']['Default'], 'priority default should be medium');
    }

    protected function testDefaultViews(): void
    {
        $this->assertEquals('0', $this->schema['views']['Default'], 'views default should be 0');
    }

    protected function testDefaultPublishDate(): void
    {
        $this->assertContains('CURRENT_TIMESTAMP', $this->schema['publish_date']['Default'], 'publish_date default should be CURRENT_TIMESTAMP');
    }

    protected function testDefaultSurveyId(): void
    {
        $this->assertNull($this->schema['survey_id']['Default'], 'survey_id default should be NULL');
        $this->assertEquals('YES', $this->schema['survey_id']['Null'], 'survey_id should allow NULL');
    }

    protected function testCharsetUtf8mb4(): void
    {
        $status = $this->getTableStatus();
        $this->assertContains('utf8mb4', $status['Collation'], 'table charset should be utf8mb4');
    }

    protected function testCollationUnicodeCi(): void
    {
        $status = $this->getTableStatus();
        $this->assertContains('utf8mb4_unicode_ci', $status['Collation'], 'table collation should be utf8mb4_unicode_ci');
    }

    protected function testEngineInnodb(): void
    {
        $status = $this->getTableStatus();
        $this->assertEquals('InnoDB', $status['Engine'], 'table engine should be InnoDB');
    }

    protected function testInsertNotNullTitleShouldFail(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("INSERT INTO notices (content, author) VALUES ('test content', 'test author')");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Insert without title should fail due to NOT NULL constraint');
    }

    protected function testInsertNotNullContentShouldFail(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("INSERT INTO notices (title, author) VALUES ('test title', 'test author')");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Insert without content should fail due to NOT NULL constraint');
    }

    protected function testInsertNotNullAuthorShouldFail(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("INSERT INTO notices (title, content) VALUES ('test title', 'test content')");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Insert without author should fail due to NOT NULL constraint');
    }

    protected function testInsertInvalidEnumStatusShouldFail(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("INSERT INTO notices (title, content, author, status) VALUES ('test', 'content', 'author', 'invalid_status')");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Insert with invalid status enum should fail');
    }

    protected function testInsertInvalidEnumPriorityShouldFail(): void
    {
        $prevErrorReporting = error_reporting(0);
        $result = @$this->conn->query("INSERT INTO notices (title, content, author, priority) VALUES ('test', 'content', 'author', 'invalid_priority')");
        error_reporting($prevErrorReporting);
        $this->assertFalse($result, 'Insert with invalid priority enum should fail');
    }

    protected function testUpdateDateAutoUpdate(): void
    {
        $id = $this->insertNotice([
            'title' => 'Test Update',
            'content' => 'Initial content',
            'author' => 'tester'
        ]);

        $result = $this->conn->query("SELECT update_date FROM notices WHERE id = $id");
        $row = $result->fetch_assoc();
        $initialUpdateDate = $row['update_date'];
        $result->free();

        sleep(1);

        $this->conn->query("UPDATE notices SET title = 'Updated Title' WHERE id = $id");

        $result = $this->conn->query("SELECT update_date FROM notices WHERE id = $id");
        $row = $result->fetch_assoc();
        $updatedUpdateDate = $row['update_date'];
        $result->free();

        $this->assertNotEquals($initialUpdateDate, $updatedUpdateDate, 'update_date should auto-update on record update');
    }
}
