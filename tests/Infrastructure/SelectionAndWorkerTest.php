<?php
declare(strict_types=1);

namespace FakerTesting\Infrastructure;

final class SelectionAndWorkerTest extends SyntheticFixtureTestCase
{
    public function testCatalogRequiresTwentyFourUniqueReportIdsAndSixCasesPerOwner(): void
    {
        $expected = [];
        for ($number = 1; $number <= 22; ++$number) {
            $expected[] = sprintf('F%02d', $number);
        }
        $expected = array_merge($expected, ['N01', 'N02']);
        $this->assertSame($expected, \systemCaseIds());
        $this->assertCount(24, array_unique(\systemCaseIds()));
        $this->assertSame(['F13', 'F14', 'F15', 'F16', 'N01', 'N02'], \testerCaseIds('Jiaqing Lyu'));
        $owned = [];
        foreach (['Zhiyan Chang', 'Haochang Shi', 'Jiaqing Lyu', 'Jingyi Zhu'] as $tester) {
            $ids = \testerCaseIds($tester);
            $this->assertCount(6, $ids);
            $owned = array_merge($owned, $ids);
        }
        sort($expected);
        sort($owned);
        $this->assertSame($expected, $owned);
        $this->assertSame([], \testerCaseIds('Unknown synthetic tester'));
    }

    public function testPartialReportTableDoesNotCountUnexecutedCasesOrWorkerAssertionsAsParentAssertions(): void
    {
        $summary = ['run_id' => 'SYNTHETIC-INFRATEST', 'started_at_utc' => '2000-01-01T00:00:00Z',
            'report_error' => null, 'cases' => [
                ['id' => 'F15', 'status' => 'Passed', 'assertions' => 300, 'seconds' => 0.01, 'failure_reason' => null],
                ['id' => 'N01', 'status' => 'Passed', 'assertions' => 7, 'seconds' => 0.02, 'failure_reason' => null],
            ], 'observations' => ['F15' => ['data' => ['validated_count' => 100, 'examples' => ['synthetic@example.com']]]],
            'measurements' => ['N01' => ['worker_validation_assertions' => 27000]]];
        $environment = ['os_family' => 'Darwin', 'php_version' => '7.4.33',
            'execution_context' => ['label' => 'SYNTHETIC INFRASTRUCTURE FIXTURE']];
        $table = \renderCaseTable($summary, $environment);
        $this->assertSame(22, substr_count($table, 'Not Run in this execution'));
        $this->assertSame(2, substr_count($table, 'parent PHPUnit assertions'));
        $this->assertStringContainsString('Passed; 300 parent PHPUnit assertions', $table);
        $this->assertStringContainsString('Passed; 7 parent PHPUnit assertions', $table);
        $this->assertStringNotContainsString('27007 parent PHPUnit assertions', $table);
        $this->assertStringNotContainsString('| TC21 |', $table);
        $this->assertStringNotContainsString('| TC24 |', $table);
    }

    /** @dataProvider invalidRunnerSelections */
    public function testInvalidRunnerSelectionIsRejectedBeforeAnExecution(array $arguments): void
    {
        $result = \command(array_merge([PHP_BINARY, \projectRoot() . '/scripts/run-tests.php'], $arguments), \projectRoot());
        $this->assertSame(2, $result['exit_code']);
        $this->assertStringContainsString('Usage:', $result['output']);
        $this->assertStringNotContainsString('Evidence directory:', $result['output']);
        $this->assertStringNotContainsString('CURRENT EXECUTION', $result['output']);
    }

    public function invalidRunnerSelections(): array
    {
        return [
            'supplemental ID cannot select report' => [['--filter=TC01']],
            'missing functional ID' => [['--cases=F01,F23']],
            'missing nonfunctional ID' => [['--cases=N03']],
            'duplicate selected ID' => [['--cases=F15,F15']],
            'empty list entry' => [['--cases=F15,']],
            'empty tester' => [['--tester=']],
            'unknown tester' => [['--tester=Synthetic tester']],
            'mutually exclusive selection' => [['--filter=F13', '--tester=Jiaqing Lyu']],
            'report selector on supplemental suite' => [['--suite=supplemental', '--cases=F13']],
            'unsupported suite' => [['--suite=synthetic']],
        ];
    }

    /** @dataProvider invalidWorkerInvocations */
    public function testWorkerInvalidInvocationPublishesNoMeasurement(array $arguments): void
    {
        $output = $this->temporary . '/synthetic-never-published.json';
        $arguments = array_map(static fn(string $argument): string => str_replace('{OUTPUT}', $output, $argument), $arguments);
        $result = \command(array_merge([PHP_BINARY, '-d', 'memory_limit=256M',
            \projectRoot() . '/scripts/benchmark-worker.php'], $arguments), $this->temporary);
        $this->assertSame(1, $result['exit_code']);
        $this->assertStringContainsString('Benchmark worker error:', $result['output']);
        $this->assertFileDoesNotExist($output);
        $this->assertSame([], glob($output . '.tmp-*'));
    }

    public function invalidWorkerInvocations(): array
    {
        return [
            'missing arguments' => [[]],
            'invalid report case' => [['--case=F01', '--output={OUTPUT}']],
            'relative output' => [['--case=N01', '--output=synthetic-relative.json']],
            'missing directory' => [['--case=N02', '--output={OUTPUT}/missing/measurement.json']],
            'duplicate option' => [['--case=N01', '--case=N02', '--output={OUTPUT}']],
            'unexpected option' => [['--case=N01', '--output={OUTPUT}', '--synthetic=true']],
        ];
    }

    public function testWorkerRefusesToOverwriteAnExistingMeasurement(): void
    {
        $contents = "Explicitly synthetic preexisting infrastructure fixture.\n";
        $output = $this->fixture('synthetic-existing-measurement.json', $contents);
        $result = \command([PHP_BINARY, '-d', 'memory_limit=256M', \projectRoot() . '/scripts/benchmark-worker.php',
            '--case=N02', '--output=' . $output], $this->temporary);
        $this->assertSame(1, $result['exit_code']);
        $this->assertStringContainsString('Refusing an existing measurement file', $result['output']);
        $this->assertSame($contents, file_get_contents($output));
        $this->assertSame([], glob($output . '.tmp-*'));
    }
}
