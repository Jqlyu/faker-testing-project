<?php
declare(strict_types=1);

namespace FakerTesting\Report;

use Faker\Generator;
use FakerTesting\Support\ReportTestCase;

/**
 * New implementations of F01-F12 from the supplied report's detailed design.
 * These are not the teammates' original test scripts or historical execution.
 * Unspecified inputs follow docs/REPORT_CASE_MAPPING.md.
 */
final class FirstFunctionalReportTest extends ReportTestCase
{
    public function testF01NamesAndSeededReplay(): void
    {
        $faker = $this->newFaker(740001);
        $genders = [null, 'male', 'female'];
        $names = $this->nameSequence($faker, $genders);
        foreach ($names as $index => $name) {
            $this->assertNonEmptyString($name, 'F01 name ' . $index);
        }
        $this->assertCount(60, $names);
        $faker->seed(740001);
        $this->assertSame($names, $this->nameSequence($faker, $genders));

        $this->observe('F01', [
            'seed' => 740001, 'locale' => 'en_US', 'genders' => $genders,
            'samples_per_gender' => 20, 'validated_count' => count($names),
            'replay_identical' => true, 'examples' => array_slice($names, 0, 3),
        ]);
    }

    public function testF02SafeEmailSyntaxAndReservedDomains(): void
    {
        $faker = $this->newFaker(740002);
        $emails = [];
        for ($i = 0; $i < 100; ++$i) {
            $email = $faker->safeEmail();
            $this->assertSafeEmail($email);
            $emails[] = $email;
        }
        $this->observe('F02', [
            'seed' => 740002, 'locale' => 'en_US', 'validated_count' => count($emails),
            'allowed_domains' => ['example.com', 'example.net', 'example.org'],
            'examples' => array_slice($emails, 0, 3),
        ]);
    }

    public function testF03UtcDateBoundariesAndRejectedReversal(): void
    {
        $faker = $this->newFaker(740003);
        $zone = new \DateTimeZone('UTC');
        $start = new \DateTime('2020-02-28 00:00:00', $zone);
        $end = new \DateTime('2020-03-01 23:59:59', $zone);
        $examples = [];
        for ($i = 0; $i < 100; ++$i) {
            $date = $faker->dateTimeBetween($start, $end, 'UTC');
            $this->assertInstanceOf(\DateTime::class, $date);
            $this->assertSame('UTC', $date->getTimezone()->getName());
            $this->assertGreaterThanOrEqual($start->getTimestamp(), $date->getTimestamp());
            $this->assertLessThanOrEqual($end->getTimestamp(), $date->getTimestamp());
            if (count($examples) < 3) {
                $examples[] = $date->format('Y-m-d H:i:s T');
            }
        }

        $equal = new \DateTime('2020-02-29 12:00:00', $zone);
        $singleton = $faker->dateTimeBetween($equal, $equal, 'UTC');
        $this->assertInstanceOf(\DateTime::class, $singleton);
        $this->assertSame($equal->getTimestamp(), $singleton->getTimestamp());
        $this->assertSame('UTC', $singleton->getTimezone()->getName());
        $exceptionClass = null;
        try {
            $faker->dateTimeBetween($end, $start, 'UTC');
        } catch (\InvalidArgumentException $exception) {
            $exceptionClass = get_class($exception);
        }
        $this->assertSame(\InvalidArgumentException::class, $exceptionClass);

        $this->observe('F03', [
            'seed' => 740003, 'locale' => 'en_US', 'timezone' => 'UTC',
            'start' => $start->format('Y-m-d H:i:s'), 'end' => $end->format('Y-m-d H:i:s'),
            'validated_count' => 100, 'examples' => $examples,
            'equal_endpoint' => $singleton->format('Y-m-d H:i:s T'),
            'reversed_interval_exception' => $exceptionClass,
        ]);
    }

