<?php

abstract class TestCase
{
    protected $passCount = 0;
    protected $failCount = 0;
    protected $errors = [];
    protected $currentTest = '';

    abstract public function run(): void;

    protected function setUp(): void {}

    protected function tearDown(): void {}

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if ($condition) {
            $this->passCount++;
        } else {
            $this->failCount++;
            $this->errors[] = "FAIL [{$this->currentTest}]: " . ($message ?: 'Expected true, got false');
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertTrue(!$condition, $message ?: 'Expected false, got true');
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        $this->assertTrue(
            $expected === $actual,
            $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true)
        );
    }

    protected function assertNotEquals($expected, $actual, string $message = ''): void
    {
        $this->assertTrue(
            $expected !== $actual,
            $message ?: "Expected not equal to " . var_export($expected, true)
        );
    }

    protected function assertNull($value, string $message = ''): void
    {
        $this->assertTrue($value === null, $message ?: 'Expected null');
    }

    protected function assertNotNull($value, string $message = ''): void
    {
        $this->assertTrue($value !== null, $message ?: 'Expected not null');
    }

    protected function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertTrue(
            strpos($haystack, $needle) !== false,
            $message ?: "Expected string contains '$needle'"
        );
    }

    protected function assertGreaterThan(int $expected, int $actual, string $message = ''): void
    {
        $this->assertTrue(
            $actual > $expected,
            $message ?: "Expected $actual > $expected"
        );
    }

    protected function assertLessThan(int $expected, int $actual, string $message = ''): void
    {
        $this->assertTrue(
            $actual < $expected,
            $message ?: "Expected $actual < $expected"
        );
    }

    protected function assertCount(int $expectedCount, array $array, string $message = ''): void
    {
        $this->assertEquals(
            $expectedCount,
            count($array),
            $message ?: "Expected count $expectedCount, got " . count($array)
        );
    }

    protected function assertNotEmpty($value, string $message = ''): void
    {
        $this->assertTrue(!empty($value), $message ?: 'Expected not empty');
    }

    protected function assertEmpty($value, string $message = ''): void
    {
        $this->assertTrue(empty($value), $message ?: 'Expected empty');
    }

    protected function runTest(string $name, callable $testFn): void
    {
        $this->currentTest = $name;
        try {
            $testFn();
        } catch (Throwable $e) {
            $this->failCount++;
            $this->errors[] = "ERROR [{$this->currentTest}]: " . $e->getMessage();
        }
    }

    public function getPassCount(): int { return $this->passCount; }
    public function getFailCount(): int { return $this->failCount; }
    public function getErrors(): array { return $this->errors; }

    public function printResults(): void
    {
        $className = get_class($this);
        echo "\n=== $className ===\n";
        echo "Pass: {$this->passCount}, Fail: {$this->failCount}\n";
        if (!empty($this->errors)) {
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
    }
}
