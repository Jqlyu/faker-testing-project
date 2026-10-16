<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/case-catalog.php';
$positionals = [];
$contextKind = 'github-actions';
foreach (array_slice($argv, 1) as $argument) {
    if (strpos($argument, '--context=') === 0) { $contextKind = substr($argument, 10); }
    elseif (strpos($argument, '--') === 0) { fwrite(STDERR, 'Unknown option: ' . $argument . PHP_EOL); exit(2); }
    else { $positionals[] = $argument; }
}
if (count($positionals) > 2 || !in_array($contextKind, ['github-actions', 'local-device'], true)) {
    fwrite(STDERR, "Usage: php scripts/compare-results.php [INPUT OUTPUT] [--context=github-actions|local-device]\n"); exit(2);
}
$input = $positionals[0] ?? projectRoot() . '/results/imported';
$output = $positionals[1] ?? projectRoot() . '/results/comparison';
if (!is_dir($output) && !mkdir($output, 0777, true) && !is_dir($output)) {
    fwrite(STDERR, 'Cannot create comparison directory: ' . $output . PHP_EOL); exit(2);
}
if (file_exists($output . '/comparison.json') || file_exists($output . '/comparison.md')) {
    fwrite(STDERR, 'Comparison evidence already exists; select a new output directory: ' . $output . PHP_EOL); exit(2);
}
$prefix = $contextKind === 'github-actions' ? 'github-' : 'local-';
$required = array_map(static fn(string $os): string => $prefix . $os, ['Windows', 'Darwin', 'Linux']);
$comparison = ['schema_version' => 2, 'generated_at_utc' => gmdate('c'), 'status' => 'Not Run',
    'required_execution_context' => $contextKind, 'required_platform_keys' => $required,
    'reasons' => [], 'ignored_sources' => [], 'platform_results' => [], 'case_results' => [], 'sources' => [],
    'statistics' => ['designed_report_cases' => 24, 'required_platform_executions' => 3,
        'available_platform_executions' => 0, 'required_case_executions' => 72, 'available_case_executions' => 0],
    'comparison_policy' => 'Compare current report IDs/statuses, raw evidence and controlled versions within one execution context. Local Windows and GitHub Windows CI are separate. Random samples, assertion totals and performance values need not be equal across platforms. No performance acceptance criterion is defined.'];
foreach (['local-Windows', 'local-Darwin', 'local-Linux', 'github-Windows', 'github-Darwin', 'github-Linux'] as $key) {
    $comparison['platform_results'][$key] = ['status' => 'Not Run', 'actual' => 'No current full report evidence supplied',
        'required' => in_array($key, $required, true), 'versions' => null, 'counts' => null];
}
$runs = [];
$expectedIds = systemCaseIds();
$sortedExpected = $expectedIds; sort($sortedExpected);
function comparisonJson(string $path): array
{
    if (!is_file($path)) { throw new RuntimeException('Required evidence file missing: ' . $path); }
    $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException('Expected an evidence object: ' . $path); }
    return $data;
}

