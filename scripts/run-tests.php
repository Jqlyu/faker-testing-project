<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/case-catalog.php';
date_default_timezone_set('UTC');
$root = projectRoot();
$suite = 'system';
$filter = null;
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--suite=') === 0) {
        $suite = substr($argument, 8);
    } elseif (strpos($argument, '--filter=') === 0) {
        $filter = substr($argument, 9);
    } else {
        fwrite(STDERR, "Usage: php scripts/run-tests.php [--suite=system|upstream] [--filter=TC01]\n");
        exit(2);
    }
}
if (!in_array($suite, ['system', 'upstream'], true) || $filter === '') {
    fwrite(STDERR, "Invalid suite or empty filter.\n");
    exit(2);
}
$runId = gmdate('Ymd\THis\Z') . '-' . strtolower(PHP_OS_FAMILY) . '-' . $suite . '-' . bin2hex(random_bytes(3));
$directory = $root . '/results/' . $runId;
mkdir($directory, 0777, true);
$environment = ['captured_at_utc' => gmdate('c'), 'os_family' => PHP_OS_FAMILY,
    'os_version' => php_uname('r'), 'architecture' => php_uname('m'), 'php_version' => PHP_VERSION,
    'phpunit_version' => null, 'composer_version' => null, 'faker' => null,
    'capture_complete' => false, 'unknown_fields' => 'null means unavailable; setup may have failed'];
writeJson($directory . '/environment.json', $environment);
$start = microtime(true);
$summary = ['schema_version' => 1, 'run_id' => $runId, 'suite' => $suite,
    'filter' => $filter, 'started_at_utc' => gmdate('c'), 'status' => 'Error',
    'exit_code' => 2, 'phpunit_exit_code' => null, 'counts' => null,
    'cases' => [], 'report_error' => null];