    public function testF04IntegerBoundaryPartitions(): void
    {
        $faker = $this->newFaker(740004);
        // The report omits these exact pairs and draw counts; the mapping records this selection.
        $partitions = [
            'positive' => [1, 100], 'negative' => [-100, -1], 'signed' => [-10, 10],
            'zero_singleton' => [0, 0], 'positive_singleton' => [42, 42],
            'reversed' => [100, 1], 'minimum_singleton' => [PHP_INT_MIN, PHP_INT_MIN],
            'maximum_singleton' => [PHP_INT_MAX, PHP_INT_MAX],
        ];
        $observed = [];
        $validated = 0;
        foreach ($partitions as $label => [$first, $second]) {
            $minimum = min($first, $second);
            $maximum = max($first, $second);
            $examples = [];
            for ($i = 0; $i < 20; ++$i) {
                $value = $faker->numberBetween($first, $second);
                $this->assertIsInt($value, 'F04 ' . $label);
                $this->assertGreaterThanOrEqual($minimum, $value);
                $this->assertLessThanOrEqual($maximum, $value);
                if ($first === $second) {
                    $this->assertSame($first, $value);
                }
                if (count($examples) < 3) {
                    $examples[] = $value;
                }
                ++$validated;
            }
            $observed[$label] = ['inputs' => [$first, $second], 'validated_count' => 20,
                'examples' => $examples];
        }
        $this->observe('F04', [
            'seed' => 740004, 'locale' => 'en_US', 'validated_count' => $validated,
            'samples_per_partition' => 20, 'partitions' => $observed,
            'integer_size_bytes' => PHP_INT_SIZE,
        ]);
    }

    public function testF05FinnishPostcodeAndPinnedStates(): void
    {
        $faker = $this->newFaker(740005, 'fi_FI');
        // Pinned v1.9.2 locale vocabulary, independent of the provider at execution time.
        $states = [
            'Ahvenanmaa', 'Etelä-Karjala', 'Etelä-Pohjanmaa', 'Etelä-Savo', 'Kainuu',
            'Kanta-Häme', 'Keski-Pohjanmaa', 'Keski-Suomi', 'Kymenlaakso', 'Lappi',
            'Pirkanmaa', 'Pohjanmaa', 'Pohjois-Karjala', 'Pohjois-Pohjanmaa',
            'Pohjois-Savo', 'Päijät-Häme', 'Satakunta', 'Uusimaa', 'Varsinais-Suomi',
        ];
        $examples = [];
        for ($i = 0; $i < 100; ++$i) {
            $postcode = $faker->postcode();
            $state = $faker->state();
            $this->assertIsString($postcode);
            $this->assertMatchesRegularExpression('/\A[0-9]{5}\z/', $postcode);
            $this->assertIsString($state);
            $this->assertContains($state, $states);
            if (count($examples) < 3) {
                $examples[] = ['postcode' => $postcode, 'state' => $state];
            }
        }
        $this->observe('F05', [
            'seed' => 740005, 'locale' => 'fi_FI', 'validated_count' => 100,
            'pinned_states' => $states, 'examples' => $examples,
        ]);
    }

    public function testF06UuidVersionVariantAndSampleDiversity(): void
    {
        $faker = $this->newFaker(740006);
        $uuids = [];
        for ($i = 0; $i < 100; ++$i) {
            $uuid = $faker->uuid();
            $this->assertUuid($uuid);
            $uuids[] = $uuid;
        }
        $distinct = count(array_unique($uuids));
        $this->assertSame(count($uuids), $distinct);
        $this->observe('F06', [
            'seed' => 740006, 'locale' => 'en_US', 'validated_count' => count($uuids),
            'version' => 3, 'variant' => 'RFC', 'distinct_count' => $distinct,
            'examples' => array_slice($uuids, 0, 3),
        ]);
    }

    public function testF07RegexifyMatchesThreePatterns(): void
    {
        $faker = $this->newFaker(740007);
        $patterns = ['[A-Z]{2}[0-9]{3}', '(cat|dog)-[0-9]{2}', '[A-F]{1,4}'];
        $observed = [];
        foreach ($patterns as $pattern) {
            $examples = [];
            for ($i = 0; $i < 50; ++$i) {
                $value = $faker->regexify($pattern);
                $this->assertIsString($value);
                $this->assertMatchesRegularExpression('/\A(?:' . $pattern . ')\z/', $value);
                if (count($examples) < 3) {
                    $examples[] = $value;
                }
            }
            $observed[] = ['pattern' => $pattern, 'validated_count' => 50, 'examples' => $examples];
        }
        $this->observe('F07', [
            'seed' => 740007, 'locale' => 'en_US', 'validated_count' => 150,
            'patterns' => $observed,
        ]);
    }

