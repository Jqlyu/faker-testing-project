<?php
declare(strict_types=1);

const FAKER_COMMIT = '848d8125239d7dbf8ab25cb7f054f1a630e68c2e';

function projectRoot(): string
{
    return dirname(__DIR__);
}

function writeJson(string $path, array $data): void
{
    if (file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL) === false) {
        throw new RuntimeException('Cannot write ' . $path);
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

function treeHash(string $directory): string
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,
        FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
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
    $files = ['tests' => treeHash($root . '/tests'), 'scripts' => treeHash($root . '/scripts'),
        'phpunit.xml' => hash_file('sha256', $root . '/phpunit.xml')];
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
        foreach (['failure' => 'Failed', 'error' => 'Error', 'skipped' => 'Skipped'] as $tag => $label) {
            foreach ($node->getElementsByTagName($tag) as $problem) {
                $status = $label;
                $reasons[] = trim($problem->textContent) ?: $problem->getAttribute('message');
            }
        }
        preg_match('/\btest(TC\d{2})/', $node->getAttribute('name'), $matches);
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
