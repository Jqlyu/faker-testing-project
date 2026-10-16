<?php
declare(strict_types=1);

namespace FakerTesting\Report;

use FakerTesting\Support\ReportTestCase;
use RuntimeException;

require_once dirname(__DIR__, 2) . '/scripts/common.php';

final class NonFunctionalReportTest extends ReportTestCase
{
    public function testN01GenerationTimingAndThroughput(): void
    {
        $measurement = $this->runMeasurement('N01');
        $this->assertWorkloadAndTiming($measurement);
        $this->observe('N01', $measurement);
        printf("N01: batches %.6f / %.6f / %.6f s; median %.6f s; %.2f records/s; worker assertions %d.\n",
            $measurement['batch_seconds'][0], $measurement['batch_seconds'][1],
            $measurement['batch_seconds'][2], $measurement['median_seconds'],
            $measurement['records_per_second'], $measurement['worker_validation_assertions']);
    }

    public function testN02IsolatedAllocatedMemoryPeak(): void
    {
        $measurement = $this->runMeasurement('N02');
        $this->assertWorkloadAndTiming($measurement);
        $this->assertIsInt($measurement['peak_allocated_memory_bytes']);
        $this->assertGreaterThan(0, $measurement['peak_allocated_memory_bytes']);
        $this->assertEqualsWithDelta($measurement['peak_allocated_memory_bytes'] / (1024 * 1024),
            $measurement['peak_allocated_memory_mib'], 0.000000001);
        $this->assertSame('256M', $measurement['memory_limit']);
        $this->assertSame(268435456, $measurement['memory_limit_bytes']);
        $this->assertSame([], $measurement['framework_scope']['loaded_phpunit_runner_classes']);
        $this->assertFalse($measurement['framework_scope']['phpunit_runner_started']);
        $this->assertTrue($measurement['framework_scope']['composer_autoloader_loaded']);
        $this->assertTrue($measurement['framework_scope']['phpunit_assert_class_loaded']);
        $this->observe('N02', $measurement);
        printf("N02: isolated worker PID %d; PHP allocated peak %d bytes (%.2f MiB); memory_limit %s; worker assertions %d.\n",
            $measurement['worker_pid'], $measurement['peak_allocated_memory_bytes'],
            $measurement['peak_allocated_memory_mib'], $measurement['memory_limit'],
            $measurement['worker_validation_assertions']);
    }

