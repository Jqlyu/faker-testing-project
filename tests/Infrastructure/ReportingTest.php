<?php
declare(strict_types=1);

namespace FakerTesting\Infrastructure;

/** Synthetic reporting fixtures are never presented as Faker or OS execution evidence. */
final class ReportingTest extends SyntheticFixtureTestCase
{
    public function testJunitPreservesFailureErrorAndSkipReasons(): void
    {
        $path = $this->temporary . '/synthetic.xml';
        file_put_contents($path, '<testsuites><testsuite name="Synthetic">' .
            '<testcase name="pass" time="0.1" assertions="1"/>' .
            '<testcase name="fail"><failure>Expected range; got 999</failure></testcase>' .
            '<testcase name="error"><error>Dependency unavailable</error></testcase>' .
            '<testcase name="skip"><skipped>Feature unavailable</skipped></testcase>' .
            '</testsuite></testsuites>');
        $parsed = \readJunit($path);
        $this->assertSame(['total' => 4, 'passed' => 1, 'failed' => 1, 'errors' => 1, 'skipped' => 1], $parsed['counts']);
        $this->assertSame('Expected range; got 999', $parsed['cases'][1]['failure_reason']);
        $this->assertSame('Dependency unavailable', $parsed['cases'][2]['failure_reason']);
        $this->assertSame('Feature unavailable', $parsed['cases'][3]['failure_reason']);
        $this->assertSame(['Passed', 'Failed', 'Error', 'Skipped'], array_column($parsed['cases'], 'status'));
        $this->assertSame(1, $parsed['cases'][0]['assertions']);
        $this->assertSame(0.1, $parsed['cases'][0]['seconds']);
        $this->assertNull($parsed['cases'][0]['failure_reason']);
    }

