<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    die("This script must be run from command line.\n");
}

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/DatabaseTestCase.php';
require_once __DIR__ . '/SchemaConstraintTest.php';
require_once __DIR__ . '/IndexHitTest.php';
require_once __DIR__ . '/StatsQueryTest.php';
require_once __DIR__ . '/PaginationBoundaryTest.php';
require_once __DIR__ . '/FuzzySearchPerformanceTest.php';

function printUsage(): void
{
    echo "Usage: php run_tests.php [options]\n\n";
    echo "Options:\n";
    echo "  --filter=<pattern>   Run only tests matching the pattern\n";
    echo "  --list               List all available test classes\n";
    echo "  --help               Show this help message\n\n";
    echo "Environment Variables:\n";
    echo "  DB_HOST              Database host (default: 127.0.0.1)\n";
    echo "  DB_USER              Database user (default: root)\n";
    echo "  DB_PASS              Database password (default: '')\n";
    echo "  TEST_DB_NAME         Test database name (default: notice_test_db)\n";
    echo "  RUN_PERF_TESTS       Set to 1 to enable performance tests\n";
    echo "  PERF_TEST_RECORDS    Number of records for perf tests (default: 100000)\n";
    echo "  PERF_TIME_LIMIT      Time limit in seconds for perf tests (default: 5.0)\n";
}

function listTests(): void
{
    $testClasses = [
        'SchemaConstraintTest' => '字段约束测试（NOT NULL、ENUM、默认值、字符集）',
        'IndexHitTest' => '索引命中测试（EXPLAIN 验证 publish_date、status 索引）',
        'StatsQueryTest' => '统计 SQL 正确性测试（总数、今日数、总浏览量）',
        'PaginationBoundaryTest' => '分页 LIMIT/OFFSET 边界测试（首页/末页/越界）',
        'FuzzySearchPerformanceTest' => '模糊搜索性能基线测试（10万条数据下耗时上限）',
    ];

    echo "Available Test Classes:\n\n";
    foreach ($testClasses as $name => $description) {
        echo "  $name\n";
        echo "    $description\n\n";
    }
}

$options = getopt('', ['filter:', 'list', 'help']);

if (isset($options['help'])) {
    printUsage();
    exit(0);
}

if (isset($options['list'])) {
    listTests();
    exit(0);
}

$filter = $options['filter'] ?? null;

$allTestClasses = [
    'SchemaConstraintTest',
    'IndexHitTest',
    'StatsQueryTest',
    'PaginationBoundaryTest',
    'FuzzySearchPerformanceTest',
];

$testClasses = $allTestClasses;
if ($filter !== null) {
    $testClasses = array_filter($allTestClasses, function ($class) use ($filter) {
        return stripos($class, $filter) !== false;
    });
}

if (empty($testClasses)) {
    echo "No test classes matched the filter: $filter\n";
    exit(1);
}

echo "========================================\n";
echo "  Notices Table Data Layer Tests\n";
echo "========================================\n";
echo "Database: " . (getenv('DB_HOST') ?: '127.0.0.1') . "\n";
echo "Test DB: " . (getenv('TEST_DB_NAME') ?: 'notice_test_db') . "\n";
echo "Tests: " . count($testClasses) . " class(es)\n";
echo "========================================\n\n";

$totalPass = 0;
$totalFail = 0;
$allErrors = [];
$startTime = microtime(true);

foreach ($testClasses as $className) {
    try {
        $test = new $className();
        $test->run();
        $test->printResults();

        $totalPass += $test->getPassCount();
        $totalFail += $test->getFailCount();
        foreach ($test->getErrors() as $error) {
            $allErrors[] = "[$className] $error";
        }
    } catch (Throwable $e) {
        echo "\n=== $className ===\n";
        echo "ERROR: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        $totalFail++;
        $allErrors[] = "[$className] FATAL: " . $e->getMessage();
    }
}

$elapsedTime = microtime(true) - $startTime;

echo "\n========================================\n";
echo "  Test Summary\n";
echo "========================================\n";
echo "Total Pass: $totalPass\n";
echo "Total Fail: $totalFail\n";
echo "Time: " . round($elapsedTime, 3) . "s\n";
echo "========================================\n";

if (!empty($allErrors)) {
    echo "\nFailed Tests:\n";
    foreach ($allErrors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    exit(1);
}

echo "\nAll tests passed!\n";
exit(0);
