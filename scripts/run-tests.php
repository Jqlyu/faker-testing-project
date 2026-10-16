<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/case-catalog.php';
date_default_timezone_set('UTC');
$root = projectRoot();
$suite = 'report';
$filter = $tester = $caseList = null;
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--suite=') === 0) { $suite = substr($argument, 8); }
    elseif (strpos($argument, '--filter=') === 0) { $filter = substr($argument, 9); }
    elseif (strpos($argument, '--tester=') === 0) { $tester = substr($argument, 9); }
    elseif (strpos($argument, '--cases=') === 0) { $caseList = substr($argument, 8); }
    else { fwrite(STDERR, "Unknown option: $argument\n"); exit(2); }
}
if ($suite === 'system') { $suite = 'report'; }
$selected = [];
try {
    if (!in_array($suite, ['report', 'supplemental', 'upstream'], true)) {
        throw new InvalidArgumentException('Suite must be report, supplemental or upstream.');
    }
    if (count(array_filter([$filter, $tester, $caseList], static fn($v): bool => $v !== null)) > 1) {
        throw new InvalidArgumentException('Choose only one of --filter, --tester and --cases.');
    }
    if ($suite === 'report') {
        $selected = systemCaseIds();
        if ($filter !== null) { $selected = [strtoupper(trim($filter))]; }
        elseif ($tester !== null) { $selected = testerCaseIds($tester); }
        elseif ($caseList !== null) { $selected = array_map('strtoupper', array_map('trim', explode(',', $caseList))); }
        if ($selected === [] || count($selected) !== count(array_unique($selected)) || array_diff($selected, systemCaseIds()) !== []) {
            throw new InvalidArgumentException('Unknown/empty/duplicate report IDs or tester. Use F01-F22/N01-N02; --tester="Jiaqing Lyu" selects six cases.');
        }
    } elseif ($tester !== null || $caseList !== null || $filter === '') {
        throw new InvalidArgumentException('Tester/case-list selection applies only to the report suite; filter cannot be empty.');
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\nUsage: php scripts/run-tests.php [--filter=F13 | --tester=\"Jiaqing Lyu\" | --cases=F13,F14,F15,F16,N01,N02] [--suite=report|supplemental|upstream]\n");
    exit(2);
}
$runId = gmdate('Ymd\THis\Z') . '-' . strtolower(PHP_OS_FAMILY) . '-' . $suite . '-' . bin2hex(random_bytes(3));
$directory = $root . '/results/' . $runId;
mkdir($directory, 0777, true);
$ci = getenv('GITHUB_ACTIONS') === 'true';
$kind = $ci ? 'github-actions' : 'local-device';
$osLabel = ['Darwin' => 'macOS', 'Windows' => 'Windows', 'Linux' => 'Linux'][PHP_OS_FAMILY] ?? PHP_OS_FAMILY;
$context = ['kind' => $kind, 'platform_key' => ($ci ? 'github-' : 'local-') . PHP_OS_FAMILY,
    'label' => ($ci ? 'GitHub ' : 'Local ') . $osLabel . ($ci ? ' CI' : ''),
    'reported_historical_platform' => false];
$environment = ['captured_at_utc' => gmdate('c'), 'os_family' => PHP_OS_FAMILY,
    'os_version' => php_uname('r'), 'architecture' => php_uname('m'), 'php_version' => PHP_VERSION,
    'execution_context' => $context, 'capture_complete' => false];
writeJson($directory . '/environment.json', $environment);
$start = microtime(true);
$summary = ['schema_version' => 2, 'run_id' => $runId, 'suite' => $suite,
    'filter' => $filter, 'tester_selection' => $tester, 'selected_case_ids' => $selected,
    'full_report_run' => $suite === 'report' && count($selected) === count(systemCaseIds()),
    'started_at_utc' => gmdate('c'), 'status' => 'Error', 'exit_code' => 2, 'phpunit_exit_code' => null,
    'counts' => null, 'cases' => [], 'observations' => [], 'measurements' => [], 'report_error' => null,
    'statistics' => ['designed_report_cases' => 24, 'selected_report_cases' => count($selected),
        'executed_report_cases' => 0, 'phpunit_test_methods' => 0, 'parent_phpunit_assertions' => 0,
        'benchmark_worker_assertions' => 0, 'platform_executions_in_this_run' => 1]];
echo 'Evidence directory: ' . $directory . PHP_EOL;
try {
    if (!is_file($root . '/vendor/autoload.php')) { throw new RuntimeException('Dependencies missing. Run php scripts/install.php.'); }
    require $root . '/vendor/autoload.php';
    if (PHP_VERSION !== '7.4.33') { throw new RuntimeException('This report baseline requires actual PHP 7.4.33; found ' . PHP_VERSION); }
    $lock = json_decode(file_get_contents($root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    $dependencies = [];
    foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
        $dependencies[$package['name']] = ['version' => $package['version'],
            'reference' => $package['source']['reference'] ?? $package['dist']['reference'] ?? null];
    }
    ksort($dependencies);
    $installedCommit = Composer\InstalledVersions::getReference('fzaninotto/faker');
    if (($dependencies['fzaninotto/faker']['reference'] ?? '') !== FAKER_COMMIT || $installedCommit !== FAKER_COMMIT) {
        throw new RuntimeException('Installed/locked Faker must be the pinned original v1.9.2 commit.');
    }
    $composer = is_file($root . '/tools/composer.phar')
        ? optionalCommand([PHP_BINARY, $root . '/tools/composer.phar', '--version', '--no-ansi'], $root)
        : optionalCommand(['composer', '--version', '--no-ansi'], $root);
    $extensions = [];
    foreach (get_loaded_extensions() as $extension) { $extensions[$extension] = phpversion($extension) ?: null; }
    ksort($extensions);
    $phpunit = $suite === 'upstream' ? $root . '/tools/phpunit-5.7.27.phar' : $root . '/vendor/phpunit/phpunit/phpunit';
    $environment = array_merge($environment, ['capture_complete' => true,
        'os_name' => php_uname('s'), 'os_detail' => php_uname('v'),
        'product_version' => PHP_OS_FAMILY === 'Darwin' ? optionalCommand(['sw_vers', '-productVersion'], $root) : null,
        'php_binary' => PHP_BINARY, 'php_int_size' => PHP_INT_SIZE, 'php_ini' => php_ini_loaded_file() ?: null,
        'php_extensions' => $extensions, 'composer_version' => $composer,
        'phpunit_version' => optionalCommand([PHP_BINARY, $phpunit, '--version'], $root),
        'faker' => ['package' => 'fzaninotto/faker', 'version' => '1.9.2', 'commit' => $installedCommit,
            'src_sha256' => treeHash($root . '/vendor/fzaninotto/faker/src')],
        'dependencies' => $dependencies, 'composer_lock_sha256' => hash_file('sha256', $root . '/composer.lock'),
        'test_contract_sha256' => contractHash(), 'report_design_source' => json_decode(file_get_contents($root . '/docs/REPORT_SOURCE.json'), true),
        'case_seeds' => array_map(static fn(array $case): int => $case['seed'], caseCatalog()),
        'case_locales' => array_map(static fn(array $case): string => $case['locale'], caseCatalog()),
        'timezone' => 'UTC', 'locale_setting' => 'C', 'precision' => 14, 'serialize_precision' => -1,
        'test_memory_limit' => $suite === 'upstream' ? '512M' : '256M',
        'benchmark_memory_scope' => 'Fresh PHP CLI worker with Composer and PHPUnit assertion classes; not OS process RSS or Faker-exclusive memory',
        'project_commit' => optionalCommand(['git', 'rev-parse', 'HEAD'], $root),
        'project_worktree_status' => optionalCommand(['git', 'status', '--porcelain'], $root),
        'github' => ['run_id' => getenv('GITHUB_RUN_ID') ?: null, 'run_attempt' => getenv('GITHUB_RUN_ATTEMPT') ?: null,
            'sha' => getenv('GITHUB_SHA') ?: null, 'runner_os' => getenv('RUNNER_OS') ?: null,
            'runner_image' => getenv('ImageOS') ?: null, 'runner_image_version' => getenv('ImageVersion') ?: null],
        'hardware' => ['architecture' => php_uname('m'), 'cpu_model' => null, 'total_ram_bytes' => null],
        'unknown_fields' => 'null means unavailable/not applicable; no historical environment values are substituted']);
    writeJson($directory . '/environment.json', $environment);
    if ($suite !== 'upstream' && !preg_match('/^PHPUnit 9\\.6\\.38(?:\\s|$)/', $environment['phpunit_version'] ?? '')) {
        throw new RuntimeException('The report/supplemental runner must be PHPUnit 9.6.38.');
    }
    $sut = json_decode(file_get_contents($root . '/sut.json'), true, 512, JSON_THROW_ON_ERROR);
    if ($environment['faker']['src_sha256'] !== $sut['source_tree_sha256']) { throw new RuntimeException('Installed Faker source differs from the clean pinned release.'); }
    if (!is_file($phpunit)) { throw new RuntimeException('PHPUnit missing; run scripts/download-tools.php for the upstream runner.'); }
    if ($suite === 'upstream') {
        $clone = $root . '/upstream/faker';
        if (optionalCommand(['git', 'rev-parse', 'HEAD'], $clone) !== FAKER_COMMIT ||
            optionalCommand(['git', 'status', '--porcelain', '--untracked-files=no'], $clone) !== '' ||
            treeHash($clone . '/src') !== $environment['faker']['src_sha256']) {
            throw new RuntimeException('Need a clean, matching upstream checkout; run scripts/fetch-upstream.php.');
        }
        $arguments = [PHP_BINARY, '-d', 'memory_limit=512M', $phpunit, '--no-configuration', '--bootstrap',
            $root . '/tests/upstream-bootstrap.php', '--colors=never', '--log-junit', $directory . '/junit.xml',
            '--log-json', $directory . '/upstream-events.json', $clone . '/test/Faker'];
        if ($filter !== null) { $arguments[] = '--filter'; $arguments[] = $filter; }
    } else {
        $configuration = $suite === 'report' ? 'phpunit.xml' : 'phpunit-supplemental.xml';
        $arguments = [PHP_BINARY, $phpunit, '--configuration', $root . '/' . $configuration,
            '--colors=never', '--log-junit', $directory . '/junit.xml'];
        if ($suite === 'report' && count($selected) !== 24) {
            $arguments[] = '--filter';
            $arguments[] = '(?:^|::)test(?:' . implode('|', $selected) . ')(?=[A-Z_]|$)';
        } elseif ($suite === 'supplemental' && $filter !== null) {
            $arguments[] = '--filter'; $arguments[] = $filter;
        }
    }
    $environment['execution_arguments'] = $arguments;
    writeJson($directory . '/environment.json', $environment);
    putenv('FAKER_RESULTS_DIR=' . $directory);
    $execution = command($arguments, $root, $directory . '/phpunit.log');
    $summary['phpunit_exit_code'] = $summary['exit_code'] = $execution['exit_code'];
    $parsed = readJunit($directory . '/junit.xml');
    if ($suite === 'upstream') { $parsed = includeUpstreamSkippedCases($parsed, $directory . '/upstream-events.json'); }
    $summary['counts'] = $parsed['counts']; $summary['cases'] = $parsed['cases'];
    $summary['counts_source'] = $suite === 'upstream' ? 'JUnit plus native legacy JSON skipped events' : 'Native PHPUnit JUnit';
    $summary['statistics']['phpunit_test_methods'] = $parsed['counts']['total'];
    $summary['statistics']['parent_phpunit_assertions'] = array_sum(array_column($parsed['cases'], 'assertions'));
    if ($suite === 'report') {
        $ids = array_column($parsed['cases'], 'id'); $expected = $selected;
        sort($ids); sort($expected);
        $summary['statistics']['executed_report_cases'] = count(array_unique(array_filter($ids)));
        if ($ids !== $expected) { throw new RuntimeException('Selected report IDs must each execute exactly once; missing/extra/duplicate IDs found.'); }
        foreach ($parsed['cases'] as $case) {
            $id = $case['id']; $path = $directory . '/observations/' . $id . '.json';
            if (is_file($path)) {
                $observation = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($observation) || ($observation['case_id'] ?? null) !== $id || !is_array($observation['data'] ?? null) || $observation['data'] === []) {
                    throw new RuntimeException('Malformed current observation evidence: ' . $id);
                }
                $summary['observations'][$id] = $observation;
            }
            elseif ($case['status'] === 'Passed') { throw new RuntimeException('Passed case lacks current observation evidence: ' . $id); }
            if (in_array($id, ['N01', 'N02'], true)) {
                $measurement = $directory . '/measurements/' . $id . '.json';
                if (is_file($measurement)) {
                    $data = json_decode(file_get_contents($measurement), true, 512, JSON_THROW_ON_ERROR);
                    if (!is_array($data)) { throw new RuntimeException('Malformed measurement evidence: ' . $id); }
                    validateBenchmarkMeasurement($data, $id);
                    if ($case['status'] === 'Passed' && $data !== ($summary['observations'][$id]['data'] ?? null)) {
                        throw new RuntimeException('Measurement and observation disagree: ' . $id);
                    }
                    $summary['measurements'][$id] = $data;
                    $summary['statistics']['benchmark_worker_assertions'] += $summary['measurements'][$id]['worker_validation_assertions'] ?? 0;
                } elseif ($case['status'] === 'Passed') { throw new RuntimeException('Passed benchmark lacks a measurement file: ' . $id); }
            }
        }
    }
    $summary['status'] = $execution['exit_code'] === 0 && $parsed['counts']['failed'] === 0 &&
        $parsed['counts']['errors'] === 0 && ($suite !== 'report' || $parsed['counts']['skipped'] === 0) ? 'Passed' : 'Failed';
    if ($summary['status'] !== 'Passed' && $summary['exit_code'] === 0) { $summary['exit_code'] = 1; }
} catch (Throwable $error) {
    $summary['report_error'] = $error->getMessage(); $summary['status'] = 'Error'; $summary['exit_code'] = 2;
    file_put_contents($directory . '/runner-error.log', (string) $error . PHP_EOL);
    fwrite(STDERR, 'Runner error: ' . $error->getMessage() . PHP_EOL);
}
$summary['finished_at_utc'] = gmdate('c'); $summary['elapsed_seconds'] = microtime(true) - $start;
writeJson($directory . '/summary.json', $summary);
$console = "\n================ CURRENT EXECUTION ================\n" . $context['label'] .
    ' | PHP ' . PHP_VERSION . ' | Faker1.9.2 | ' . $summary['started_at_utc'] . "\n";
if ($suite === 'report') {
    $byId = [];
    foreach ($summary['cases'] as $case) { $byId[$case['id']] = $case; }
    foreach ($selected as $id) {
        $design = caseCatalog()[$id]; $case = $byId[$id] ?? null;
        $console .= "\n" . $id . ' | ' . $design['name'] . ' | ' . ($case['status'] ?? 'Not Run') .
            ' | parent PHPUnit assertions=' . ($case['assertions'] ?? 0) . "\n  Inputs: " . $design['inputs'] . "\n";
        if ($case !== null) { $console .= '  Actual: ' . ($case['failure_reason'] ?? observedOutput($id, $summary['observations'][$id] ?? [])) . "\n"; }
    }
    $markdown = renderCaseTable($summary, $environment);
    file_put_contents($directory . '/REPORT_TABLE.md', $markdown);
    file_put_contents($directory . '/TEST_CASES.md', $markdown);
    if ($summary['full_report_run']) { file_put_contents($root . '/docs/TEST_CASES.md', $markdown); }
}
$console .= "\nRun status: " . $summary['status'] . '; method counts: ' . json_encode($summary['counts']) . "\n";
$console .= 'Separate statistics: ' . json_encode($summary['statistics']) . "\n";
if ($summary['report_error'] !== null) { $console .= 'Error: ' . $summary['report_error'] . "\n"; }
$console .= 'Full evidence directory: ' . $directory . "\n===================================================\n";
file_put_contents($directory . '/console.log', $console);
echo $console;
exit($summary['exit_code']);
