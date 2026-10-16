<?php
declare(strict_types=1);

/** The PDF defines designs/ownership; actual results come only from a new run. */
function caseCatalog(): array
{
    $zhiyan = 'Zhiyan Chang';
    $hao = 'Haochang Shi';
    $jiaqing = 'Jiaqing Lyu';
    $jingyi = 'Jingyi Zhu';
    $make = static function (string $name, string $tester, int $seed, string $locale, string $input,
        string $expected, string $notes = ''): array {
        return ['name' => $name, 'tester' => $tester, 'seed' => $seed, 'locale' => $locale,
            'preconditions' => 'Faker v1.9.2; PHP 7.4.33; PHPUnit 9.6.38; UTC; ' . $locale . '; seed ' . $seed,
            'inputs' => $input, 'expected' => $expected,
            'notes' => $notes . ' New implementation/execution from the supplied report design; historical results are not reused.'];
    };
    return [
        'F01' => $make('Names, genders and seeded sequence replay', $zhiyan, 740001, 'en_US',
            'null/male/female; 20 names each; replay all 60 after reseeding',
            'Nonempty strings; identical 60-value replay', 'Original design context: native macOS.'),
        'F02' => $make('Safe email syntax and example domains', $zhiyan, 740002, 'en_US',
            '100 safeEmail() calls', 'All valid emails at example.com, example.net or example.org'),
        'F03' => $make('UTC date boundaries, singleton and reversed interval', $zhiyan, 740003, 'en_US',
            '100 dates: 2020-02-28 00:00:00 to 2020-03-01 23:59:59 UTC; equal 2020-02-29 12:00:00; reversed bounds',
            'DateTime in UTC inclusive range; equal timestamp returned; reverse throws InvalidArgumentException',
            'Exact times chosen because the PDF specifies only the dates.'),
        'F04' => $make('Integer boundary partitions', $zhiyan, 740004, 'en_US',
            '8 pairs x20: [1,100],[-100,-1],[-10,10],[0,0],[42,42],[100,1],[PHP_INT_MIN,PHP_INT_MIN],[PHP_INT_MAX,PHP_INT_MAX]',
            'Integers in normalized inclusive range; singleton equals its bound',
            'PDF omits exact pairs/sample count; these are explicit new choices.'),
        'F05' => $make('Finnish postcodes and regions', $zhiyan, 740005, 'fi_FI',
            '100 postcode/state pairs', 'Five ASCII postcode digits; state in pinned list of 19 Finnish regions'),
        'F06' => $make('UUID v3 format, RFC variant and sample uniqueness', $zhiyan, 740006, 'en_US',
            '100 uuid() values', 'Lowercase 8-4-4-4-12; version3; RFC variant; no duplicate in this sample'),
        'F07' => $make('Regexify pattern partitions', $hao, 740007, 'en_US',
            '50 each: [A-Z]{2}[0-9]{3}; (cat|dog)-[0-9]{2}; [A-F]{1,4}',
            'All150 values match their full corresponding pattern', 'Original design context: Linux.'),
        'F08' => $make('Random elements and duplicate policy', $hao, 740008, 'en_US',
            'Source alpha/beta/gamma/delta/epsilon; counts0,3,5,6; duplicates true/false',
            'Exact count and source membership; unique when prohibited; six without duplicates throws LengthException',
            'Source values/repetitions are new choices. Allowed duplicates do not require chance repetition;6 from5 forces it.'),
        'F09' => $make('IPv4 and IPv6 validation', $hao, 740009, 'en_US',
            '100 IPv4 plus100 IPv6', 'String values pass independent PHP validation for the matching IP version'),
        'F10' => $make('Credit card formats and independent Luhn checksum', $hao, 740010, 'en_US',
            '25 each: Visa, MasterCard, American Express, Discover Card',
            'All100 values have vendor format and valid independent Luhn checksum',
            'Use actual API key Discover Card; MasterCard supports its documented2-series.'),
        'F11' => $make('Paragraph count, content and output modes', $hao, 740011, 'en_US',
            'Counts0,1,3,5; array/text modes; reseed before each paired mode',
            'Exact count; nonempty paragraphs end in periods; text uses two LF separators;0 gives[]/empty string'),
        'F12' => $make('EAN13 and independent checksum validator controls', $hao, 740012, 'en_US',
            '100 ean13(); valid4006381333931; invalid4006381333932',
            'Exactly13 ASCII digits; valid check digit; known valid accepted and invalid rejected',
            'The PDF omits exact control fixtures; these are new explicit choices.'),
        'F13' => $make('UTC date range: 100 samples', $jiaqing, 4160, 'en_US',
            '100 dates: 2020-01-01 00:00:00 to2020-12-31 23:59:59 UTC',
            'DateTime values in inclusive interval with UTC timezone', 'Original design context: Windows CI.'),
        'F14' => $make('UUID lowercase format, version and variant', $jiaqing, 4160, 'en_US',
            '100 uuid() values', 'Lowercase 8-4-4-4-12 format, v3 and RFC variant',
            'Independent execution from F06; historical Windows UUID evidence needs confirmation.'),
        'F15' => $make('Dedicated safeEmail evidence: 100 samples', $jiaqing, 4160, 'en_US',
            '100 safeEmail() values', 'Valid syntax and example.com/example.net/example.org domains',
            'Ordinary email TC02 cannot substitute. Independent from F02; historical Windows evidence unconfirmed.'),
        'F16' => $make('Independent IPv4 validation: 100 samples', $jiaqing, 4160, 'en_US',
            '100 ipv4() values', 'Strings pass FILTER_VALIDATE_IP with FILTER_FLAG_IPV4'),
        'F17' => $make('Ten unique random digits', $jingyi, 4160, 'en_US',
            '10 unique()->randomDigit() values', 'Integers0..9, no duplicates, all10 digits represented',
            'Seed4160 is a new reproducibility choice; original design context: local Windows11.'),
        'F18' => $make('Optional corrected integer and fractional weights', $jingyi, 4160, 'en_US',
            'Integer0/100 with DEFAULT/PROVIDER; weight0.5 x200',
            '0 returns DEFAULT;100 returns PROVIDER; both fractional outcomes; provider count50..150 inclusive',
            'Use corrected design. Historical106/200 is not an expected exact result. Seed is a new choice.'),
        'F19' => $make('Valid even digits', $jingyi, 4160, 'en_US',
            '20 valid(even predicate)->randomDigit() values', 'Even integers in0..9', 'Seed is a new choice.'),
        'F20' => $make('Seed1234 ten-digit sequence replay', $jingyi, 1234, 'en_US',
            'Generate10 random digits; reseed1234; generate10 again',
            'Both sequences contain10 digits and are identical in order; no historical digits hard-coded'),
        'F21' => $make('Bothify invoice identifier', $jingyi, 4160, 'en_US',
            '20 bothify(INV-##??) values', 'INV- followed by2 ASCII digits and2 lowercase letters', 'Seed is a new choice.'),
        'F22' => $make('Date range: 20 samples and date-only endpoints', $jingyi, 4160, 'en_US',
            '20 dateTimeBetween(2020-01-01,2020-12-31,UTC)',
            'Dates in inclusive parsed interval ending2020-12-31 00:00:00 UTC',
            'Seed/timezone are new choices. Literal date-only endpoints differ from F13.'),
        'N01' => $make('Fixed-workload generation time and throughput', $jiaqing, 4160, 'en_US',
            '100 warm-up;3 batches x1000 rows; name,email,uuid; one retained batch; generation only timed',
            'Validated output;3 positive batch seconds; median; records/s=1000/median; measurement completed',
            'No predefined speed criterion; performance acceptance remains not assessed.'),
        'N02' => $make('Isolated fixed-workload PHP allocated memory peak', $jiaqing, 4160, 'en_US',
            'Fresh PHP worker,256M config;100 warm-up;3x1000 name/email/uuid rows; one retained batch',
            'No OOM/worker/report error; peak allocated PHP memory in bytes and MiB; actual limit and process scope recorded',
            'Includes loaded dependencies and validation; not Faker-exclusive memory or OS RSS.256M is configuration, not acceptance.'),
    ];
}