    private function runMeasurement(string $caseId): array
    {
        $directory = realpath($this->evidenceDirectory());
        if ($directory === false) {
            throw new RuntimeException('The benchmark evidence directory does not exist.');
        }
        $measurementsDirectory = $directory . '/measurements';
        if (!is_dir($measurementsDirectory) && !mkdir($measurementsDirectory, 0777, true) &&
            !is_dir($measurementsDirectory)) {
            throw new RuntimeException('Cannot create the benchmark measurement directory.');
        }
        $path = $measurementsDirectory . '/' . $caseId . '.json';
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Cannot remove a stale benchmark measurement: ' . $path);
        }
        $result = \command([PHP_BINARY, '-d', 'memory_limit=256M', '-d', 'date.timezone=UTC',
            '-d', 'precision=14', '-d', 'serialize_precision=-1',
            \projectRoot() . '/scripts/benchmark-worker.php', '--case=' . $caseId, '--output=' . $path],
            \projectRoot(), $directory . '/worker-' . $caseId . '.log');
        if ($result['exit_code'] !== 0) {
            throw new RuntimeException($caseId . ' benchmark worker exited ' . $result['exit_code'] . ': ' . $result['output']);
        }
        if (!is_file($path)) {
            throw new RuntimeException($caseId . ' benchmark worker did not produce its measurement.');
        }
        try {
            $raw = file_get_contents($path);
            if ($raw === false) {
                throw new RuntimeException('Cannot read ' . $path);
            }
            $measurement = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new RuntimeException($caseId . ' benchmark measurement is malformed JSON.', 0, $error);
        }
        $required = ['schema_version', 'case_id', 'measurement_status', 'performance_acceptance',
            'locale', 'timezone', 'seed', 'warmup_seed', 'seed_reset_after_warmup', 'workload', 'timer',
            'batch_seconds', 'median_seconds', 'records_per_second', 'worker_validation_assertions',
            'validated_outputs_sha256', 'peak_allocated_memory_bytes', 'peak_allocated_memory_mib',
            'memory_limit', 'memory_limit_bytes', 'memory_metric', 'worker_pid', 'framework_scope'];
        if (!is_array($measurement) || array_diff($required, array_keys($measurement)) !== [] ||
            $measurement['case_id'] !== $caseId || $measurement['measurement_status'] !== 'completed' ||
            !is_array($measurement['workload']) || !is_array($measurement['framework_scope']) ||
            !is_array($measurement['batch_seconds']) || array_keys($measurement['batch_seconds']) !== [0, 1, 2]) {
            throw new RuntimeException($caseId . ' benchmark measurement is incomplete.');
        }
        $workloadFields = ['fields', 'warmup_rows', 'warmup_generation_calls', 'batch_count', 'rows_per_batch',
            'measured_rows', 'validated_rows', 'generation_calls', 'retention', 'max_retained_measured_rows'];
        $scopeFields = ['description', 'composer_autoloader_loaded', 'phpunit_assert_class_loaded',
            'phpunit_runner_started', 'loaded_phpunit_runner_classes', 'memory_includes'];
        if (array_diff($workloadFields, array_keys($measurement['workload'])) !== [] ||
            array_diff($scopeFields, array_keys($measurement['framework_scope'])) !== []) {
            throw new RuntimeException($caseId . ' benchmark workload or framework scope is incomplete.');
        }
        foreach (['schema_version', 'seed', 'warmup_seed', 'worker_validation_assertions',
            'peak_allocated_memory_bytes', 'memory_limit_bytes', 'worker_pid'] as $field) {
            if (!is_int($measurement[$field])) {
                throw new RuntimeException($caseId . ' benchmark integer field is incomplete: ' . $field);
            }
        }
        foreach (['locale', 'timezone', 'timer', 'performance_acceptance', 'validated_outputs_sha256',
            'memory_limit', 'memory_metric'] as $field) {
            if (!is_string($measurement[$field]) || $measurement[$field] === '') {
                throw new RuntimeException($caseId . ' benchmark text field is incomplete: ' . $field);
            }
        }
        foreach (array_merge($measurement['batch_seconds'], [$measurement['median_seconds'],
            $measurement['records_per_second'], $measurement['peak_allocated_memory_mib']]) as $number) {
            if ((!is_float($number) && !is_int($number)) || !is_finite((float) $number)) {
                throw new RuntimeException($caseId . ' benchmark numeric measurement is incomplete.');
            }
        }
        foreach (array_diff($workloadFields, ['fields', 'retention']) as $field) {
            if (!is_int($measurement['workload'][$field])) {
                throw new RuntimeException($caseId . ' benchmark workload count is incomplete: ' . $field);
            }
        }
        $scope = $measurement['framework_scope'];
        if (!is_bool($measurement['seed_reset_after_warmup']) || !is_array($measurement['workload']['fields']) ||
            !is_string($measurement['workload']['retention']) || !is_string($scope['description']) ||
            !is_string($scope['memory_includes']) || !is_bool($scope['composer_autoloader_loaded']) ||
            !is_bool($scope['phpunit_assert_class_loaded']) || !is_bool($scope['phpunit_runner_started']) ||
            !is_array($scope['loaded_phpunit_runner_classes'])) {
            throw new RuntimeException($caseId . ' benchmark configuration or framework scope is incomplete.');
        }
        return $measurement;
    }

    private function assertWorkloadAndTiming(array $measurement): void
    {
        $this->assertSame(1, $measurement['schema_version']);
        $this->assertSame('not_assessed', $measurement['performance_acceptance']);
        $this->assertSame('en_US', $measurement['locale']);
        $this->assertSame('UTC', $measurement['timezone']);
        $this->assertSame(4160, $measurement['seed']);
        $this->assertSame(4160, $measurement['warmup_seed']);
        $this->assertTrue($measurement['seed_reset_after_warmup']);
        $this->assertSame(['name', 'email', 'uuid'], $measurement['workload']['fields']);
        $this->assertSame(100, $measurement['workload']['warmup_rows']);
        $this->assertSame(300, $measurement['workload']['warmup_generation_calls']);
        $this->assertSame(3, $measurement['workload']['batch_count']);
        $this->assertSame(1000, $measurement['workload']['rows_per_batch']);
        $this->assertSame(3000, $measurement['workload']['measured_rows']);
        $this->assertSame(3000, $measurement['workload']['validated_rows']);
        $this->assertSame(9000, $measurement['workload']['generation_calls']);
        $this->assertSame(1000, $measurement['workload']['max_retained_measured_rows']);
        $this->assertNotSame('', $measurement['workload']['retention']);
        $this->assertSame('hrtime(true)', $measurement['timer']);
        foreach ($measurement['batch_seconds'] as $seconds) {
            $this->assertIsNumeric($seconds);
            $this->assertTrue(is_finite((float) $seconds));
            $this->assertGreaterThan(0, $seconds);
        }
        $sortedSeconds = $measurement['batch_seconds'];
        sort($sortedSeconds, SORT_NUMERIC);
        $this->assertEqualsWithDelta($sortedSeconds[1], $measurement['median_seconds'], 0.000000000001);
        $this->assertGreaterThan(0, $measurement['median_seconds']);
        $this->assertTrue(is_finite((float) $measurement['records_per_second']));
        $this->assertGreaterThan(0, $measurement['records_per_second']);
        $expectedThroughput = 1000 / $measurement['median_seconds'];
        $this->assertEqualsWithDelta($expectedThroughput, $measurement['records_per_second'], $expectedThroughput * 0.000000000001);
        $this->assertSame(27000, $measurement['worker_validation_assertions']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $measurement['validated_outputs_sha256']);
        $this->assertIsInt($measurement['worker_pid']);
        $this->assertGreaterThan(0, $measurement['worker_pid']);
        $this->assertNotSame(getmypid(), $measurement['worker_pid']);
    }
}
