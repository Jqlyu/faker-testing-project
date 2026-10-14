<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/case-catalog.php';
$input = $argv[1] ?? projectRoot() . '/results/imported';
$output = $argv[2] ?? projectRoot() . '/results/comparison';
if (!is_dir($output)) {
    mkdir($output, 0777, true);
}
$comparison = ['schema_version' => 1, 'case_id' => 'TC24', 'generated_at_utc' => gmdate('c'),
    'status' => 'Not Run', 'reasons' => [], 'platform_cases' => [], 'sources' => [],
    'comparison_policy' => 'Compare case identities/statuses and controlled versions; do not compare random rows or performance times.'];
$runs = [];
try {
    if (!is_dir($input)) {
        throw new RuntimeException('Input directory missing: download and extract three OS artifacts first.');
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($input, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getFilename() !== 'summary.json') {
            continue;
        }
        $summary = json_decode(file_get_contents($file->getPathname()), true, 512, JSON_THROW_ON_ERROR);
        if ($summary['suite'] !== 'system' || $summary['filter'] !== null) {
            continue;
        }
        $directory = $file->getPath();
        $environment = json_decode(file_get_contents($directory . '/environment.json'), true, 512, JSON_THROW_ON_ERROR);
        $os = $environment['os_family'];
        if (!in_array($os, ['Windows', 'Darwin', 'Linux'], true)) {
            throw new RuntimeException('Unexpected OS: ' . $os);
        }
        if (isset($runs[$os])) {
            throw new RuntimeException('Multiple full runs for ' . $os . '; put exactly one selected run per OS in the input folder.');
        }
        // Validate against the authoritative XML rather than trusting edited summary counts.
        $parsed = readJunit($directory . '/junit.xml');
        if ($parsed['cases'] !== $summary['cases'] || $parsed['counts'] !== $summary['counts']) {
            throw new RuntimeException('Summary and JUnit disagree for ' . $os);
        }
        $runs[$os] = ['summary' => $summary, 'environment' => $environment];
        $comparison['sources'][$os] = ['run_id' => $summary['run_id'], 'summary' => $file->getPathname(),
            'junit_sha256' => hash_file('sha256', $directory . '/junit.xml')];
    }
    $expectedIds = systemCaseIds();
    sort($expectedIds);
    $requiredCount = count($expectedIds);
    foreach (['Windows' => 'TC21', 'Darwin' => 'TC22', 'Linux' => 'TC23'] as $os => $id) {
        if (!isset($runs[$os])) {
            $comparison['platform_cases'][$id] = ['status' => 'Not Run', 'actual' => 'No full ' . $os . ' run available'];
            $comparison['reasons'][] = 'Missing ' . $os . ' execution evidence';
            continue;
        }
        $summary = $runs[$os]['summary'];
        $ids = array_column($summary['cases'], 'id');
        sort($ids);
        $valid = $ids === $expectedIds && ($summary['counts']['passed'] ?? 0) === $requiredCount &&
            $summary['exit_code'] === 0 && $summary['status'] === 'Passed';
        $comparison['platform_cases'][$id] = ['status' => $valid ? 'Passed' : 'Failed',
            'actual' => $os . ': ' . ($summary['counts']['passed'] ?? 'Unavailable') . '/' . $requiredCount . ' passed; exit ' . $summary['exit_code']];
        if (!$valid) {
            $comparison['reasons'][] = $os . ' did not pass all registered system scenarios';
        }
    }
    if (count($runs) === 3) {
        $controls = ['php_version', 'phpunit_version', 'composer_lock_sha256', 'test_contract_sha256',
            'php_int_size', 'seed', 'timezone', 'locale', 'locale_setting', 'precision', 'serialize_precision', 'test_memory_limit'];
        $baseline = $runs['Linux']['environment'];
        foreach ($runs as $os => $run) {
            foreach ($controls as $key) {
                if (!isset($run['environment'][$key], $baseline[$key]) || $run['environment'][$key] !== $baseline[$key]) {
                    $comparison['reasons'][] = $os . ' differs in controlled field ' . $key;
                }
            }
            if ($run['environment']['faker'] !== $baseline['faker']) {
                $comparison['reasons'][] = $os . ' uses a different Faker package/commit/source hash';
            }
            $identities = static function (array $cases): array {
                $values = [];
                foreach ($cases as $case) {
                    $values[$case['id']] = [$case['name'], $case['class'], $case['status']];
                }
                ksort($values);
                return $values;
            };
            if ($identities($run['summary']['cases']) !== $identities($runs['Linux']['summary']['cases'])) {
                $comparison['reasons'][] = $os . ' has different test identities or statuses';
            }
        }
        $comparison['status'] = $comparison['reasons'] === [] ? 'Passed' : 'Failed';
    }
} catch (Throwable $error) {
    $comparison['status'] = 'Error';
    $comparison['reasons'][] = $error->getMessage();
}
writeJson($output . '/comparison.json', $comparison);
$markdown = "# Cross-platform comparison\n\nTC24: **" . $comparison['status'] . "**\n\n";
$markdown .= "| Scenario | Actual result | Status |\n|---|---|---|\n";
foreach ($comparison['platform_cases'] as $id => $result) {
    $markdown .= '| ' . $id . ' | ' . markdownCell($result['actual']) . ' | ' . $result['status'] . " |\n";
}
foreach ($comparison['reasons'] as $reason) {
    $markdown .= "\n- " . $reason;
}
file_put_contents($output . '/comparison.md', $markdown . PHP_EOL);
if ($runs !== []) {
    $selected = $runs['Darwin'] ?? reset($runs);
    file_put_contents($output . '/TEST_CASES.md', renderCaseTable($selected['summary'], $selected['environment'], $comparison));
}
echo $markdown . PHP_EOL;
exit($comparison['status'] === 'Passed' ? 0 : 1);
