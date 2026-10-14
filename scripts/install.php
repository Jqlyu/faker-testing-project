<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
$directory = projectRoot() . '/results/setup-' . gmdate('Ymd\THis\Z') . '-' . bin2hex(random_bytes(3));
mkdir($directory, 0777, true);
$result = ['started_at_utc' => gmdate('c'), 'os' => PHP_OS_FAMILY, 'os_version' => php_uname('r'),
    'architecture' => php_uname('m'), 'php_version' => PHP_VERSION, 'exit_code' => 2];
try {
    $composer = projectRoot() . '/tools/composer.phar';
    if (!is_file(projectRoot() . '/composer.lock')) {
        throw new RuntimeException('composer.lock missing; do not silently resolve new dependency versions.');
    }
    $manifest = json_decode(file_get_contents(projectRoot() . '/tools/versions.json'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_file($composer) || hash_file('sha256', $composer) !== $manifest['composer']['sha256']) {
        throw new RuntimeException('Run php scripts/download-tools.php to obtain the pinned Composer.');
    }
    $result['composer_version'] = optionalCommand([PHP_BINARY, $composer, '--version', '--no-ansi'], projectRoot());
    $result['lock_sha256'] = hash_file('sha256', projectRoot() . '/composer.lock');
    $install = command([PHP_BINARY, $composer, 'install', '--no-interaction', '--prefer-dist', '--no-progress'],
        projectRoot(), $directory . '/composer-install.log');
    $result['exit_code'] = $install['exit_code'];
    if ($install['exit_code'] === 0) {
        $check = command([PHP_BINARY, $composer, 'check-platform-reqs'], projectRoot(), $directory . '/platform-requirements.log');
        $result['exit_code'] = $check['exit_code'];
    }
} catch (Throwable $error) {
    $result['error'] = $error->getMessage();
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
}
$result['finished_at_utc'] = gmdate('c');
writeJson($directory . '/installation.json', $result);
exit($result['exit_code']);
