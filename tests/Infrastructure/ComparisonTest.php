<?php
declare(strict_types=1);

namespace FakerTesting\Infrastructure;

/** All apparent platforms/measurements below are synthetic parser fixtures in temporary directories. */
final class ComparisonTest extends SyntheticFixtureTestCase
{
    private int $comparisonNumber = 0;

    public function testMissingContextsRemainNotRunWithAllTwentyFourCaseRows(): void
    {
        mkdir($this->temporary . '/input');
        [$exit, $comparison, $markdown] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame(2, $comparison['schema_version']);
        $this->assertSame('Not Run', $comparison['status']);
        $this->assertSame('github-actions', $comparison['required_execution_context']);
        $this->assertSame(['github-Windows', 'github-Darwin', 'github-Linux'], $comparison['required_platform_keys']);
        $this->assertSame(['local-Windows', 'local-Darwin', 'local-Linux', 'github-Windows', 'github-Darwin', 'github-Linux'],
            array_keys($comparison['platform_results']));
        $this->assertSame(\systemCaseIds(), array_keys($comparison['case_results']));
        $this->assertCount(3, $comparison['reasons']);
        foreach ($comparison['platform_results'] as $platform) {
            $this->assertSame('Not Run', $platform['status']);
        }
        $this->assertSame(0, $comparison['statistics']['available_case_executions']);
        $this->assertSame(72, $comparison['statistics']['required_case_executions']);
        $this->assertStringNotContainsString('TC24', $markdown);
    }

    public function testCompleteThreeCiFixturesCompareWithinTheirContext(): void
    {
        $this->threeCiRuns();
        [$exit, $comparison, $markdown] = $this->compare();
        $this->assertSame(0, $exit, implode('; ', $comparison['reasons']));
        $this->assertSame('Passed', $comparison['status']);
        $this->assertSame([], $comparison['reasons']);
        $this->assertCount(3, $comparison['sources']);
        $this->assertSame(3, $comparison['statistics']['available_platform_executions']);
        $this->assertSame(72, $comparison['statistics']['available_case_executions']);
        foreach (['Windows', 'Darwin', 'Linux'] as $os) {
            $this->assertSame('Passed', $comparison['platform_results']['github-' . $os]['status']);
            $this->assertSame('Not Run', $comparison['platform_results']['local-' . $os]['status']);
            $this->assertSame('Passed', $comparison['case_results']['F15']['github-' . $os]);
            $this->assertSame('Not Run', $comparison['case_results']['F15']['local-' . $os]);
        }
        $this->assertStringNotContainsString('TC21', $markdown);
        $this->assertStringNotContainsString('TC24', $markdown);
    }

    public function testCiWindowsCannotFillMissingLocalWindowsEvidence(): void
    {
        $this->threeCiRuns();
        $this->syntheticRun('Darwin', 'local-device');
        [$exit, $comparison] = $this->compare('local-device');
        $this->assertSame(1, $exit);
        $this->assertSame('Not Run', $comparison['status']);
        $this->assertContains('Missing local-Windows execution evidence', $comparison['reasons']);
        $this->assertSame('Passed', $comparison['platform_results']['github-Windows']['status']);
        $this->assertSame('Not Run', $comparison['platform_results']['local-Windows']['status']);
        $this->assertSame('Not Run', $comparison['case_results']['N02']['local-Windows']);
        $this->assertSame(1, $comparison['statistics']['available_platform_executions']);
        $this->assertSame(24, $comparison['statistics']['available_case_executions']);
    }

    public function testDuplicateFullContextIsAnError(): void
    {
        $this->syntheticRun('Windows', 'github-actions', '-one');
        $this->syntheticRun('Windows', 'github-actions', '-two');
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('Multiple full runs for github-Windows', implode('; ', $comparison['reasons']));
    }

    public function testMissingBenchmarkFileCannotBeReplacedBySummaryData(): void
    {
        $runs = $this->threeCiRuns();
        unlink($runs['Windows'] . '/measurements/N02.json');
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('Required evidence file missing:', implode('; ', $comparison['reasons']));
        $this->assertStringContainsString('measurements/N02.json', implode('; ', $comparison['reasons']));
    }

