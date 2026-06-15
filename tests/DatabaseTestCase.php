<?php

require_once __DIR__ . '/TestCase.php';

abstract class DatabaseTestCase extends TestCase
{
    protected $conn;
    protected $testDbName;

    protected function setUp(): void
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $this->testDbName = getenv('TEST_DB_NAME') ?: 'notice_test_db';

        $this->conn = new mysqli($host, $user, $pass);
        if ($this->conn->connect_error) {
            throw new RuntimeException("Database connection failed: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
        $this->createTestDatabase();
        $this->createNoticesTable();
    }

    protected function tearDown(): void
    {
        if ($this->conn) {
            $this->conn->query("DROP DATABASE IF EXISTS `{$this->testDbName}`");
            $this->conn->close();
        }
    }

    protected function createTestDatabase(): void
    {
        $this->conn->query("DROP DATABASE IF EXISTS `{$this->testDbName}`");
        $this->conn->query("CREATE DATABASE `{$this->testDbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $this->conn->select_db($this->testDbName);
    }

    protected function createNoticesTable(): void
    {
        $sql = "CREATE TABLE notices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL COMMENT '公告标题',
            content TEXT NOT NULL COMMENT '公告内容',
            author VARCHAR(100) NOT NULL COMMENT '发布人',
            category VARCHAR(100) DEFAULT '' COMMENT '公告分类',
            publish_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
            update_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
            status ENUM('published', 'draft') DEFAULT 'published' COMMENT '状态',
            survey_id INT DEFAULT NULL COMMENT '关联问卷ID',
            priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
            views INT DEFAULT 0 COMMENT '浏览次数',
            INDEX idx_publish_date (publish_date),
            INDEX idx_status (status),
            INDEX idx_category (category),
            INDEX idx_survey_id (survey_id),
            INDEX idx_status_publish_date (status, publish_date),
            INDEX idx_priority_publish_date (priority, publish_date),
            INDEX idx_author_publish_date (author, publish_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }

    protected function insertNotice(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $types = '';
        $values = [];

        foreach ($data as $key => $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }

        $sql = "INSERT INTO notices (" . implode(', ', $columns) . ") VALUES ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    protected function getTableSchema(): array
    {
        $result = $this->conn->query("DESCRIBE notices");
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }
        $result->free();
        return $columns;
    }

    protected function getTableIndexes(): array
    {
        $result = $this->conn->query("SHOW INDEX FROM notices");
        $indexes = [];
        while ($row = $result->fetch_assoc()) {
            $keyName = $row['Key_name'];
            if (!isset($indexes[$keyName])) {
                $indexes[$keyName] = [
                    'unique' => $row['Non_unique'] == 0,
                    'columns' => []
                ];
            }
            $indexes[$keyName]['columns'][] = $row['Column_name'];
        }
        $result->free();
        return $indexes;
    }

    protected function getTableStatus(): array
    {
        $result = $this->conn->query("SHOW TABLE STATUS LIKE 'notices'");
        $status = $result->fetch_assoc();
        $result->free();
        return $status;
    }

    protected function explainQuery(string $sql): array
    {
        $result = $this->conn->query("EXPLAIN $sql");
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
        return $rows;
    }

    protected function getNoticeCount(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) as cnt FROM notices");
        $row = $result->fetch_assoc();
        $result->free();
        return (int)$row['cnt'];
    }
}