function systemCaseIds(): array
{
    return array_keys(caseCatalog());
}

function testerCaseIds(string $tester): array
{
    return array_keys(array_filter(caseCatalog(), static fn(array $case): bool => $case['tester'] === $tester));
}

function markdownCell(string $value): string
{
    return str_replace(["\r", "\n", '|'], ['', '<br>', '\\|'], $value);
}

function observedOutput(string $id, array $observation): string
{
    $data = $observation['data'] ?? $observation;
    if ($id === 'N01' && isset($data['median_seconds'], $data['records_per_second'])) {
        return sprintf('Measurement completed; batches=%s s; median=%.9f s; %.2f records/s; no speed criterion assessed',
            json_encode($data['batch_seconds']), $data['median_seconds'], $data['records_per_second']);
    }
    if ($id === 'N02' && isset($data['peak_allocated_memory_bytes'])) {
        return sprintf('Measurement completed; PHP allocated peak=%d bytes (%.3f MiB); limit=%s; worker PID=%s; no memory criterion assessed',
            $data['peak_allocated_memory_bytes'], $data['peak_allocated_memory_mib'], $data['memory_limit'], $data['worker_pid']);
    }
    if ($id === 'F18' && isset($data['provider_count'])) {
        return sprintf('Weight0=%s;100=%s; fractional PROVIDER=%d/200; DEFAULT=%d/200',
            $data['weight_0'], $data['weight_100'], $data['provider_count'], $data['default_count']);
    }
    if ($id === 'F20' && isset($data['first'], $data['second'])) {
        return 'Two10-digit sequences matched: ' . json_encode($data['first']);
    }
    $count = $data['validated_count'] ?? $data['sample_count'] ?? $data['total_samples'] ?? null;
    $brief = $count === null ? 'Recorded observation: ' : 'Validated ' . $count . '; ';
    $examples = $data['examples'] ?? $data['values'] ?? null;
    if (is_array($examples)) {
        return $brief . 'examples=' . json_encode(array_slice($examples, 0, 3), JSON_UNESCAPED_UNICODE);
    }
    $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $brief . (strlen($encoded) > 550 ? substr($encoded, 0, 550) . '...; see observation JSON' : $encoded);
}

