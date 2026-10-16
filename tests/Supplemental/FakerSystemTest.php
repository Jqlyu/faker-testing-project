<?php
declare(strict_types=1);

namespace FakerTesting\Supplemental;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

/** Legacy TC checks are supplemental; they are not the report F/N cases. */
final class FakerSystemTest extends TestCase
{
    private Generator $faker;

    protected function setUp(): void
    {
        $this->faker = Factory::create('en_US');
        // Faker uses a global PRNG: reseed before each test to isolate execution order.
        $this->faker->seed(4160);
    }

    private function assertNonEmptyString($value, string $context): void
    {
        $this->assertIsString($value, $context);
        $this->assertNotSame('', trim($value), $context);
        $this->assertTrue(mb_check_encoding($value, 'UTF-8'), $context . ': invalid UTF-8');
        $this->assertStringNotContainsString('{{', $value, $context . ': unresolved formatter');
    }

    public function testTC01NameIsNonEmptyString(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $this->assertNonEmptyString($this->faker->name, "name sample $i");
        }
    }

    public function testTC02EmailHasValidSyntax(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $email = $this->faker->email;
            $this->assertIsString($email);
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "Invalid email: $email");
        }
    }

    public function testTC03AddressIsNonEmptyString(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $address = $this->faker->address;
            $this->assertNonEmptyString($address, "address sample $i");
            $this->assertMatchesRegularExpression('/[0-9]/', $address);
        }
    }

    public function testTC04PhoneNumberHasBasicFormat(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $phone = $this->faker->phoneNumber;
            $this->assertNonEmptyString($phone, "phone sample $i");
            $this->assertMatchesRegularExpression('/^[+0-9(). x-]+$/D', $phone);
            $main = explode(' x', $phone)[0];
            $digits = preg_replace('/\D/', '', $main);
            $this->assertContains(strlen($digits), [10, 11], "Unexpected phone digit count: $phone");
        }
    }

    public function testTC05IntegersStayWithinOneToOneHundred(): void
    {
        for ($i = 0; $i < 500; ++$i) {
            $number = $this->faker->numberBetween(1, 100);
            $this->assertIsInt($number);
            $this->assertGreaterThanOrEqual(1, $number);
            $this->assertLessThanOrEqual(100, $number);
        }
    }

    public function testTC06EqualIntegerBoundsReturnThatValue(): void
    {
        foreach ([-10, 0, 42, 2147483647] as $bound) {
            $this->assertSame($bound, $this->faker->numberBetween($bound, $bound));
        }
    }

    public function testTC07FloatRangeAndPrecision(): void
    {
        for ($i = 0; $i < 500; ++$i) {
            $value = $this->faker->randomFloat(2, -10.25, 20.75);
            $this->assertIsFloat($value);
            $this->assertTrue(is_finite($value));
            $this->assertGreaterThanOrEqual(-10.25, $value);
            $this->assertLessThanOrEqual(20.75, $value);
            $this->assertEqualsWithDelta(round($value * 100), $value * 100, 0.000001);
        }
    }

    public function testTC08DocumentedNumericBoundaries(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            // numberBetween explicitly accepts bounds in either order.
            $value = $this->faker->numberBetween(10, -10);
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(-10, $value);
            $this->assertLessThanOrEqual(10, $value);
            $digit = $this->faker->randomNumber(1, true);
            $this->assertGreaterThanOrEqual(1, $digit);
            $this->assertLessThanOrEqual(9, $digit);
            $nineDigits = $this->faker->randomNumber(9, true);
            $this->assertIsInt($nineDigits);
            $this->assertSame(9, strlen((string) $nineDigits));
            $this->assertSame(0, $this->faker->numberBetween(0, 0));
        }
    }

    public function testTC09DateHasRequestedFormatAndIsValid(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $date = $this->faker->date('Y-m-d', '2024-12-31 23:59:59');
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/D', $date);
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            $this->assertNotFalse($parsed);
            $this->assertSame($date, $parsed->format('Y-m-d'));
            $this->assertLessThanOrEqual('2024-12-31', $date);
        }
    }

    public function testTC10DatesStayInExplicitInterval(): void
    {
        $start = new \DateTime('2020-01-01 00:00:00', new \DateTimeZone('UTC'));
        $end = new \DateTime('2020-12-31 23:59:59', new \DateTimeZone('UTC'));
        for ($i = 0; $i < 100; ++$i) {
            $date = $this->faker->dateTimeBetween($start, $end, 'UTC');
            $this->assertInstanceOf(\DateTime::class, $date);
            $this->assertGreaterThanOrEqual($start->getTimestamp(), $date->getTimestamp());
            $this->assertLessThanOrEqual($end->getTimestamp(), $date->getTimestamp());
            $this->assertSame('UTC', $date->getTimezone()->getName());
        }
    }

    public function testTC11Ipv4IsSyntacticallyValid(): void
    {
        for ($i = 0; $i < 100; ++$i) {
            $ip = $this->faker->ipv4;
            $this->assertIsString($ip);
            $this->assertNotFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4), "Invalid IPv4: $ip");
        }
    }

    public function testTC12UuidHasVersionThreeAndRfcVariant(): void
    {
        // This archived provider generates UUID v3, not v4.
        for ($i = 0; $i < 100; ++$i) {
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{8}-[a-f0-9]{4}-3[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D',
                $this->faker->uuid
            );
        }
    }

    public function testTC13EnUsUsesLocaleProvidersAndPostalFormat(): void
    {
        $providers = array_map('get_class', $this->faker->getProviders());
        $this->assertContains('Faker\\Provider\\en_US\\Person', $providers);
        $this->assertContains('Faker\\Provider\\en_US\\Address', $providers);
        for ($i = 0; $i < 100; ++$i) {
            $this->assertNonEmptyString($this->faker->name, "en_US name $i");
            $this->assertMatchesRegularExpression('/^\d{5}(-\d{4})?$/D', $this->faker->postcode);
            $this->assertMatchesRegularExpression('/^[A-Z]{2}$/D', $this->faker->stateAbbr);
        }
    }

    public function testTC14FiFiUsesLocaleProvidersAndUtf8(): void
    {
        $finnish = Factory::create('fi_FI');
        $finnish->seed(4160);
        $providers = array_map('get_class', $finnish->getProviders());
        $this->assertContains('Faker\\Provider\\fi_FI\\Person', $providers);
        $this->assertContains('Faker\\Provider\\fi_FI\\Address', $providers);
        for ($i = 0; $i < 100; ++$i) {
            $this->assertNonEmptyString($finnish->name, "fi_FI name $i");
            $this->assertNonEmptyString($finnish->address, "fi_FI address $i");
            $this->assertMatchesRegularExpression('/^\d{5}$/D', $finnish->postcode);
        }
    }

    public function testTC15FixedSeedReproducesWholeSequence(): void
    {
        $generate = static function (int $seed): array {
            $faker = Factory::create('en_US');
            $faker->seed($seed);
            $rows = [];
            for ($i = 0; $i < 20; ++$i) {
                $rows[] = [$faker->name, $faker->email, $faker->numberBetween(1, 100),
                    $faker->date('Y-m-d', '2024-12-31 23:59:59'), $faker->uuid];
            }
            return $rows;
        };
        $sequence = $generate(4160);
        $this->assertCount(20, $sequence);
        $this->assertSame($sequence, $generate(4160));
        $this->assertNotSame($sequence, $generate(4161));
    }

    public function testTC16UniqueValuesAndExhaustion(): void
    {
        $unique = $this->faker->unique(false, 10000);
        $values = [];
        for ($i = 0; $i < 100; ++$i) {
            $value = $unique->numberBetween(1, 100);
            $this->assertIsInt($value);
            $this->assertGreaterThanOrEqual(1, $value);
            $this->assertLessThanOrEqual(100, $value);
            $values[] = $value;
        }
        sort($values);
        $this->assertSame(range(1, 100), $values);
        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('Maximum retries of 10000 reached without finding a unique value');
        $unique->numberBetween(1, 100);
    }

    public function testTC17InvalidParameterRaisesSpecificException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('randomNumber() generates numbers of fixed width.');
        // The second parameter is a boolean, not an upper bound.
        $this->faker->randomNumber(5, 200);
    }

    public function testTC18UnknownFormatterHasClearException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown formatter "formatterThatDoesNotExist4160"');
        $this->faker->formatterThatDoesNotExist4160();
    }

    public function testTC19ThousandCompositeRecordsRemainValid(): void
    {
        for ($i = 0; $i < 1000; ++$i) {
            $this->assertNonEmptyString($this->faker->name, "record $i name");
            $this->assertNonEmptyString($this->faker->address, "record $i address");
            $email = $this->faker->email;
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "record $i: $email");
            $this->assertNotFalse(filter_var($this->faker->ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
            $this->assertMatchesRegularExpression(
                '/^[a-f0-9]{8}-[a-f0-9]{4}-3[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D',
                $this->faker->uuid
            );
        }
    }

    public function testTC20PerformanceIsMeasuredWithValidatedOutput(): void
    {
        // No arbitrary speed threshold: this records a baseline, not an SLA.
        for ($i = 0; $i < 100; ++$i) {
            $this->faker->name;
            $this->faker->email;
            $this->faker->uuid;
        }
        $this->faker->seed(4160);
        $samples = [];
        $rowsPerSample = 1000;
        $checksum = hash_init('sha256');
        for ($sample = 0; $sample < 3; ++$sample) {
            $start = hrtime(true);
            $rows = [];
            for ($i = 0; $i < $rowsPerSample; ++$i) {
                $rows[] = [$this->faker->name, $this->faker->email, $this->faker->uuid];
            }
            $samples[] = (hrtime(true) - $start) / 1e9;
            // Validate outside the timed section so assertion overhead is excluded.
            foreach ($rows as [$name, $email, $uuid]) {
                $this->assertNonEmptyString($name, 'benchmark name');
                $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
                $this->assertMatchesRegularExpression(
                    '/^[a-f0-9]{8}-[a-f0-9]{4}-3[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $uuid
                );
                hash_update($checksum, json_encode([$name, $email, $uuid], JSON_THROW_ON_ERROR));
            }
        }
        $ordered = $samples;
        sort($ordered);
        $measurement = [
            'case_id' => 'TC20', 'seed' => 4160, 'warmup_rows' => 100,
            'rows_per_sample' => $rowsPerSample, 'samples' => 3,
            'formatters_per_row' => ['name', 'email', 'uuid'],
            'generation_operations' => 9000, 'assertions_inside_timing' => false,
            'seconds_per_sample' => $samples, 'median_seconds' => $ordered[1],
            'rows_per_second_at_median' => $rowsPerSample / $ordered[1],
            'peak_process_memory_bytes' => memory_get_peak_usage(true),
            'output_sha256' => hash_final($checksum), 'threshold' => null,
            'environment_file' => 'environment.json',
        ];
        foreach ($samples as $elapsed) {
            $this->assertGreaterThan(0.0, $elapsed);
        }
        $directory = getenv('FAKER_RESULTS_DIR');
        if ($directory !== false && $directory !== '') {
            $written = file_put_contents($directory . '/performance.json',
                json_encode($measurement, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);
            $this->assertNotFalse($written, 'Could not save performance evidence');
        } else {
            fwrite(STDOUT, 'TC20 measurement: ' . json_encode($measurement, JSON_THROW_ON_ERROR) . PHP_EOL);
        }
    }
}
