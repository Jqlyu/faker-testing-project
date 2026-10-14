<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
try {
    $manifest = json_decode(file_get_contents(projectRoot() . '/tools/versions.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($manifest as $tool) {
        $path = projectRoot() . '/tools/' . $tool['filename'];
        if (!is_file($path) || hash_file('sha256', $path) !== $tool['sha256']) {
            $context = stream_context_create(['http' => ['timeout' => 120, 'user_agent' => 'Faker-Testing-Project']]);
            $data = file_get_contents($tool['url'], false, $context);
            if ($data === false || hash('sha256', $data) !== $tool['sha256']) {
                throw new RuntimeException('Download or checksum verification failed: ' . $tool['filename']);
            }
            if (file_put_contents($path, $data) === false) {
                throw new RuntimeException('Cannot save ' . $path);
            }
        }
        echo $tool['filename'] . ' ' . $tool['version'] . ' SHA256 verified' . PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