try {
    if (!is_file($root . '/vendor/autoload.php')) {
        throw new RuntimeException('Dependencies missing. Run php tools/composer.phar install first.');
    }
    require $root . '/vendor/autoload.php';
    $lock = json_decode(file_get_contents($root . '/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    $dependencies = [];
    foreach (array_merge($lock['packages'], $lock['packages-dev']) as $package) {
        $dependencies[$package['name']] = ['version' => $package['version'],
            'reference' => $package['source']['reference'] ?? $package['dist']['reference'] ?? null];
    }
    ksort($dependencies);
    if (($dependencies['fzaninotto/faker']['reference'] ?? '') !== FAKER_COMMIT) {
        throw new RuntimeException('composer.lock does not point to the pinned Faker commit.');
    }
    $installedCommit = Composer\InstalledVersions::getReference('fzaninotto/faker');
    if ($installedCommit !== FAKER_COMMIT) {
        throw new RuntimeException('Installed Faker reference differs from composer.lock; reinstall dependencies.');
    }
    $composer = is_file($root . '/tools/composer.phar')
        ? optionalCommand([PHP_BINARY, $root . '/tools/composer.phar', '--version', '--no-ansi'], $root)
        : optionalCommand(['composer', '--version', '--no-ansi'], $root);
    $extensions = [];
    foreach (get_loaded_extensions() as $extension) {
        $extensions[$extension] = phpversion($extension) ?: null;
    }
    ksort($extensions);
    $phpunit = $suite === 'system' ? $root . '/vendor/phpunit/phpunit/phpunit'
        : $root . '/tools/phpunit-5.7.27.phar';
    $environment = ['captured_at_utc' => gmdate('c'), 'capture_complete' => true, 'os_family' => PHP_OS_FAMILY,
        'os_name' => php_uname('s'), 'os_version' => php_uname('r'), 'os_detail' => php_uname('v'),
        'product_version' => PHP_OS_FAMILY === 'Darwin' ? optionalCommand(['sw_vers', '-productVersion'], $root) : null,
        'architecture' => php_uname('m'), 'php_version' => PHP_VERSION, 'php_binary' => PHP_BINARY,
        'php_int_size' => PHP_INT_SIZE, 'php_ini' => php_ini_loaded_file() ?: null,
        'php_extensions' => $extensions, 'composer_version' => $composer,
        'phpunit_version' => optionalCommand([PHP_BINARY, $phpunit, '--version'], $root),
        'faker' => ['package' => 'fzaninotto/faker', 'version' => '1.9.2', 'commit' => $installedCommit,
            'src_sha256' => treeHash($root . '/vendor/fzaninotto/faker/src')],
        'dependencies' => $dependencies, 'composer_lock_sha256' => hash_file('sha256', $root . '/composer.lock'),
        'test_contract_sha256' => contractHash(), 'seed' => 4160, 'timezone' => 'UTC',
        'locale' => 'en_US; TC14=fi_FI', 'locale_setting' => 'C', 'precision' => 14, 'serialize_precision' => -1,
        'test_memory_limit' => $suite === 'system' ? '256M' : '512M',
        'project_commit' => optionalCommand(['git', 'rev-parse', 'HEAD'], $root),
        'project_worktree_status' => optionalCommand(['git', 'status', '--porcelain'], $root),
        'github' => ['run_id' => getenv('GITHUB_RUN_ID') ?: null, 'run_attempt' => getenv('GITHUB_RUN_ATTEMPT') ?: null,
            'sha' => getenv('GITHUB_SHA') ?: null, 'runner_os' => getenv('RUNNER_OS') ?: null,
            'runner_image' => getenv('ImageOS') ?: null, 'runner_image_version' => getenv('ImageVersion') ?: null],
        'unknown_fields' => 'null means unavailable or not applicable; no inferred version values'];
    writeJson($directory . '/environment.json', $environment);
    $sut = json_decode(file_get_contents($root . '/sut.json'), true, 512, JSON_THROW_ON_ERROR);
    if ($environment['faker']['src_sha256'] !== $sut['source_tree_sha256']) {
        throw new RuntimeException('Installed Faker source differs from the verified upstream release. Reinstall from composer.lock.');
    }
    if (!is_file($phpunit)) {
        throw new RuntimeException('PHPUnit missing. For upstream run php scripts/download-tools.php first.');
    }
    if ($suite === 'upstream') {
        $clone = $root . '/upstream/faker';
        if (optionalCommand(['git', 'rev-parse', 'HEAD'], $clone) !== FAKER_COMMIT ||
            optionalCommand(['git', 'status', '--porcelain', '--untracked-files=no'], $clone) !== '') {
            throw new RuntimeException('Need a clean upstream/faker checkout at pinned commit. Run php scripts/fetch-upstream.php.');
        }
        if (treeHash($clone . '/src') !== $environment['faker']['src_sha256']) {
            throw new RuntimeException('Cloned and installed Faker source trees differ.');
        }
        // Large realText provider fixtures exceed PHP's default 128 MB limit.
        $arguments = [PHP_BINARY, '-d', 'memory_limit=512M', $phpunit, '--no-configuration', '--bootstrap',
            $root . '/tests/upstream-bootstrap.php', '--colors=never', '--log-junit',
            $directory . '/junit.xml', '--log-json', $directory . '/upstream-events.json', $clone . '/test/Faker'];
    } else {
        $arguments = [PHP_BINARY, $phpunit, '--configuration', $root . '/phpunit.xml',
            '--colors=never', '--testdox', '--log-junit', $directory . '/junit.xml'];
    }
    if ($filter !== null) {
        $arguments[] = '--filter';
        $arguments[] = $filter;
    }
    $environment['execution_arguments'] = $arguments;
    writeJson($directory . '/environment.json', $environment);
    putenv('FAKER_RESULTS_DIR=' . $directory);
    echo "Evidence directory: results/$runId\n";
    $execution = command($arguments, $root, $directory . '/phpunit.log');
    $summary['phpunit_exit_code'] = $execution['exit_code'];
    $summary['exit_code'] = $execution['exit_code'];
    $parsed = readJunit($directory . '/junit.xml');
    if ($suite === 'upstream') {
        $parsed = includeUpstreamSkippedCases($parsed, $directory . '/upstream-events.json');
        $summary['counts_source'] = 'JUnit plus PHPUnit 5 native JSON skipped-case events';
    } else {
        $summary['counts_source'] = 'JUnit';
    }
    $summary['counts'] = $parsed['counts'];
    $summary['cases'] = $parsed['cases'];
    if ($suite === 'system' && $filter === null) {
        $ids = array_column($parsed['cases'], 'id');
        $expected = systemCaseIds();
        sort($expected);
        sort($ids);
        if ($ids !== $expected) {
            throw new RuntimeException('Full system execution must contain each system scenario registered in case-catalog.php exactly once.');
        }
    }
    $summary['status'] = $execution['exit_code'] === 0 && $parsed['counts']['failed'] === 0 &&
        $parsed['counts']['errors'] === 0 ? 'Passed' : 'Failed';
    if ($summary['status'] !== 'Passed' && $summary['exit_code'] === 0) {
        $summary['exit_code'] = 1;
    }
} catch (Throwable $error) {
    $summary['report_error'] = $error->getMessage();
    $summary['status'] = 'Error';
    $summary['exit_code'] = 2;
    file_put_contents($directory . '/runner-error.log', (string) $error . PHP_EOL);
    fwrite(STDERR, 'Runner error: ' . $error->getMessage() . PHP_EOL);
}
$summary['finished_at_utc'] = gmdate('c');
$summary['elapsed_seconds'] = microtime(true) - $start;
writeJson($directory . '/summary.json', $summary);
if ($suite === 'system' && isset($environment)) {
    $markdown = renderCaseTable($summary, $environment);
    file_put_contents($directory . '/TEST_CASES.md', $markdown);
    if ($filter === null) {
        file_put_contents($root . '/docs/TEST_CASES.md', $markdown);
    }
}
echo 'Status: ' . $summary['status'] . '; ' . json_encode($summary['counts']) . PHP_EOL;
exit($summary['exit_code']);
