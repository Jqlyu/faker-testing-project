<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require __DIR__ . '/case-catalog.php';
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
        $id = $summary['run_id'];
        if (!preg_match('/^[a-zA-Z0-9-]+$/D', $id)) {
            throw new RuntimeException('Invalid run ID.');
        }
        $relative = 'results/evidence/' . $id;
        $target = projectRoot() . '/' . $relative;
        if (file_exists($target)) {
            throw new RuntimeException('Evidence already archived; refusing to overwrite ' . $relative);
        }
        mkdir($target, 0777, true);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,
            FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            $destination = $target . '/' . substr($file->getPathname(), strlen($source) + 1);
            if ($file->isDir()) {
                mkdir($destination);
            } elseif (!copy($file->getPathname(), $destination)) {
                throw new RuntimeException('Cannot archive ' . $file->getPathname());
            }
        }
        $summary['evidence_path'] = $relative;
        writeJson($target . '/summary.json', $summary);
        if ($summary['suite'] === 'system' && $summary['filter'] === null && is_file($target . '/environment.json')) {
            $environment = json_decode(file_get_contents($target . '/environment.json'), true, 512, JSON_THROW_ON_ERROR);
            $table = renderCaseTable($summary, $environment);
            file_put_contents($target . '/TEST_CASES.md', $table);
            file_put_contents(projectRoot() . '/docs/TEST_CASES.md', $table);
        }
        echo 'Archived ' . $relative . PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