    /** @dataProvider truncatedBenchmarkFields */
    public function testValidJsonWithMissingBenchmarkFieldsCannotPassComparison(array $path, string $reason): void
    {
        $runs = $this->threeCiRuns();
        $measurement = $this->syntheticMeasurement('N01');
        $this->removeMeasurementField($measurement, $path);
        // Keep all three persisted copies identical: JSON syntax and cross-file equality alone are insufficient.
        \writeJson($runs['Windows'] . '/measurements/N01.json', $measurement);
        $this->changeJson($runs['Windows'] . '/observations/N01.json', static function (array $observation) use ($measurement): array {
            $observation['data'] = $measurement;
            return $observation;
        });
        $observation = json_decode(file_get_contents($runs['Windows'] . '/observations/N01.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($measurement, $observation): array {
            $summary['measurements']['N01'] = $measurement;
            $summary['observations']['N01'] = $observation;
            return $summary;
        });
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString($reason, implode('; ', $comparison['reasons']));
    }

    public function truncatedBenchmarkFields(): array
    {
        return [
            'missing seed' => [['seed'], 'benchmark field: seed'],
            'missing framework scope' => [['framework_scope'], 'incomplete framework scope'],
            'missing measured generation calls' => [['workload', 'generation_calls'], 'benchmark workload: generation_calls'],
        ];
    }

    /** @dataProvider invalidBenchmarkContracts */
    public function testSharedBenchmarkValidatorRejectsWrongTypesAndScopes(array $path, $value, string $reason): void
    {
        $measurement = $this->syntheticMeasurement('N02');
        $node = &$measurement;
        foreach (array_slice($path, 0, -1) as $key) {
            $node = &$node[$key];
        }
        $node[$path[count($path) - 1]] = $value;
        unset($node);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($reason);
        \validateBenchmarkMeasurement($measurement, 'N02');
    }

    public function invalidBenchmarkContracts(): array
    {
        return [
            'wrong seed type' => [['seed'], '4160', 'benchmark field: seed'],
            'wrong child runtime' => [['worker_php_version'], '8.4.0', 'benchmark field: worker_php_version'],
            'wrong memory configuration' => [['memory_limit'], '512M', 'benchmark field: memory_limit'],
            'loaded runner' => [['framework_scope', 'phpunit_runner_started'], true, 'incomplete framework scope'],
            'missing composer scope' => [['framework_scope', 'composer_autoloader_loaded'], false, 'incomplete framework scope'],
            'incorrect measured calls' => [['workload', 'generation_calls'], 3000, 'benchmark workload: generation_calls'],
            'wrong peak type' => [['peak_allocated_memory_bytes'], '6291456', 'incomplete process/memory/output evidence'],
            'invalid output hash' => [['validated_outputs_sha256'], 'not-a-sha256', 'incomplete process/memory/output evidence'],
            'missing scope text' => [['timing_scope'], '', 'missing measurement scope/provenance'],
            'missing batch' => [['batch_seconds'], [0.11, 0.12], 'needs three batch measurements'],
            'zero duration' => [['batch_seconds'], [0.11, 0, 0.12], 'missing/invalid numeric measurement'],
            'nonfinite duration' => [['batch_seconds'], [0.11, INF, 0.12], 'missing/invalid numeric measurement'],
            'wrong median' => [['median_seconds'], 0.11, 'measurement arithmetic is inconsistent'],
        ];
    }

    public function testPassedCaseRequiresItsOwnCurrentObservationFile(): void
    {
        $runs = $this->threeCiRuns();
        unlink($runs['Windows'] . '/observations/F15.json');
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('observations/F15.json', implode('; ', $comparison['reasons']));
    }