try {
    if (!is_dir($input)) { throw new RuntimeException('Input directory missing: supply actual full report run directories.'); }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getFilename() !== 'summary.json') { continue; }
        $summary = comparisonJson($file->getPathname());
        if (($summary['schema_version'] ?? null) !== 2 || ($summary['suite'] ?? null) !== 'report' || ($summary['full_report_run'] ?? false) !== true) {
            $comparison['ignored_sources'][] = ['summary' => $file->getPathname(), 'reason' => 'Legacy, filtered, supplemental or upstream result; not a current full report execution'];
            continue;
        }
        $directory = $file->getPath();
        $environment = comparisonJson($directory . '/environment.json');
        $context = $environment['execution_context'] ?? [];
        $kind = $context['kind'] ?? ''; $key = $context['platform_key'] ?? ''; $os = $environment['os_family'] ?? '';
        $expectedKey = ($kind === 'github-actions' ? 'github-' : 'local-') . $os;
        if (!in_array($kind, ['github-actions', 'local-device'], true) || !isset($comparison['platform_results'][$key]) ||
            $key !== $expectedKey || ($environment['capture_complete'] ?? false) !== true) {
            throw new RuntimeException('Invalid/incomplete execution context in ' . $file->getPathname());
        }
        if (isset($runs[$key])) { throw new RuntimeException('Multiple full runs for ' . $key . '; select exactly one run per context.'); }
        if ($kind === 'github-actions' && (empty($environment['github']['run_id']) || empty($environment['github']['sha']) || empty($environment['github']['runner_os']))) {
            throw new RuntimeException('GitHub CI context lacks workflow run/commit/runner provenance: ' . $key);
        }
        $parsed = readJunit($directory . '/junit.xml');
        if ($parsed['cases'] !== ($summary['cases'] ?? null) || $parsed['counts'] !== ($summary['counts'] ?? null)) {
            throw new RuntimeException('Summary and native JUnit disagree for ' . $key);
        }
        $ids = array_column($parsed['cases'], 'id'); sort($ids);
        $selected = $summary['selected_case_ids'] ?? []; sort($selected);
        if ($ids !== $sortedExpected || $selected !== $sortedExpected) { throw new RuntimeException('Full report requires each F01-F22/N01-N02 exactly once: ' . $key); }
        foreach ($parsed['cases'] as $case) {
            if ($case['status'] !== 'Passed') { continue; }
            $id = $case['id']; $observation = comparisonJson($directory . '/observations/' . $id . '.json');
            if (($observation['case_id'] ?? null) !== $id || !is_array($observation['data'] ?? null) || $observation !== ($summary['observations'][$id] ?? null)) {
                throw new RuntimeException('Current observation and summary disagree for ' . $key . '/' . $id);
            }
            if (in_array($id, ['N01', 'N02'], true)) {
                $measurement = comparisonJson($directory . '/measurements/' . $id . '.json');
                validateBenchmarkMeasurement($measurement, $id);
                if ($measurement !== ($summary['measurements'][$id] ?? null) || $measurement !== $observation['data']) {
                    throw new RuntimeException('Measurement/observation/summary disagree for ' . $key . '/' . $id);
                }
            }
        }
        $passed = $summary['status'] === 'Passed' && $summary['exit_code'] === 0 && $summary['phpunit_exit_code'] === 0 && $parsed['counts']['passed'] === 24;
        if ($passed) {
            $expectedStatistics = ['designed_report_cases' => 24, 'selected_report_cases' => 24, 'executed_report_cases' => 24,
                'phpunit_test_methods' => 24, 'parent_phpunit_assertions' => array_sum(array_column($parsed['cases'], 'assertions')),
                'benchmark_worker_assertions' => 54000, 'platform_executions_in_this_run' => 1];
            foreach ($expectedStatistics as $field => $value) {
                if (($summary['statistics'][$field] ?? null) !== $value) { throw new RuntimeException('Summary statistics disagree with current evidence: ' . $key . '/' . $field); }
            }
        }
        $comparison['platform_results'][$key] = ['status' => $passed ? 'Passed' : 'Failed',
            'actual' => ($context['label'] ?? $key) . ': ' . $parsed['counts']['passed'] . '/24 passed; exit ' . $summary['exit_code'],
            'required' => in_array($key, $required, true), 'versions' => ['php' => $environment['php_version'] ?? null,
                'phpunit' => $environment['phpunit_version'] ?? null, 'faker' => $environment['faker'] ?? null,
                'os_version' => $environment['os_version'] ?? null, 'architecture' => $environment['architecture'] ?? null],
            'counts' => $parsed['counts'], 'measurements' => $summary['measurements'], 'statistics' => $summary['statistics'], 'github' => $environment['github'] ?? null];
        $runs[$key] = ['summary' => $summary, 'environment' => $environment];
        $comparison['sources'][$key] = ['run_id' => $summary['run_id'], 'summary' => $file->getPathname(), 'junit_sha256' => hash_file('sha256', $directory . '/junit.xml')];
    }
    foreach ($required as $key) {
        if (!isset($runs[$key])) { $comparison['reasons'][] = 'Missing ' . $key . ' execution evidence'; }
        elseif ($comparison['platform_results'][$key]['status'] !== 'Passed') { $comparison['reasons'][] = $key . ' did not pass all 24 report cases'; }
    }
    $available = array_intersect($required, array_keys($runs));
    $comparison['statistics']['available_platform_executions'] = count($available);
    $comparison['statistics']['available_case_executions'] = count($available) * 24;
    if ($available !== []) {
        $baselineKey = reset($available); $baseline = $runs[$baselineKey]['environment'];
        $controls = ['php_version', 'phpunit_version', 'composer_lock_sha256', 'test_contract_sha256', 'php_int_size', 'case_seeds', 'case_locales', 'timezone', 'locale_setting', 'precision', 'serialize_precision', 'test_memory_limit', 'faker', 'dependencies', 'report_design_source'];
        foreach ($available as $key) {
            $environment = $runs[$key]['environment'];
            if (($environment['php_version'] ?? null) !== '7.4.33' ||
                !preg_match('/^PHPUnit 9\.6\.38(?:\s|$)/', $environment['phpunit_version'] ?? '') || ($environment['faker']['package'] ?? null) !== 'fzaninotto/faker' || ($environment['faker']['commit'] ?? null) !== FAKER_COMMIT) {
                $comparison['reasons'][] = $key . ' does not use the required PHP/PHPUnit/Faker baseline';
            }
            foreach ($controls as $field) {
                if (!array_key_exists($field, $environment) || !array_key_exists($field, $baseline) || $environment[$field] !== $baseline[$field]) { $comparison['reasons'][] = $key . ' differs in controlled field ' . $field; }
            }
            if ($contextKind === 'github-actions' && (($environment['github']['run_id'] ?? null) !== ($baseline['github']['run_id'] ?? null) || ($environment['github']['sha'] ?? null) !== ($baseline['github']['sha'] ?? null))) { $comparison['reasons'][] = $key . ' belongs to a different GitHub workflow run or commit'; }
            $identities = static function (array $cases): array {
                $values = [];
                foreach ($cases as $case) { $values[$case['id']] = [$case['name'], $case['class'], $case['status']]; }
                ksort($values); return $values;
            };
            if ($identities($runs[$key]['summary']['cases']) !== $identities($runs[$baselineKey]['summary']['cases'])) { $comparison['reasons'][] = $key . ' has different test identities/statuses'; }
        }
    }
    $nonMissingReasons = array_filter($comparison['reasons'], static fn(string $reason): bool => strpos($reason, 'Missing ') !== 0);
    $comparison['status'] = $nonMissingReasons !== [] ? 'Failed' : (count($available) !== 3 ? 'Not Run' : 'Passed');
} catch (Throwable $error) {
    $comparison['status'] = 'Error'; $comparison['reasons'][] = $error->getMessage();
}
foreach ($expectedIds as $id) {
    foreach ($comparison['platform_results'] as $key => $platform) {
        $result = 'Not Run';
        foreach ($runs[$key]['summary']['cases'] ?? [] as $case) { if ($case['id'] === $id) { $result = $case['status']; break; } }
        $comparison['case_results'][$id][$key] = $result;
    }
}
writeJson($output . '/comparison.json', $comparison);
$markdown = "# Current report cross-platform comparison\n\nStatus: **" . $comparison['status'] . "**; required context: " . $contextKind . ". Comparison is evidence analysis, not an additional report test case.\n\n";
$markdown .= "| Execution context | Actual result | Status |\n|---|---|---|\n";
foreach ($comparison['platform_results'] as $key => $platform) { $markdown .= '| ' . $key . ' | ' . markdownCell($platform['actual']) . ' | ' . $platform['status'] . " |\n"; }
$markdown .= "\n| ID | Local Windows | Local macOS | Local Linux | GitHub Windows CI | GitHub macOS CI | GitHub Linux CI |\n|---|---|---|---|---|---|---|\n";
foreach ($comparison['case_results'] as $id => $platforms) { $markdown .= '| ' . $id . ' | ' . implode(' | ', $platforms) . " |\n"; }
foreach ($comparison['reasons'] as $reason) { $markdown .= "\n- " . $reason; }
$markdown .= "\n\nStatistics: `" . json_encode($comparison['statistics']) . "`\n\nNo performance acceptance criterion was assessed. Missing evidence is not a pass.\n";
file_put_contents($output . '/comparison.md', $markdown);
echo $markdown . "\nEvidence directory: " . realpath($output) . PHP_EOL;
exit($comparison['status'] === 'Passed' ? 0 : 1);
