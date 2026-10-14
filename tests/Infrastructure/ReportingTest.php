<?php
declare(strict_types=1);

namespace FakerTesting\Infrastructure;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/scripts/common.php';

/** Synthetic reporting fixtures are never presented as Faker or OS execution evidence. */
final class ReportingTest extends TestCase
{
    private string $temporary;

    protected function setUp(): void
    {
        $this->temporary = sys_get_temp_dir() . '/faker-reporting-' . bin2hex(random_bytes(6));
        mkdir($this->temporary);
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary,
            \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->temporary);
    }

    public function testJunitPreservesFailureErrorAndSkipReasons(): void
    {
        $path = $this->temporary . '/synthetic.xml';
        file_put_contents($path, '<testsuites><testsuite name="Synthetic">' .
            '<testcase name="pass" time="0.1" assertions="1"/>' .
            '<testcase name="fail"><failure>Expected range; got 999</failure></testcase>' .
            '<testcase name="error"><error>Dependency unavailable</error></testcase>' .
            '<testcase name="skip"><skipped>Feature unavailable</skipped></testcase>' .
            '</testsuite></testsuites>');
        $parsed = \readJunit($path);
        $this->assertSame(['total' => 4, 'passed' => 1, 'failed' => 1, 'errors' => 1, 'skipped' => 1], $parsed['counts']);
        $this->assertSame('Expected range; got 999', $parsed['cases'][1]['failure_reason']);
        $this->assertSame('Dependency unavailable', $parsed['cases'][2]['failure_reason']);
        $this->assertSame('Feature unavailable', $parsed['cases'][3]['failure_reason']);
    }

    public function testZeroCasesCannotBecomeSuccessfulExecution(): void
    {
        $path = $this->temporary . '/synthetic-empty.xml';
        file_put_contents($path, '<testsuites/>');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no test cases');
        \readJunit($path);
    }

    public function testTruncatedReportCannotBecomeSuccessfulExecution(): void
    {
        $path = $this->temporary . '/synthetic-truncated.xml';
        file_put_contents($path, '<testsuites><testsuite>');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid XML');
        \readJunit($path);
    }

    public function testChildExitCodeIsPreserved(): void
    {
        $result = \command([PHP_BINARY, '-r', 'fwrite(STDERR, "intentional reporting self-check"); exit(7);'], $this->temporary);
        $this->assertSame(7, $result['exit_code']);
        $this->assertSame('intentional reporting self-check', $result['output']);
    }

    public function testMissingThreePlatformEvidenceFailsComparison(): void
    {
        mkdir($this->temporary . '/empty-input');
        $result = \command([PHP_BINARY, \projectRoot() . '/scripts/compare-results.php',
            $this->temporary . '/empty-input', $this->temporary . '/comparison'], \projectRoot());
        $this->assertSame(1, $result['exit_code']);
        $comparison = json_decode(file_get_contents($this->temporary . '/comparison/comparison.json'), true);
        $this->assertSame('Not Run', $comparison['status']);
        $this->assertCount(3, $comparison['reasons']);
        foreach ($comparison['platform_cases'] as $case) {
            $this->assertSame('Not Run', $case['status']);
        }
    }

    public function testLegacyNativeEventsRetainSkippedCases(): void
    {
        $path = $this->temporary . '/synthetic-legacy-events.json';
        $event = ['event' => 'test', 'test' => 'Synthetic::testSkipped', 'status' => 'error',
            'message' => 'Skipped Test: external service unavailable', 'time' => 0.01];
        file_put_contents($path, json_encode(['event' => 'suiteStart']) . json_encode($event));
        $parsed = \includeUpstreamSkippedCases(['counts' => ['total' => 0, 'passed' => 0,
            'failed' => 0, 'errors' => 0, 'skipped' => 0], 'cases' => []], $path);
        $this->assertSame(1, $parsed['counts']['total']);
        $this->assertSame(1, $parsed['counts']['skipped']);
        $this->assertSame('Skipped', $parsed['cases'][0]['status']);
        $this->assertSame('external service unavailable', $parsed['cases'][0]['failure_reason']);
    }
}
