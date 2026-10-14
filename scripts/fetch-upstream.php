<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
try {
    $root = projectRoot();
    $directory = $root . '/upstream/faker';
    if (!is_dir($directory)) {
        if (!is_dir($root . '/upstream')) {
            mkdir($root . '/upstream');
        }
        $clone = command(['git', '-c', 'core.autocrlf=false', 'clone', '--branch', 'v1.9.2', 'https://github.com/fzaninotto/Faker.git', $directory], $root);
        echo $clone['output'] . PHP_EOL;
        if ($clone['exit_code'] !== 0) {
            throw new RuntimeException('Git clone failed.');
        }
    }
    if (optionalCommand(['git', 'rev-parse', 'HEAD'], $directory) !== FAKER_COMMIT ||
        optionalCommand(['git', 'status', '--porcelain', '--untracked-files=no'], $directory) !== '') {
        throw new RuntimeException('Existing checkout is not clean at v1.9.2. Preserve your changes and select the pinned commit manually.');
    }
    echo 'Clean upstream checkout verified at ' . FAKER_COMMIT . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
