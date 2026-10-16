<?php
declare(strict_types=1);

const FAKER_COMMIT = '848d8125239d7dbf8ab25cb7f054f1a630e68c2e';

function projectRoot(): string
{
    return dirname(__DIR__);
}

function writeJson(string $path, array $data): void
{
    if (file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL) === false) {
        throw new RuntimeException('Cannot write ' . $path);
    }
}

/** Validate persisted benchmark evidence independently of a PHPUnit PASS label. */
function validateBenchmarkMeasurement(array $measurement, string $id): void
{
    $fixed = ['schema_version' => 1, 'case_id' => $id, 'measurement_status' => 'completed',
        'performance_acceptance' => 'not_assessed', 'locale' => 'en_US', 'timezone' => 'UTC',
        'locale_setting' => 'C', 'seed' => 4160, 'warmup_seed' => 4160, 'seed_reset_after_warmup' => true,
        'timer' => 'hrtime(true)', 'worker_php_version' => '7.4.33', 'memory_limit' => '256M',
        'memory_limit_bytes' => 268435456, 'worker_validation_assertions' => 27000];
    foreach ($fixed as $key => $value) {
        if (($measurement[$key] ?? null) !== $value) { throw new RuntimeException($id . ' incomplete or incompatible benchmark field: ' . $key); }
    }
    $workload = ['fields' => ['name', 'email', 'uuid'], 'warmup_rows' => 100, 'warmup_generation_calls' => 300,
        'batch_count' => 3, 'rows_per_batch' => 1000, 'measured_rows' => 3000, 'validated_rows' => 3000,
        'generation_calls' => 9000, 'max_retained_measured_rows' => 1000];
    foreach ($workload as $key => $value) {
        if (($measurement['workload'][$key] ?? null) !== $value) { throw new RuntimeException($id . ' incomplete benchmark workload: ' . $key); }
    }
    $scope = ['composer_autoloader_loaded' => true, 'phpunit_assert_class_loaded' => true,
        'phpunit_runner_started' => false, 'loaded_phpunit_runner_classes' => []];
    foreach ($scope as $key => $value) {
        if (($measurement['framework_scope'][$key] ?? null) !== $value) { throw new RuntimeException($id . ' incomplete framework scope: ' . $key); }
    }
    foreach (['timing_scope', 'memory_metric', 'peak_capture_point', 'worker_assertion_scope', 'captured_at_utc', 'worker_php_binary'] as $key) {
        if (!is_string($measurement[$key] ?? null) || trim($measurement[$key]) === '') { throw new RuntimeException($id . ' missing measurement scope/provenance: ' . $key); }
    }
    foreach ([$measurement['workload']['retention'] ?? null, $measurement['framework_scope']['description'] ?? null,
        $measurement['framework_scope']['memory_includes'] ?? null] as $description) {
        if (!is_string($description) || trim($description) === '') { throw new RuntimeException($id . ' missing workload/framework description.'); }
    }
    if (!is_int($measurement['worker_pid'] ?? null) || $measurement['worker_pid'] <= 0 ||
        !is_int($measurement['peak_allocated_memory_bytes'] ?? null) ||
        !is_string($measurement['validated_outputs_sha256'] ?? null) ||
        !preg_match('/^[a-f0-9]{64}$/D', $measurement['validated_outputs_sha256'])) {
        throw new RuntimeException($id . ' incomplete process/memory/output evidence.');
    }
    $batches = $measurement['batch_seconds'] ?? null;
    if (!is_array($batches) || count($batches) !== 3) { throw new RuntimeException($id . ' needs three batch measurements.'); }
    foreach (array_merge($batches, [$measurement['median_seconds'] ?? null, $measurement['records_per_second'] ?? null,
        $measurement['peak_allocated_memory_bytes'], $measurement['peak_allocated_memory_mib'] ?? null]) as $number) {
        if ((!is_int($number) && !is_float($number)) || !is_finite((float) $number) || $number <= 0) {
            throw new RuntimeException($id . ' has a missing/invalid numeric measurement.');
        }
    }
    sort($batches, SORT_NUMERIC);
    if (abs($batches[1] - $measurement['median_seconds']) > 1.0e-12 ||
        abs(1000 / $measurement['median_seconds'] - $measurement['records_per_second']) > $measurement['records_per_second'] * 1.0e-12 ||
        abs($measurement['peak_allocated_memory_bytes'] / 1048576 - $measurement['peak_allocated_memory_mib']) > 1.0e-9) {
        throw new RuntimeException($id . ' measurement arithmetic is inconsistent.');
    }
}

/** An argument array bypasses shell quoting and works with spaces on every OS. */
function command(array $arguments, string $cwd, ?string $logFile = null): array
{
    $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]],
        $pipes, $cwd, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start process: ' . json_encode($arguments));
    }
    fclose($pipes[0]);
    $log = $logFile === null ? null : fopen($logFile, 'wb');
    if ($logFile !== null && $log === false) {
        throw new RuntimeException('Cannot open log ' . $logFile);
    }
    $output = '';
    while (!feof($pipes[1])) {
        $chunk = fread($pipes[1], 8192);
        if ($chunk === false) {
            throw new RuntimeException('Cannot read child output');
        }
        $output .= $chunk;
        if ($log !== null) {
            fwrite($log, $chunk);
            echo $chunk;
        }
    }
    fclose($pipes[1]);
    if (is_resource($log)) {
        fclose($log);
    }
    return ['exit_code' => proc_close($process), 'output' => trim($output)];
}