    public function testSummaryCannotOverrideNativeJunitCounts(): void
    {
        $runs = $this->threeCiRuns();
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary): array {
            $summary['counts']['passed'] = 23;
            return $summary;
        });
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('Summary and native JUnit disagree', implode('; ', $comparison['reasons']));
    }

    public function testDuplicateCaseCannotFillTheRequiredTwentyFourUniqueIds(): void
    {
        $runs = $this->threeCiRuns();
        $path = $runs['Windows'] . '/junit.xml';
        file_put_contents($path, str_replace('testN02Synthetic', 'testN01Synthetic', file_get_contents($path)));
        $parsed = \readJunit($path);
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($parsed): array {
            $summary['cases'] = $parsed['cases'];
            $summary['counts'] = $parsed['counts'];
            return $summary;
        });
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('each F01-F22/N01-N02 exactly once', implode('; ', $comparison['reasons']));
    }

    public function testFullRunCannotClaimADifferentSelectedIdSet(): void
    {
        $runs = $this->threeCiRuns();
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary): array {
            $summary['selected_case_ids'] = ['F15'];
            return $summary;
        });
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('each F01-F22/N01-N02 exactly once', implode('; ', $comparison['reasons']));
    }

    /** @dataProvider tamperedStatistics */
    public function testPassedRunStatisticsMustMatchNativeAndWorkerEvidence(string $field, int $value): void
    {
        $runs = $this->threeCiRuns();
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($field, $value): array {
            $summary['statistics'][$field] = $value;
            return $summary;
        });
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('Summary statistics disagree with current evidence: github-Windows/' . $field,
            implode('; ', $comparison['reasons']));
    }

    public function tamperedStatistics(): array
    {
        return [
            ['designed_report_cases', 25], ['selected_report_cases', 23], ['executed_report_cases', 23],
            ['phpunit_test_methods', 25], ['parent_phpunit_assertions', 25],
            ['benchmark_worker_assertions', 0], ['platform_executions_in_this_run', 3],
        ];
    }

    /** @dataProvider nonPassingNativeStatuses */
    public function testNativeNonPassCannotBecomeSuccessfulComparison(string $tag, string $status): void
    {
        $runs = $this->threeCiRuns();
        $path = $runs['Windows'] . '/junit.xml';
        file_put_contents($path, str_replace(
            '<testcase name="testF15Synthetic" class="SyntheticInfrastructureContract" assertions="1" time="0.01"/>',
            '<testcase name="testF15Synthetic" class="SyntheticInfrastructureContract" assertions="1" time="0.01"><' .
            $tag . '>Synthetic non-passing fixture</' . $tag . '></testcase>', file_get_contents($path)));
        $parsed = \readJunit($path);
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($parsed): array {
            $summary['cases'] = $parsed['cases'];
            $summary['counts'] = $parsed['counts'];
            $summary['status'] = 'Failed';
            $summary['exit_code'] = $summary['phpunit_exit_code'] = 1;
            return $summary;
        });
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Failed', $comparison['status']);
        $this->assertSame('Failed', $comparison['platform_results']['github-Windows']['status']);
        $this->assertSame($status, $comparison['case_results']['F15']['github-Windows']);
    }

    public function nonPassingNativeStatuses(): array
    {
        return [['failure', 'Failed'], ['error', 'Error'], ['skipped', 'Skipped']];
    }

    public function testControlledEnvironmentDifferenceFailsComparison(): void
    {
        $runs = $this->threeCiRuns();
        $this->changeJson($runs['Darwin'] . '/environment.json', static function (array $environment): array {
            $environment['timezone'] = 'Europe/Helsinki';
            return $environment;
        });
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Failed', $comparison['status']);
        $this->assertContains('github-Darwin differs in controlled field timezone', $comparison['reasons']);
    }

    /** @dataProvider unsupportedPhpunitVersions */
    public function testEqualButWrongOrMissingPhpunitVersionsCannotPass($version): void
    {
        $runs = $this->threeCiRuns();
        foreach ($runs as $directory) {
            $this->changeJson($directory . '/environment.json', static function (array $environment) use ($version): array {
                $environment['phpunit_version'] = $version;
                return $environment;
            });
        }
        [$exit, $comparison] = $this->compare();
        $this->assertSame(1, $exit);
        $this->assertSame('Failed', $comparison['status']);
        foreach (['Windows', 'Darwin', 'Linux'] as $os) {
            $this->assertContains('github-' . $os . ' does not use the required PHP/PHPUnit/Faker baseline', $comparison['reasons']);
        }
    }

    public function unsupportedPhpunitVersions(): array
    {
        return [['PHPUnit 10.0.0'], [null]];
    }

    public function testValidDifferentBenchmarkValuesAreNotATimingAcceptanceFailure(): void
    {
        $runs = $this->threeCiRuns();
        $path = $runs['Windows'] . '/measurements/N01.json';
        $measurement = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $measurement['batch_seconds'] = [0.22, 0.26, 0.24];
        $measurement['median_seconds'] = 0.24;
        $measurement['records_per_second'] = 1000 / 0.24;
        $measurement['peak_allocated_memory_bytes'] = 8388608;
        $measurement['peak_allocated_memory_mib'] = 8.0;
        \writeJson($path, $measurement);
        $this->changeJson($runs['Windows'] . '/observations/N01.json', static function (array $observation) use ($measurement): array {
            $observation['data'] = $measurement;
            return $observation;
        });
        $observation = json_decode(file_get_contents($runs['Windows'] . '/observations/N01.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($measurement, $observation): array {
            $summary['measurements']['N01'] = $measurement;
            $summary['observations']['N01'] = $observation;
            return $summary;
        });
        [$exit, $comparison] = $this->compare();
        $this->assertSame(0, $exit, implode('; ', $comparison['reasons']));
        $this->assertSame('Passed', $comparison['status']);
        $this->assertSame('not_assessed', $comparison['platform_results']['github-Windows']['measurements']['N01']['performance_acceptance']);
    }

    public function testMissingCiProvenanceIsAnError(): void
    {
        $this->syntheticRun('Windows');
        $this->changeJson($this->temporary . '/input/github-Windows/environment.json', static function (array $environment): array {
            unset($environment['github']['sha']);
            return $environment;
        });
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('lacks workflow run/commit/runner provenance', implode('; ', $comparison['reasons']));
    }

    public function testInconsistentBenchmarkArithmeticIsAnError(): void
    {
        $runs = $this->threeCiRuns();
        $measurementPath = $runs['Windows'] . '/measurements/N01.json';
        $measurement = json_decode(file_get_contents($measurementPath), true, 512, JSON_THROW_ON_ERROR);
        $measurement['median_seconds'] = 10;
        \writeJson($measurementPath, $measurement);
        $this->changeJson($runs['Windows'] . '/observations/N01.json', static function (array $observation) use ($measurement): array {
            $observation['data'] = $measurement;
            return $observation;
        });
        $observation = json_decode(file_get_contents($runs['Windows'] . '/observations/N01.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->changeJson($runs['Windows'] . '/summary.json', static function (array $summary) use ($measurement, $observation): array {
            $summary['measurements']['N01'] = $measurement;
            $summary['observations']['N01'] = $observation;
            return $summary;
        });
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
        $this->assertStringContainsString('measurement arithmetic is inconsistent', implode('; ', $comparison['reasons']));
    }

    public function testNativeZeroTimeSurvivesSummarySerialization(): void
    {
        $runs = $this->threeCiRuns();
        foreach ($runs as $directory) {
            $path = $directory . '/junit.xml';
            file_put_contents($path, str_replace('time="0.01"', 'time="0"', file_get_contents($path)));
            $parsed = \readJunit($path);
            $this->changeJson($directory . '/summary.json', static function (array $summary) use ($parsed): array {
                $summary['cases'] = $parsed['cases'];
                $summary['counts'] = $parsed['counts'];
                return $summary;
            });
        }
        [$exit, $comparison] = $this->compare();
        $this->assertSame(0, $exit, implode('; ', $comparison['reasons']));
        $this->assertSame('Passed', $comparison['status']);
    }

    public function testMalformedSummaryIsAnErrorAndFilteredRunDoesNotSupplyAPlatform(): void
    {
        $this->fixture('input/filtered/summary.json', json_encode([
            'schema_version' => 2, 'suite' => 'report', 'full_report_run' => false, 'selected_case_ids' => ['F15'],
            'synthetic_fixture' => true,
        ], JSON_THROW_ON_ERROR));
        [, $comparison] = $this->compare();
        $this->assertSame('Not Run', $comparison['status']);
        $this->assertCount(1, $comparison['ignored_sources']);
        $this->fixture('input/malformed/summary.json', '{"schema_version":2');
        [, $comparison] = $this->compare();
        $this->assertSame('Error', $comparison['status']);
    }

    public function testExistingComparisonEvidenceIsPreservedAndRefused(): void
    {
        mkdir($this->temporary . '/input');
        foreach (['comparison.json', 'comparison.md'] as $filename) {
            $directory = $this->temporary . '/existing-' . str_replace('.', '-', $filename);
            mkdir($directory);
            $contents = 'SYNTHETIC original evidence must not be overwritten: ' . $filename;
            file_put_contents($directory . '/' . $filename, $contents);
            $result = \command([PHP_BINARY, \projectRoot() . '/scripts/compare-results.php',
                $this->temporary . '/input', $directory], \projectRoot());
            $this->assertSame(2, $result['exit_code']);
            $this->assertStringContainsString('Comparison evidence already exists', $result['output']);
            $this->assertSame($contents, file_get_contents($directory . '/' . $filename));
            $other = $filename === 'comparison.json' ? 'comparison.md' : 'comparison.json';
            $this->assertFileDoesNotExist($directory . '/' . $other);
        }
    }

    private function compare(?string $context = null): array
    {
        $output = $this->temporary . '/comparison-' . ++$this->comparisonNumber;
        $arguments = [PHP_BINARY, \projectRoot() . '/scripts/compare-results.php', $this->temporary . '/input', $output];
        if ($context !== null) {
            $arguments[] = '--context=' . $context;
        }
        $result = \command($arguments, \projectRoot());
        $this->assertFileExists($output . '/comparison.json', $result['output']);
        return [$result['exit_code'], json_decode(file_get_contents($output . '/comparison.json'), true, 512, JSON_THROW_ON_ERROR),
            file_get_contents($output . '/comparison.md')];
    }

    private function threeCiRuns(): array
    {
        $runs = [];
        foreach (['Windows', 'Darwin', 'Linux'] as $os) {
            $runs[$os] = $this->syntheticRun($os);
        }
        return $runs;
    }

    private function syntheticRun(string $os, string $kind = 'github-actions', string $suffix = ''): string
    {
        $key = ($kind === 'github-actions' ? 'github-' : 'local-') . $os;
        $directory = $this->temporary . '/input/' . $key . $suffix;
        mkdir($directory . '/observations', 0777, true);
        mkdir($directory . '/measurements');
        file_put_contents($directory . '/SYNTHETIC-FIXTURE.txt', 'Not an operating-system or benchmark execution.');
        $xml = '<testsuites><testsuite name="SyntheticInfrastructureContract">';
        foreach (\systemCaseIds() as $id) {
            $xml .= '<testcase name="test' . $id . 'Synthetic" class="SyntheticInfrastructureContract" assertions="1" time="0.01"/>';
        }
        file_put_contents($directory . '/junit.xml', $xml . '</testsuite></testsuites>');
        $parsed = \readJunit($directory . '/junit.xml');
        $summary = ['schema_version' => 2, 'suite' => 'report', 'full_report_run' => true,
            'run_id' => 'SYNTHETIC-' . $key . $suffix, 'selected_case_ids' => \systemCaseIds(),
            'filter' => null, 'tester_selection' => null, 'status' => 'Passed', 'exit_code' => 0, 'phpunit_exit_code' => 0,
            'started_at_utc' => '2000-01-01T00:00:00Z', 'finished_at_utc' => '2000-01-01T00:00:01Z',
            'cases' => $parsed['cases'], 'counts' => $parsed['counts'], 'observations' => [], 'measurements' => [],
            'statistics' => ['designed_report_cases' => 24, 'selected_report_cases' => 24, 'executed_report_cases' => 24,
                'phpunit_test_methods' => 24, 'parent_phpunit_assertions' => 24, 'benchmark_worker_assertions' => 54000,
                'platform_executions_in_this_run' => 1], 'report_error' => null, 'synthetic_fixture' => true];
        foreach (\systemCaseIds() as $id) {
            $data = ['synthetic_fixture' => true, 'validated_count' => 1];
            if (in_array($id, ['N01', 'N02'], true)) {
                $data = $this->syntheticMeasurement($id);
                $summary['measurements'][$id] = $data;
                \writeJson($directory . '/measurements/' . $id . '.json', $data);
            }
            $observation = ['case_id' => $id, 'data' => $data, 'provenance' => 'SYNTHETIC INFRASTRUCTURE FIXTURE'];
            $summary['observations'][$id] = $observation;
            \writeJson($directory . '/observations/' . $id . '.json', $observation);
        }
        $environment = ['capture_complete' => true, 'os_family' => $os, 'os_version' => 'SYNTHETIC',
            'architecture' => 'synthetic-fixture', 'execution_context' => ['kind' => $kind, 'platform_key' => $key,
                'label' => 'SYNTHETIC ' . $key],
            'php_version' => '7.4.33', 'phpunit_version' => 'PHPUnit 9.6.38',
            'composer_lock_sha256' => str_repeat('a', 64), 'test_contract_sha256' => str_repeat('b', 64),
            'php_int_size' => 8, 'case_seeds' => array_map(static fn(array $case): int => $case['seed'], \caseCatalog()),
            'case_locales' => array_map(static fn(array $case): string => $case['locale'], \caseCatalog()),
            'timezone' => 'UTC', 'locale_setting' => 'C', 'precision' => 14, 'serialize_precision' => -1,
            'test_memory_limit' => '256M', 'faker' => ['package' => 'fzaninotto/faker', 'version' => '1.9.2',
                'commit' => \FAKER_COMMIT, 'src_sha256' => str_repeat('c', 64)],
            'dependencies' => ['synthetic_fixture' => true], 'report_design_source' => ['synthetic_fixture' => true],
            'github' => $kind === 'github-actions' ? ['run_id' => 'SYNTHETIC-WORKFLOW',
                'sha' => str_repeat('d', 40), 'runner_os' => $os] : ['run_id' => null, 'sha' => null, 'runner_os' => null],
            'synthetic_fixture' => true];
        \writeJson($directory . '/environment.json', $environment);
        \writeJson($directory . '/summary.json', $summary);
        return $directory;
    }

    private function syntheticMeasurement(string $id): array
    {
        return ['schema_version' => 1, 'case_id' => $id, 'measurement_status' => 'completed',
            'performance_acceptance' => 'not_assessed', 'locale' => 'en_US', 'timezone' => 'UTC',
            'locale_setting' => 'C', 'seed' => 4160, 'warmup_seed' => 4160, 'seed_reset_after_warmup' => true,
            'timer' => 'hrtime(true)', 'worker_php_version' => '7.4.33', 'worker_php_binary' => '/SYNTHETIC/php',
            'memory_limit' => '256M', 'memory_limit_bytes' => 268435456,
            'worker_validation_assertions' => 27000, 'worker_pid' => 1234,
            'framework_scope' => ['description' => 'SYNTHETIC Composer and assertion classes, without runner.',
                'composer_autoloader_loaded' => true, 'phpunit_assert_class_loaded' => true,
                'phpunit_runner_started' => false, 'loaded_phpunit_runner_classes' => [],
                'memory_includes' => 'SYNTHETIC PHP allocations including loaded dependencies.'],
            'workload' => ['fields' => ['name', 'email', 'uuid'], 'warmup_rows' => 100, 'warmup_generation_calls' => 300,
                'batch_count' => 3, 'rows_per_batch' => 1000, 'measured_rows' => 3000, 'validated_rows' => 3000,
                'generation_calls' => 9000, 'max_retained_measured_rows' => 1000,
                'retention' => 'SYNTHETIC one batch retained, then validated and released.'],
            'batch_seconds' => [0.11, 0.13, 0.12], 'median_seconds' => 0.12, 'records_per_second' => 1000 / 0.12,
            'peak_allocated_memory_bytes' => 6291456, 'peak_allocated_memory_mib' => 6.0,
            'timing_scope' => 'SYNTHETIC generation and row retention, excluding validation and report writes.',
            'memory_metric' => 'SYNTHETIC memory_get_peak_usage(true), not OS RSS or Faker-exclusive memory.',
            'peak_capture_point' => 'SYNTHETIC after batch validation and release, before report serialization.',
            'worker_assertion_scope' => 'SYNTHETIC child assertions, separate from parent PHPUnit.',
            'captured_at_utc' => '2000-01-01T00:00:00Z', 'validated_outputs_sha256' => str_repeat('e', 64),
            'synthetic_fixture' => true];
    }

    private function removeMeasurementField(array &$measurement, array $path): void
    {
        $node = &$measurement;
        foreach (array_slice($path, 0, -1) as $key) {
            $node = &$node[$key];
        }
        unset($node[$path[count($path) - 1]]);
    }

    private function changeJson(string $path, callable $change): void
    {
        \writeJson($path, $change(json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)));
    }
}
