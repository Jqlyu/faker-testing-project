<?php
declare(strict_types=1);

namespace FakerTesting\Support;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/scripts/common.php';

/** Shared assertions do not combine logical report cases or their executions. */
abstract class ReportTestCase extends TestCase
{
    protected function newFaker(int $seed, string $locale = 'en_US'): Generator
    {
        $faker = Factory::create($locale);
        $faker->seed($seed);
        return $faker;
    }

    protected function assertNonEmptyString($value, string $context = ''): void
    {
        $this->assertIsString($value, $context);
        $this->assertNotSame('', trim($value), $context);
        $this->assertTrue(mb_check_encoding($value, 'UTF-8'), $context . ' invalid UTF-8');
    }

    protected function assertUuid($value): void
    {
        $this->assertIsString($value);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-3[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $value
        );
    }

    protected function assertSafeEmail($value): void
    {
        $this->assertIsString($value);
        $this->assertNotFalse(filter_var($value, FILTER_VALIDATE_EMAIL), 'Invalid safeEmail: ' . $value);
        $domain = substr($value, strrpos($value, '@') + 1);
        $this->assertContains($domain, ['example.com', 'example.net', 'example.org'], 'Not a reserved example domain');
    }

    protected function evidenceDirectory(): string
    {
        $path = getenv('FAKER_RESULTS_DIR');
        if ($path === false || $path === '') {
            static $standalone;
            if ($standalone === null) {
                $standalone = sys_get_temp_dir() . '/faker-direct-phpunit-' . bin2hex(random_bytes(6));
                mkdir($standalone, 0777, true);
                fwrite(STDOUT, 'Direct PHPUnit evidence: ' . $standalone . PHP_EOL);
            }
            return $standalone;
        }
        return $path;
    }

    protected function observe(string $id, array $data): void
    {
        $directory = $this->evidenceDirectory() . '/observations';
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException('Cannot create observation directory');
        }
        \writeJson($directory . '/' . $id . '.json', [
            'case_id' => $id, 'observed_at_utc' => gmdate('c'), 'data' => $data,
            'provenance' => 'New implementation and current execution; not historical teammate output',
        ]);
    }
}
