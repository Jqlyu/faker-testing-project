<?php
declare(strict_types=1);

namespace FakerTesting\Infrastructure;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/scripts/common.php';
require_once dirname(__DIR__, 2) . '/scripts/case-catalog.php';

/** Infrastructure fixtures are explicitly synthetic and never stored as product/OS evidence. */
abstract class SyntheticFixtureTestCase extends TestCase
{
    protected string $temporary;

    protected function setUp(): void
    {
        $base = PHP_OS_FAMILY === 'Windows' ? sys_get_temp_dir() : '/tmp';
        $this->temporary = $base . '/faker-infrastructure-synthetic-' . bin2hex(random_bytes(6));
        mkdir($this->temporary, 0777, true);
        file_put_contents($this->temporary . '/SYNTHETIC-FIXTURES.txt',
            "Reporting infrastructure test input only; not Faker, benchmark or operating-system execution evidence.\n");
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->temporary)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->temporary,
            \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->temporary);
    }

    protected function fixture(string $filename, string $contents): string
    {
        $path = $this->temporary . '/' . $filename;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $contents);
        return $path;
    }
}