    public function testF08RandomElementsCountsDuplicatesAndOversizedSelection(): void
    {
        $faker = $this->newFaker(740008);
        $source = ['alpha', 'beta', 'gamma', 'delta', 'epsilon'];
        $observed = [];
        $validatedSelections = 0;
        foreach ([false, true] as $allowDuplicates) {
            foreach ([0, 3, 5, 6] as $count) {
                if (!$allowDuplicates && $count === 6) {
                    $exceptionClass = null;
                    try {
                        $faker->randomElements($source, $count, $allowDuplicates);
                    } catch (\LengthException $exception) {
                        $exceptionClass = get_class($exception);
                    }
                    $this->assertSame(\LengthException::class, $exceptionClass);
                    $observed[] = ['count' => $count, 'allow_duplicates' => $allowDuplicates,
                        'exception' => $exceptionClass];
                    continue;
                }
                $selection = $faker->randomElements($source, $count, $allowDuplicates);
                $this->assertIsArray($selection);
                $this->assertCount($count, $selection);
                foreach ($selection as $value) {
                    $this->assertContains($value, $source);
                }
                $distinct = count(array_unique($selection));
                if (!$allowDuplicates) {
                    $this->assertSame($count, $distinct);
                } elseif ($count > count($source)) {
                    // Six picks from five source values must repeat; shorter samples need not.
                    $this->assertLessThan($count, $distinct);
                }
                $observed[] = ['count' => $count, 'allow_duplicates' => $allowDuplicates,
                    'selection' => $selection, 'distinct_count' => $distinct];
                ++$validatedSelections;
            }
        }
        $this->observe('F08', [
            'seed' => 740008, 'locale' => 'en_US', 'source' => $source,
            'validated_count' => $validatedSelections, 'expected_exception_count' => 1,
            'selections' => $observed,
        ]);
    }

    public function testF09Ipv4AndIpv6PassIndependentValidation(): void
    {
        $faker = $this->newFaker(740009);
        $observed = [];
        foreach (['ipv4' => FILTER_FLAG_IPV4, 'ipv6' => FILTER_FLAG_IPV6] as $formatter => $flag) {
            $examples = [];
            for ($i = 0; $i < 100; ++$i) {
                $address = $faker->$formatter();
                $this->assertIsString($address);
                $this->assertNotFalse(filter_var($address, FILTER_VALIDATE_IP, $flag));
                if (count($examples) < 3) {
                    $examples[] = $address;
                }
            }
            $observed[$formatter] = ['validated_count' => 100, 'examples' => $examples];
        }
        $this->observe('F09', [
            'seed' => 740009, 'locale' => 'en_US', 'validated_count' => 200,
            'address_versions' => $observed,
        ]);
    }

    public function testF10CreditCardFormatsAndIndependentLuhnChecksum(): void
    {
        $faker = $this->newFaker(740010);
        $formats = [
            'Visa' => '/\A4[0-9]{15}\z/',
            // v1.9.2 includes 2-series masks as well as the older 51-55 masks.
            'MasterCard' => '/\A(?:5[1-5][0-9]{14}|(?:2221|2720)[0-9]{12}|2[3-6][0-9]{14})\z/',
            'American Express' => '/\A3[47][0-9]{13}\z/',
            'Discover Card' => '/\A6011[0-9]{12}\z/',
        ];
        $observed = [];
        foreach ($formats as $type => $format) {
            $examples = [];
            for ($i = 0; $i < 25; ++$i) {
                $number = $faker->creditCardNumber($type);
                $this->assertIsString($number);
                $this->assertMatchesRegularExpression($format, $number);
                $this->assertTrue(self::passesLuhn($number), 'F10 invalid Luhn checksum: ' . $number);
                if (count($examples) < 3) {
                    $examples[] = $number;
                }
            }
            $observed[$type] = ['validated_count' => 25, 'format' => $format, 'examples' => $examples];
        }
        $this->observe('F10', [
            'seed' => 740010, 'locale' => 'en_US', 'validated_count' => 100,
            'cards' => $observed, 'checksum_validator' => 'independent Luhn implementation',
        ]);
    }