function optionalCommand(array $arguments, string $cwd): ?string
{
    try {
        $result = command($arguments, $cwd);
        return $result['exit_code'] === 0 ? $result['output'] : null;
    } catch (Throwable $error) {
        return null;
    }
}

function treeHash(string $directory, array $ignoredBasenames = []): string
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,
        FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile() && !in_array($file->getFilename(), $ignoredBasenames, true)) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
            $files[$relative] = hash_file('sha256', $file->getPathname());
        }
    }
    ksort($files);
    return hash('sha256', json_encode($files, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}

function contractHash(): string
{
    $root = projectRoot();
    // Finder/Explorer metadata are ignored by Git and are not executable test inputs.
    // SUT identity keeps the strict unfiltered treeHash default.
    $metadata = ['.DS_Store', 'Thumbs.db'];
    $files = ['tests' => treeHash($root . '/tests', $metadata), 'scripts' => treeHash($root . '/scripts', $metadata),
        'phpunit.xml' => hash_file('sha256', $root . '/phpunit.xml'),
        'phpunit-supplemental.xml' => hash_file('sha256', $root . '/phpunit-supplemental.xml'),
        'phpunit-infrastructure.xml' => hash_file('sha256', $root . '/phpunit-infrastructure.xml'),
        'report_source' => hash_file('sha256', $root . '/docs/REPORT_SOURCE.json')];
    return hash('sha256', json_encode($files, JSON_THROW_ON_ERROR));
}

function readJunit(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('JUnit report missing; PHPUnit may have failed before running tests.');
    }
    $xml = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $xml->load($path, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        throw new RuntimeException('JUnit report is invalid XML.');
    }
    $xpath = new DOMXPath($xml);
    $cases = [];
    $counts = ['total' => 0, 'passed' => 0, 'failed' => 0, 'errors' => 0, 'skipped' => 0];
    foreach ($xpath->query('//testcase') as $node) {
        $status = 'Passed';
        $reasons = [];
        foreach (['skipped' => 'Skipped', 'failure' => 'Failed', 'error' => 'Error'] as $tag => $label) {
            foreach ($node->getElementsByTagName($tag) as $problem) {
                $status = $label;
                $reasons[] = trim($problem->textContent) ?: $problem->getAttribute('message');
            }
        }
        preg_match('/\btest((?:F|N|TC)[0-9]{2})(?=[A-Z_ ]|$)/', $node->getAttribute('name'), $matches);
        $class = $node->getAttribute('class') ?: $node->getAttribute('classname');
        if ($class === '') {
            $class = explode('::', $node->parentNode->getAttribute('name'))[0];
        }
        $cases[] = ['id' => $matches[1] ?? null, 'name' => $node->getAttribute('name'),
            'class' => $class,
            'status' => $status, 'failure_reason' => $reasons === [] ? null : implode("\n", $reasons),
            'assertions' => (int) $node->getAttribute('assertions'),
            'seconds' => (float) $node->getAttribute('time')];
        ++$counts['total'];
        $key = ['Passed' => 'passed', 'Failed' => 'failed', 'Error' => 'errors', 'Skipped' => 'skipped'][$status];
        ++$counts[$key];
    }
    if ($counts['total'] === 0) {
        throw new RuntimeException('JUnit contains no test cases; this is not a successful execution.');
    }
    return ['counts' => $counts, 'cases' => $cases];
}

/** PHPUnit 5 omits skipped cases in JUnit. Its native JSON log retains them. */
function includeUpstreamSkippedCases(array $parsed, string $jsonLog): array
{
    $raw = file_get_contents($jsonLog);
    if ($raw === false) {
        throw new RuntimeException('Missing upstream JSON event log.');
    }
    // The legacy logger emits adjacent objects, each starting with an event key.
    $events = json_decode('[' . preg_replace('/}\s*{\s*"event"\s*:/', '},{"event":', $raw) . ']',
        true, 512, JSON_THROW_ON_ERROR);
    foreach ($events as $event) {
        if ($event['event'] !== 'test' || strpos($event['message'] ?? '', 'Skipped Test: ') !== 0) {
            continue;
        }
        [$class, $name] = explode('::', $event['test'], 2);
        $parsed['cases'][] = ['id' => null, 'name' => $name, 'class' => $class, 'status' => 'Skipped',
            'failure_reason' => substr($event['message'], strlen('Skipped Test: ')),
            'assertions' => 0, 'seconds' => $event['time'], 'source' => 'upstream-events.json'];
        ++$parsed['counts']['total'];
        ++$parsed['counts']['skipped'];
    }
    return $parsed;
}
