<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/case-catalog.php';
if (count($argv) < 2) {
    fwrite(STDERR, "Usage: php scripts/archive-evidence.php results/RUN_ID [results/OTHER_RUN_ID]\n");
    exit(2);
}
try {
    foreach (array_slice($argv, 1) as $source) {
        $source = realpath($source);
        if ($source === false || !is_file($source . '/summary.json')) {
            throw new RuntimeException('Run directory or summary.json missing.');
        }
        $summary = json_decode(file_get_contents($source . '/summary.json'), true, 512, JSON_THROW_ON_ERROR);
        $id = $summary['run_id'] ?? null;
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9-]+$/D', $id)) {
            throw new RuntimeException('Invalid run ID.');
        }
        $relative = 'results/evidence/' . $id;
        $target = projectRoot() . '/' . $relative;
        if (file_exists($target) || is_link($target)) {
            throw new RuntimeException('Evidence already archived; refusing to overwrite ' . $relative);
        }
        if (strpos($target . '/', $source . '/') === 0) {
            throw new RuntimeException('The archive destination cannot be inside the source directory.');
        }

        $currentReport = ($summary['schema_version'] ?? null) === 2 && ($summary['suite'] ?? null) === 'report';
        $table = null;
        if ($currentReport) {
            if (!is_bool($summary['full_report_run'] ?? null) || !is_array($summary['cases'] ?? null) ||
                !isset($summary['started_at_utc']) || !array_key_exists('report_error', $summary) ||
                !is_file($source . '/environment.json')) {
                throw new RuntimeException('Current report summary or environment is incomplete.');
            }
            $environment = json_decode(file_get_contents($source . '/environment.json'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($environment) || !isset($environment['os_family'], $environment['php_version'])) {
                throw new RuntimeException('Current report environment is incomplete.');
            }
            $summary['evidence_path'] = $relative;
            $table = renderCaseTable($summary, $environment);
        }

        $archiveDirectory = dirname($target);
        if (!is_dir($archiveDirectory) && !mkdir($archiveDirectory, 0777, true) && !is_dir($archiveDirectory)) {
            throw new RuntimeException('Cannot create the evidence archive directory.');
        }
        // Exclusive creation also refuses a destination created after the existence check.
        if (!mkdir($target)) {
            throw new RuntimeException('Cannot create archive; refusing any existing destination: ' . $relative);
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,
            FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $destination = $target . '/' . substr($file->getPathname(), strlen($source) + 1);
            if ($file->isDir()) {
                if (!mkdir($destination)) {
                    throw new RuntimeException('Cannot archive directory ' . $file->getPathname());
                }
            } elseif (!copy($file->getPathname(), $destination)) {
                throw new RuntimeException('Cannot archive ' . $file->getPathname());
            }
        }
        if ($currentReport) {
            writeJson($target . '/summary.json', $summary);
            foreach (['REPORT_TABLE.md', 'TEST_CASES.md'] as $name) {
                if (file_put_contents($target . '/' . $name, $table) === false) {
                    throw new RuntimeException('Cannot write archived report table ' . $name);
                }
            }
            if ($summary['full_report_run'] && file_put_contents(projectRoot() . '/docs/TEST_CASES.md', $table) === false) {
                throw new RuntimeException('Cannot update docs/TEST_CASES.md from the full report run.');
            }
        }
        // Older schemas and other suites are copied verbatim. Never reinterpret old TC evidence.
        echo 'Archived ' . $relative . ($currentReport ? ' (current report)' : ' (copied unchanged)') . PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