    public function testF11ParagraphCountsAndTextSeparators(): void
    {
        $faker = $this->newFaker(740011);
        $observed = [];
        foreach ([0, 1, 3, 5] as $count) {
            $faker->seed(740011);
            $paragraphs = $faker->paragraphs($count, false);
            $this->assertIsArray($paragraphs);
            $this->assertCount($count, $paragraphs);
            foreach ($paragraphs as $paragraph) {
                $this->assertNonEmptyString($paragraph, 'F11 paragraph');
                $this->assertSame('.', substr($paragraph, -1));
            }
            $faker->seed(740011);
            $text = $faker->paragraphs($count, true);
            $this->assertIsString($text);
            $this->assertSame(implode("\n\n", $paragraphs), $text);
            if ($count === 0) {
                $this->assertSame([], $paragraphs);
                $this->assertSame('', $text);
            } else {
                $this->assertSame($paragraphs, explode("\n\n", $text));
            }
            $observed[] = ['count' => $count, 'array_count' => count($paragraphs),
                'text_paragraph_count' => $text === '' ? 0 : count(explode("\n\n", $text)),
                'text_bytes' => strlen($text), 'example' => $paragraphs[0] ?? '',
                'paired_outputs_equal' => true];
        }
        $this->observe('F11', [
            'seed' => 740011, 'locale' => 'en_US', 'validated_count' => 8,
            'samples_per_count_and_mode' => 1, 'paragraphs_per_mode' => 9,
            'separator' => "\n\n", 'outputs' => $observed,
        ]);
    }

    public function testF12Ean13FormatChecksumAndValidatorControls(): void
    {
        $faker = $this->newFaker(740012);
        // Explicit new control fixtures; the report did not specify its historical examples.
        $knownValid = '4006381333931';
        $knownInvalid = '4006381333932';
        $this->assertTrue(self::passesEan13Checksum($knownValid));
        $this->assertFalse(self::passesEan13Checksum($knownInvalid));
        $examples = [];
        for ($i = 0; $i < 100; ++$i) {
            $barcode = $faker->ean13();
            $this->assertIsString($barcode);
            $this->assertMatchesRegularExpression('/\A[0-9]{13}\z/', $barcode);
            $this->assertTrue(self::passesEan13Checksum($barcode), 'F12 invalid EAN13: ' . $barcode);
            if (count($examples) < 3) {
                $examples[] = $barcode;
            }
        }
        $this->observe('F12', [
            'seed' => 740012, 'locale' => 'en_US', 'validated_count' => 100,
            'examples' => $examples, 'known_valid_control' => $knownValid,
            'known_invalid_control' => $knownInvalid,
            'checksum_validator' => 'independent EAN13 implementation',
        ]);
    }

    private function nameSequence(Generator $faker, array $genders): array
    {
        $names = [];
        foreach ($genders as $gender) {
            for ($i = 0; $i < 20; ++$i) {
                $names[] = $faker->name($gender);
            }
        }
        return $names;
    }

    private static function passesLuhn(string $number): bool
    {
        if (preg_match('/\A[0-9]+\z/', $number) !== 1) {
            return false;
        }
        $sum = 0;
        $double = false;
        for ($i = strlen($number) - 1; $i >= 0; --$i) {
            $digit = (int) $number[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = !$double;
        }
        return $sum % 10 === 0;
    }

    private static function passesEan13Checksum(string $barcode): bool
    {
        if (preg_match('/\A[0-9]{13}\z/', $barcode) !== 1) {
            return false;
        }
        $weightedSum = 0;
        for ($i = 0; $i < 12; ++$i) {
            $weightedSum += (int) $barcode[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $expectedCheckDigit = (10 - $weightedSum % 10) % 10;
        return $expectedCheckDigit === (int) $barcode[12];
    }
}
