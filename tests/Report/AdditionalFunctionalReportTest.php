<?php
declare(strict_types=1);

namespace FakerTesting\Report;

use FakerTesting\Support\ReportTestCase;

/** Independently executed report cases F13-F22; no historical outputs are asserted. */
final class AdditionalFunctionalReportTest extends ReportTestCase
{
    public function testF13UtcDateRangeOneHundredSamples(): void
    {
        $faker = $this->newFaker(4160);
        $start = new \DateTime('2020-01-01 00:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTime('2020-12-31 23:59:59', new \DateTimeZone('UTC'));
        $values = [];
        for ($i = 0; $i < 100; ++$i) {
            $date = $faker->dateTimeBetween($start, $end, 'UTC');
            $this->assertInstanceOf(\DateTime::class, $date);
            $this->assertGreaterThanOrEqual($start->getTimestamp(), $date->getTimestamp());
            $this->assertLessThanOrEqual($end->getTimestamp(), $date->getTimestamp());
            $this->assertSame('UTC', $date->getTimezone()->getName());
            $values[] = $date->format('Y-m-d H:i:s T');
        }
        $this->observe('F13', ['seed' => 4160, 'validated_count' => count($values),
            'start' => $start->format(DATE_ATOM), 'end' => $end->format(DATE_ATOM), 'values' => $values]);
    }

    public function testF14UuidFormatVersionAndVariant(): void
    {
        $faker = $this->newFaker(4160);
        $values = [];
        for ($i = 0; $i < 100; ++$i) {
            $values[] = $value = $faker->uuid;
            $this->assertUuid($value);
        }
        $this->observe('F14', ['seed' => 4160, 'validated_count' => count($values), 'version' => 3,
            'variant' => 'RFC 4122', 'values' => $values]);
    }

    public function testF15ReservedSafeEmailAddresses(): void
    {
        $faker = $this->newFaker(4160, 'en_US');
        $values = [];
        for ($i = 0; $i < 100; ++$i) {
            $values[] = $value = $faker->safeEmail;
            $this->assertSafeEmail($value);
        }
        $this->observe('F15', ['seed' => 4160, 'locale' => 'en_US', 'validated_count' => count($values),
            'allowed_domains' => ['example.com', 'example.net', 'example.org'], 'values' => $values]);
    }

    public function testF16IndependentIpv4Validation(): void
    {
        $faker = $this->newFaker(4160);
        $values = [];
        for ($i = 0; $i < 100; ++$i) {
            $values[] = $ip = $faker->ipv4;
            $this->assertIsString($ip);
            $this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4), 'Invalid IPv4: ' . $ip);
        }
        $this->observe('F16', ['seed' => 4160, 'validated_count' => count($values), 'values' => $values]);
    }

    public function testF17TenUniqueRandomDigits(): void
    {
        $faker = $this->newFaker(4160);
        $values = [];
        for ($i = 0; $i < 10; ++$i) {
            $value = $faker->unique()->randomDigit();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(9, $value);
            $values[] = $value;
        }
        $this->assertCount(10, array_unique($values));
        $sorted = $values;
        sort($sorted);
        $this->assertSame(range(0, 9), $sorted);
        $this->observe('F17', ['seed' => 4160, 'seed_origin' => 'New implementation choice',
            'validated_count' => count($values), 'values' => $values]);
    }

    public function testF18CorrectedOptionalWeightDesign(): void
    {
        $faker = $this->newFaker(4160);
        $zero = $faker->optional(0, 'DEFAULT')->passthrough('PROVIDER');
        $hundred = $faker->optional(100, 'DEFAULT')->passthrough('PROVIDER');
        $this->assertSame('DEFAULT', $zero);
        $this->assertSame('PROVIDER', $hundred);
        $values = [];
        for ($i = 0; $i < 200; ++$i) {
            $value = $faker->optional(0.5, 'DEFAULT')->passthrough('PROVIDER');
            $this->assertContains($value, ['DEFAULT', 'PROVIDER']);
            $values[] = $value;
        }
        $providers = count(array_filter($values, static fn(string $v): bool => $v === 'PROVIDER'));
        $this->assertContains('DEFAULT', $values);
        $this->assertContains('PROVIDER', $values);
        $this->assertGreaterThanOrEqual(50, $providers);
        $this->assertLessThanOrEqual(150, $providers);
        $this->observe('F18', ['seed' => 4160, 'seed_origin' => 'New implementation choice',
            'weight_0' => $zero, 'weight_100' => $hundred, 'fractional_weight' => 0.5,
            'trials' => 200, 'provider_count' => $providers, 'default_count' => 200 - $providers,
            'expected_provider_count_interval' => [50, 150], 'values' => $values]);
    }

    public function testF19ValidEvenRandomDigits(): void
    {
        $faker = $this->newFaker(4160);
        $validator = static fn(int $value): bool => $value % 2 === 0;
        $values = [];
        for ($i = 0; $i < 20; ++$i) {
            $value = $faker->valid($validator)->randomDigit();
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(9, $value);
            $this->assertSame(0, $value % 2);
            $values[] = $value;
        }
        $this->observe('F19', ['seed' => 4160, 'validated_count' => count($values), 'values' => $values]);
    }

    public function testF20Seed1234ReplaysTenRandomDigits(): void
    {
        $faker = $this->newFaker(1234);
        $first = $second = [];
        for ($i = 0; $i < 10; ++$i) {
            $first[] = $faker->randomDigit();
        }
        $faker->seed(1234);
        for ($i = 0; $i < 10; ++$i) {
            $second[] = $faker->randomDigit();
        }
        $this->assertCount(10, $first);
        $this->assertCount(10, $second);
        foreach (array_merge($first, $second) as $value) {
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(9, $value);
        }
        $this->assertSame($first, $second);
        $this->observe('F20', ['seed' => 1234, 'sequence_length' => 10, 'first' => $first,
            'second' => $second, 'identical' => $first === $second]);
    }

    public function testF21BothifyInvoicePattern(): void
    {
        $faker = $this->newFaker(4160);
        $values = [];
        for ($i = 0; $i < 20; ++$i) {
            $values[] = $value = $faker->bothify('INV-##??');
            $this->assertIsString($value);
            $this->assertMatchesRegularExpression('/^INV-[0-9]{2}[a-z]{2}$/D', $value);
        }
        $this->observe('F21', ['seed' => 4160, 'pattern' => 'INV-##??',
            'validated_count' => count($values), 'values' => $values]);
    }

    public function testF22TwentyDatesWithReportDateOnlyEndpoints(): void
    {
        $faker = $this->newFaker(4160);
        $start = new \DateTime('2020-01-01', new \DateTimeZone('UTC'));
        $end = new \DateTime('2020-12-31', new \DateTimeZone('UTC'));
        $values = [];
        for ($i = 0; $i < 20; ++$i) {
            $date = $faker->dateTimeBetween('2020-01-01', '2020-12-31', 'UTC');
            $this->assertInstanceOf(\DateTime::class, $date);
            $this->assertGreaterThanOrEqual($start->getTimestamp(), $date->getTimestamp());
            $this->assertLessThanOrEqual($end->getTimestamp(), $date->getTimestamp());
            $values[] = $date->format(DATE_ATOM);
        }
        $this->observe('F22', ['seed' => 4160, 'validated_count' => count($values),
            'timezone_choice' => 'UTC; report omits timezone', 'start' => $start->format(DATE_ATOM),
            'end' => $end->format(DATE_ATOM), 'values' => $values]);
    }
}