function renderCaseTable(array $summary, array $environment, ?array $comparison = null): string
{
    $actual = [];
    foreach ($summary['cases'] as $case) {
        $actual[$case['id']] = $case;
    }
    $evidence = $summary['evidence_path'] ?? 'results/' . $summary['run_id'];
    $context = $environment['execution_context']['label'] ?? $environment['os_family'];
    $text = "# Current report-case evidence\n\n";
    $text .= 'Run: `' . $summary['run_id'] . '`; ' . $summary['started_at_utc'] . '; context: **' . $context .
        '**; PHP ' . $environment['php_version'] . '. Evidence: `' . $evidence . '/junit.xml`.' . "\n\n";
    $text .= "Tester names indicate report responsibility, not authorship/execution of this new implementation. Historical report PASS/counts/times are not reused. Not Run means absent from this execution. A measurement completed status does not establish performance acceptance.\n\n";
    $text .= '| ID | Tester | Feature being tested | Preconditions | Inputs | Expected Output | Actual Output | Purpose and notes |' . "\n";
    $text .= '|---|---|---|---|---|---|---|---|' . "\n";
    foreach (caseCatalog() as $id => $design) {
        $case = $actual[$id] ?? null;
        $result = 'Not Run in this execution';
        if ($case !== null) {
            $detail = $summary['observations'][$id] ?? [];
            $result = $case['status'] . '; ' . $case['assertions'] . ' parent PHPUnit assertions; ' .
                sprintf('%.6f s', $case['seconds']) . '; ' . ($case['failure_reason'] ?? observedOutput($id, $detail));
        }
        $cells = [$id, $design['tester'], $design['name'], $design['preconditions'], $design['inputs'],
            $design['expected'], $result, $design['notes'] . ' Execution context: ' . $context . '.'];
        $text .= '| ' . implode(' | ', array_map('markdownCell', $cells)) . " |\n";
    }
    if ($summary['report_error'] !== null) {
        $text .= "\nRun error: " . markdownCell($summary['report_error']) . "\n";
    }
    return $text;
}
