<?php
declare(strict_types=1);

/** Scenario definitions are separate from actual results parsed from PHPUnit. */
function caseCatalog(): array
{
    return [
        'TC01' => ['Name generation', 'en_US, seed 4160', '100 name values', 'Nonempty UTF-8 strings; no unresolved formatter'],
        'TC02' => ['Email syntax', 'en_US, seed 4160', '100 email values', 'Every string passes FILTER_VALIDATE_EMAIL'],
        'TC03' => ['Address generation', 'en_US, seed 4160', '100 address values', 'Nonempty UTF-8 strings containing a digit'],
        'TC04' => ['Phone basic format', 'en_US, seed 4160', '100 phoneNumber values', 'Allowed characters; 10 or 11 main-number digits'],
        'TC05' => ['Integer range', 'en_US, seed 4160', 'numberBetween(1,100), 500 samples', 'Integers in inclusive range [1,100]'],
        'TC06' => ['Equal integer bounds', 'en_US, seed 4160', 'Both bounds=-10,0,42,2147483647', 'Exactly the specified bound'],
        'TC07' => ['Float range and precision', 'en_US, seed 4160', 'randomFloat(2,-10.25,20.75), 500 samples', 'Finite floats in range, at most two decimals'],
        'TC08' => ['Numeric boundaries', 'en_US, seed 4160', 'Reversed bounds; strict 1/9 digits; zero, 100 rounds', 'Range normalized; correct digit count; zero returned'],
        'TC09' => ['Date format and calendar validity', 'UTC, fixed maximum', "date(Y-m-d,2024-12-31 23:59:59), 100 samples", 'Valid calendar date; Y-m-d; does not exceed maximum'],
        'TC10' => ['Explicit date interval', 'UTC, fixed timestamps', 'dateTimeBetween within 2020, 100 samples', 'DateTime object in inclusive interval with UTC timezone'],
        'TC11' => ['IPv4 validity', 'en_US, seed 4160', '100 ipv4 values', 'Every string passes IPv4 validation'],
        'TC12' => ['UUID format', 'en_US, seed 4160', '100 uuid values', '8-4-4-4-12 lowercase hex; v3; RFC variant'],
        'TC13' => ['en_US localization', 'Factory::create(en_US)', '100 names, postcodes and state abbreviations', 'Actual en_US providers; US postal format; two-letter state'],
        'TC14' => ['fi_FI localization', 'Factory::create(fi_FI), seed 4160', '100 names, addresses and postcodes', 'Actual fi_FI providers; UTF-8; five-digit postcode'],
        'TC15' => ['Seed reproducibility', 'UTC, fixed dates, sequential generators', '20 composite rows; seeds 4160 twice and 4161', 'Same seed gives exact same sequence; other seed differs'],
        'TC16' => ['Uniqueness and exhaustion', 'Fresh unique generator; maxRetries=10000', '100 numberBetween(1,100), then one extra', 'Exactly integers 1..100 once; extra call raises OverflowException'],
        'TC17' => ['Invalid parameter behavior', 'en_US', 'randomNumber(5,200)', 'InvalidArgumentException with fixed-width guidance'],
        'TC18' => ['Missing formatter behavior', 'en_US', 'formatterThatDoesNotExist4160()', 'InvalidArgumentException naming the unknown formatter'],
        'TC19' => ['Sustained generation', 'en_US, seed 4160', '1000 records: name,address,email,IPv4,UUID', 'All 5000 generated fields satisfy their individual constraints'],
        'TC20' => ['Performance baseline', 'PHP 7.4, en_US, seed 4160, UTC', '100 warmup rows; 3 x 1000 timed rows; 3 fields/row', '9000 measured calls; valid output; positive timings saved; no speed SLA'],
        'TC21' => ['Windows installation and execution', 'Windows, locked dependencies, PHP 7.4.33', 'Install; run TC01-TC20', 'Installation succeeds and all 20 cases pass; complete evidence'],
        'TC22' => ['macOS installation and execution', 'macOS, locked dependencies, PHP 7.4.33', 'Install; run TC01-TC20', 'Installation succeeds and all 20 cases pass; complete evidence'],
        'TC23' => ['Linux installation and execution', 'Linux, locked dependencies, PHP 7.4.33', 'Install; run TC01-TC20', 'Installation succeeds and all 20 cases pass; complete evidence'],
        'TC24' => ['Three-OS result consistency', 'One full run per OS, same code, runtime and lock', 'Compare summary.json and environment.json for three runs', 'All 20 case identities/statuses agree and pass; environment controls agree'],
    ];
}

function markdownCell(string $value): string
{
    return str_replace(["\r", "\n", '|'], ['', '<br>', '\\|'], $value);
}

function systemCaseIds(): array
{
    return array_values(array_diff(array_keys(caseCatalog()), ['TC21', 'TC22', 'TC23', 'TC24']));
}

function renderCaseTable(array $summary, array $environment, ?array $comparison = null): string
{
    $actual = [];
    foreach ($summary['cases'] as $case) {
        if ($case['id'] !== null) {
            $detail = $case['failure_reason'] ?? sprintf('%d assertions; %.6f s', $case['assertions'], $case['seconds']);
            $actual[$case['id']] = [$detail, $case['status']];
        }
    }
    $platformId = ['Windows' => 'TC21', 'Darwin' => 'TC22', 'Linux' => 'TC23'][$environment['os_family']] ?? null;
    $requiredCount = count(systemCaseIds());
    if ($platformId !== null && $summary['filter'] === null && ($summary['counts']['total'] ?? 0) === $requiredCount) {
        $actual[$platformId] = [$environment['os_family'] . '; PHP ' . $environment['php_version'] .
            '; ' . $summary['counts']['passed'] . '/' . $requiredCount . ' passed; exit ' . $summary['exit_code'], $summary['status']];
    }
    if ($comparison !== null) {
        foreach ($comparison['platform_cases'] as $id => $result) {
            $actual[$id] = [$result['actual'], $result['status']];
        }
        $actual['TC24'] = [implode('; ', $comparison['reasons']) ?: 'Three complete runs agree under recorded controls',
            $comparison['status']];
    }
    $text = "# Test cases and actual results\n\n";
    $evidencePath = $summary['evidence_path'] ?? 'results/' . $summary['run_id'];
    $text .= 'Generated from real JUnit evidence: `' . $evidencePath . '/junit.xml`.' . "\n\n";
    $text .= 'Execution: ' . $summary['started_at_utc'] . '; OS: ' . $environment['os_family'] .
        '; PHP: ' . $environment['php_version'] . '. Not Run means no execution evidence is available.' . "\n\n";
    if ($summary['report_error'] !== null) {
        $text .= 'Run error: ' . markdownCell($summary['report_error']) . ". No missing case is assumed to have passed.\n\n";
    }
    $text .= 'These are ' . count(caseCatalog()) . ' scenarios: ' . $requiredCount .
        " PHPUnit methods and four platform scenarios. A Passed TC20 establishes measurement and output validity, not a performance requirement.\n\n";
    $text .= "| ID | Test Objective | Preconditions | Input | Expected Result | Actual Result | Status |\n";
    $text .= "|---|---|---|---|---|---|---|\n";
    foreach (caseCatalog() as $id => $definition) {
        [$detail, $status] = $actual[$id] ?? ['No execution evidence', 'Not Run'];
        $cells = array_merge([$id], $definition, [$detail, $status]);
        $text .= '| ' . implode(' | ', array_map('markdownCell', $cells)) . " |\n";
    }
    return $text;
}