    public function testJunitSeparatesReportIdsFromSupplementalTcIds(): void
    {
        $names = ['testF01Names', 'testF22DateRange', 'testN01Throughput', 'testN02Memory',
            'testTC01LegacyName', 'testF010WrongWidth', 'contestF01Names', 'testNames',
            'testF09 with data set "synthetic"'];
        $xml = '<testsuites><testsuite name="SyntheticFallbackClass::suite">';
        foreach ($names as $index => $name) {
            $xml .= '<testcase name="' . htmlspecialchars($name, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '"' .
                ($index === 0 ? ' class="SyntheticExplicitClass"' : '') .
                ($index === 1 ? ' classname="SyntheticAlternateClass"' : '') . '/>';
        }
        $parsed = \readJunit($this->fixture('synthetic-identities.xml', $xml . '</testsuite></testsuites>'));
        $this->assertSame(['F01', 'F22', 'N01', 'N02', 'TC01', null, null, null, 'F09'],
            array_column($parsed['cases'], 'id'));
        $this->assertSame('SyntheticExplicitClass', $parsed['cases'][0]['class']);
        $this->assertSame('SyntheticAlternateClass', $parsed['cases'][1]['class']);
        $this->assertSame('SyntheticFallbackClass', $parsed['cases'][2]['class']);
    }

    public function testJunitCountsObservedCasesInsteadOfTrustingSuiteTotals(): void
    {
        $path = $this->fixture('synthetic-untrusted-totals.xml',
            '<testsuites tests="24" failures="0"><testsuite tests="24" failures="0">' .
            '<testcase name="testF01Synthetic"><failure message="actual failure"/></testcase>' .
            '<testcase name="testN02Synthetic"><skipped message="not executed"/></testcase>' .
            '</testsuite></testsuites>');
        $parsed = \readJunit($path);
        $this->assertSame(['total' => 2, 'passed' => 0, 'failed' => 1, 'errors' => 0, 'skipped' => 1], $parsed['counts']);
        $this->assertSame('actual failure', $parsed['cases'][0]['failure_reason']);
        $this->assertSame('not executed', $parsed['cases'][1]['failure_reason']);
    }

    public function testJunitErrorCannotBeHiddenByASkipOrFailureNode(): void
    {
        $path = $this->fixture('synthetic-overlapping-status.xml',
            '<testsuites><testsuite><testcase name="testF15Synthetic">' .
            '<error>Worker crashed</error><skipped>Also marked skipped</skipped>' .
            '<failure>Also marked failed</failure></testcase></testsuite></testsuites>');
        $parsed = \readJunit($path);
        $this->assertSame('Error', $parsed['cases'][0]['status']);
        $this->assertSame(['total' => 1, 'passed' => 0, 'failed' => 0, 'errors' => 1, 'skipped' => 0], $parsed['counts']);
        foreach (['Worker crashed', 'Also marked skipped', 'Also marked failed'] as $reason) {
            $this->assertStringContainsString($reason, $parsed['cases'][0]['failure_reason']);
        }
    }

    public function testZeroCasesCannotBecomeSuccessfulExecution(): void
    {
        $path = $this->temporary . '/synthetic-empty.xml';
        file_put_contents($path, '<testsuites/>');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no test cases');
        \readJunit($path);
    }

    public function testTruncatedReportCannotBecomeSuccessfulExecution(): void
    {
        $path = $this->temporary . '/synthetic-truncated.xml';
        file_put_contents($path, '<testsuites><testsuite>');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid XML');
        \readJunit($path);
    }

    public function testMissingReportCannotBecomeSuccessfulExecution(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JUnit report missing');
        \readJunit($this->temporary . '/synthetic-missing.xml');
    }

    public function testChildExitCodeIsPreserved(): void
    {
        $result = \command([PHP_BINARY, '-r', 'fwrite(STDERR, "intentional reporting self-check"); exit(7);'], $this->temporary);
        $this->assertSame(7, $result['exit_code']);
        $this->assertSame('intentional reporting self-check', $result['output']);
    }

    public function testContractHashCanIgnoreOsMetadataWhileDefaultTreeHashRetainsEveryFile(): void
    {
        $source = $this->fixture('synthetic-contract/nested/Example.php', "<?php\nreturn 'first synthetic source';\n");
        $directory = $this->temporary . '/synthetic-contract';
        $ignored = ['.DS_Store', 'Thumbs.db'];
        $sourceOnlyHash = \treeHash($directory, $ignored);
        $this->assertSame(\treeHash($directory), $sourceOnlyHash);

        $macMetadata = $this->fixture('synthetic-contract/.DS_Store', 'synthetic Finder metadata v1');
        $windowsMetadata = $this->fixture('synthetic-contract/nested/Thumbs.db', 'synthetic thumbnail metadata v1');
        $withMetadataHash = \treeHash($directory);
        $this->assertSame($sourceOnlyHash, \treeHash($directory, $ignored));
        $this->assertNotSame($sourceOnlyHash, $withMetadataHash);

        file_put_contents($macMetadata, 'synthetic Finder metadata v2');
        file_put_contents($windowsMetadata, 'synthetic thumbnail metadata v2');
        $changedMetadataHash = \treeHash($directory);
        $this->assertSame($sourceOnlyHash, \treeHash($directory, $ignored));
        $this->assertNotSame($withMetadataHash, $changedMetadataHash);
        $this->assertSame(\treeHash($directory, []), $changedMetadataHash);

        file_put_contents($source, "<?php\nreturn 'changed meaningful synthetic source';\n");
        $this->assertNotSame($sourceOnlyHash, \treeHash($directory, $ignored));
        $this->assertNotSame($changedMetadataHash, \treeHash($directory));
    }

    public function testLegacyNativeEventsRetainSkippedCases(): void
    {
        $path = $this->temporary . '/synthetic-legacy-events.json';
        $event = ['event' => 'test', 'test' => 'Synthetic::testSkipped', 'status' => 'error',
            'message' => 'Skipped Test: external service unavailable', 'time' => 0.01];
        file_put_contents($path, json_encode(['event' => 'suiteStart']) . json_encode($event));
        $parsed = \includeUpstreamSkippedCases(['counts' => ['total' => 0, 'passed' => 0,
            'failed' => 0, 'errors' => 0, 'skipped' => 0], 'cases' => []], $path);
        $this->assertSame(1, $parsed['counts']['total']);
        $this->assertSame(1, $parsed['counts']['skipped']);
        $this->assertSame('Skipped', $parsed['cases'][0]['status']);
        $this->assertSame('external service unavailable', $parsed['cases'][0]['failure_reason']);
    }
}
