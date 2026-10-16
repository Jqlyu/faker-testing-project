<?php
declare(strict_types=1);

use Faker\Factory;
use PHPUnit\Framework\Assert;

require_once __DIR__ . '/common.php';

/** This entry point deliberately does not bootstrap the PHPUnit test runner. */
try {
    if (PHP_SAPI !== 'cli') {
        throw new RuntimeException('The benchmark worker requires PHP CLI.');
    }
    set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (!preg_match('/^--(case|output)=(.+)$/D', $argument, $matches) || isset($options[$matches[1]])) {
            throw new RuntimeException('Usage: benchmark-worker.php --case=N01|N02 --output=<absolute JSON path>');
        }
        $options[$matches[1]] = $matches[2];
    }
    if (count($options) !== 2 || !in_array($options['case'] ?? '', ['N01', 'N02'], true)) {
        throw new RuntimeException('Both a valid --case and an absolute --output path are required.');
    }
    $output = $options['output'];
    if (!preg_match('~^(?:/|[A-Za-z]:[/\\\\]|\\\\\\\\)~', $output) || !is_dir(dirname($output))) {
        throw new RuntimeException('The output path must be absolute and its directory must exist.');
    }
    if (is_file($output)) {
        throw new RuntimeException('Refusing an existing measurement file: ' . $output);
    }

    require projectRoot() . '/vendor/autoload.php';
    date_default_timezone_set('UTC');
    if (setlocale(LC_ALL, 'C') === false) {
        throw new RuntimeException('Could not select the controlled C locale.');
    }
    $faker = Factory::create('en_US');
    $faker->seed(4160);
    $assertionsBefore = Assert::getCount();
    for ($i = 0; $i < 100; ++$i) {
        $warmupRow = ['name' => $faker->name, 'email' => $faker->email, 'uuid' => $faker->uuid];
        unset($warmupRow);
    }

    $faker->seed(4160);
    $batchSeconds = [];
    $validatedRows = 0;
    $validatedHash = hash_init('sha256');
    for ($batch = 0; $batch < 3; ++$batch) {
        $rows = [];
        $started = hrtime(true);
        for ($i = 0; $i < 1000; ++$i) {
            $rows[] = ['name' => $faker->name, 'email' => $faker->email, 'uuid' => $faker->uuid];
        }
        $finished = hrtime(true);
        $batchSeconds[] = ($finished - $started) / 1000000000;

        // All assertions and hashing occur after the generation timer has stopped.
        foreach ($rows as $row) {
            $context = 'batch ' . ($batch + 1) . ', row ' . $validatedRows;
            Assert::assertSame(['name', 'email', 'uuid'], array_keys($row), $context);
            Assert::assertIsString($row['name'], $context);
            Assert::assertNotSame('', trim($row['name']), $context);
            Assert::assertTrue(mb_check_encoding($row['name'], 'UTF-8'), $context);
            Assert::assertStringNotContainsString('{{', $row['name'], $context);
            Assert::assertIsString($row['email'], $context);
            Assert::assertNotFalse(filter_var($row['email'], FILTER_VALIDATE_EMAIL), $context);
            Assert::assertIsString($row['uuid'], $context);
            Assert::assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-3[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',
                $row['uuid'],
                $context
            );
            hash_update($validatedHash, json_encode($row, JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
            ++$validatedRows;
        }
        unset($row, $rows);
    }

    $workerValidationAssertions = Assert::getCount() - $assertionsBefore;
    $validatedOutputsHash = hash_final($validatedHash);
    $sortedSeconds = $batchSeconds;
    sort($sortedSeconds, SORT_NUMERIC);
    $medianSeconds = $sortedSeconds[1];
    foreach ($batchSeconds as $seconds) {
        if (!is_finite($seconds) || $seconds <= 0) {
            throw new RuntimeException('The monotonic generation timer did not return a positive duration.');
        }
    }
    if ($validatedRows !== 3000 || $workerValidationAssertions !== 27000) {
        throw new RuntimeException('The worker did not validate the complete measured workload.');
    }

    $memoryLimit = ini_get('memory_limit');
    if ($memoryLimit !== '256M') {
        throw new RuntimeException('The worker requires the configured 256M PHP memory limit.');
    }
    $loadedRunnerClasses = array_values(array_filter(get_declared_classes(), static function (string $class): bool {
        return strpos($class, 'PHPUnit\\TextUI\\') === 0 || strpos($class, 'PHPUnit\\Runner\\') === 0;
    }));
    if ($loadedRunnerClasses !== []) {
        throw new RuntimeException('A PHPUnit runner class was unexpectedly loaded in the isolated worker.');
    }
    $frameworkScope = [
        'description' => 'Composer autoloader plus PHPUnit assertion classes and their loaded dependencies; no PHPUnit runner is started.',
        'composer_autoloader_loaded' => class_exists(Composer\Autoload\ClassLoader::class, false),
        'phpunit_assert_class_loaded' => class_exists(Assert::class, false),
        'phpunit_runner_started' => false,
        'loaded_phpunit_runner_classes' => $loadedRunnerClasses,
        'memory_includes' => 'PHP runtime allocations, Composer, Faker, PHPUnit assertion classes, and loaded dependencies.',
    ];
    $peakAllocatedBytes = memory_get_peak_usage(true);
    $measurement = [
        'schema_version' => 1,
        'case_id' => $options['case'],
        'measurement_status' => 'completed',
        'performance_acceptance' => 'not_assessed',
        'locale' => 'en_US',
        'timezone' => date_default_timezone_get(),
        'locale_setting' => setlocale(LC_ALL, 0),
        'seed' => 4160,
        'warmup_seed' => 4160,
        'seed_reset_after_warmup' => true,
        'workload' => [
            'fields' => ['name', 'email', 'uuid'],
            'warmup_rows' => 100,
            'warmup_generation_calls' => 300,
            'batch_count' => 3,
            'rows_per_batch' => 1000,
            'measured_rows' => 3000,
            'validated_rows' => $validatedRows,
            'generation_calls' => 9000,
            'retention' => 'One 1000-row measured batch at a time; validate and hash after timing, then release before the next batch.',
            'max_retained_measured_rows' => 1000,
        ],
        'timing_scope' => 'Row generation and retention only; excludes warm-up, validation, hashing, printing, and report writes.',
        'timer' => 'hrtime(true)',
        'batch_seconds' => $batchSeconds,
        'median_seconds' => $medianSeconds,
        'records_per_second' => 1000 / $medianSeconds,
        'worker_validation_assertions' => $workerValidationAssertions,
        'worker_assertion_scope' => 'Child process assertions only; not included in parent PHPUnit/JUnit assertion counts.',
        'validated_outputs_sha256' => $validatedOutputsHash,
        'memory_limit' => $memoryLimit,
        'memory_limit_bytes' => 256 * 1024 * 1024,
        'peak_allocated_memory_bytes' => $peakAllocatedBytes,
        'peak_allocated_memory_mib' => $peakAllocatedBytes / (1024 * 1024),
        'memory_metric' => 'memory_get_peak_usage(true): PHP allocated process peak, not OS RSS or Faker-exclusive memory.',
        'peak_capture_point' => 'After all measured batches are validated, hashed, and released; before measurement serialization and report writes.',
        'worker_pid' => getmypid(),
        'worker_php_binary' => PHP_BINARY,
        'worker_php_version' => PHP_VERSION,
        'framework_scope' => $frameworkScope,
        'captured_at_utc' => gmdate('c'),
    ];

    // Publish the result only after the complete workload and validation succeed.
    $temporaryOutput = $output . '.tmp-' . getmypid();
    writeJson($temporaryOutput, $measurement);
    if (!rename($temporaryOutput, $output)) {
        throw new RuntimeException('Cannot publish completed measurement: ' . $output);
    }
} catch (Throwable $error) {
    if (isset($temporaryOutput) && is_file($temporaryOutput)) {
        unlink($temporaryOutput);
    }
    fwrite(STDERR, 'Benchmark worker error: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
