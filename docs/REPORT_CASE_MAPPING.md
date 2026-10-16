# Report ID mapping (prepared before implementation)

Source: latest supplied Group666 report, detailed tables pp19-26. The PDF is read-only. Its PASS labels, sample values, assertion counts and timings are historical statements, not this execution. Teammates original F-numbered implementations are absent from the current repository; the report-aligned suite is a new implementation from the stated design.

| Report ID | Original code | Required implementation |
|---|---|---|
| F01 | TC01 partial; TC15 uses a different sequence | Seed740001; null/male/female x20 names; replay all60 |
| F02 | TC02 is ordinary email; not equivalent | New dedicated100 safeEmail, seed740002, syntax + reserved domains |
| F03 | TC10 only covers a different interval | Seed740003,100 Feb28-Mar1 UTC; equal and reverse endpoints |
| F04 | TC05/TC06/TC08 partial | Seed740004; eight explicitly documented signed/zero/reversed/extreme/singleton partitions |
| F05 | TC14 only partly covers Finnish locale | Seed740005;100 postcode/state pairs; pin19-region list |
| F06 | TC12 format checks reusable | Seed740006;100 UUIDs; add sample uniqueness |
| F07 | Missing | Seed740007; three exact PDF regexes,50 samples each |
| F08 | Missing | Seed740008; five-source array, counts0/3/5/6, duplicate modes, LengthException |
| F09 | TC11 covers IPv4 only | Seed740009;100 IPv4 and100 IPv6; independent validation |
| F10 | Missing | Seed740010; four card types x25; independent Luhn validation |
| F11 | Missing | Seed740011; counts0/1/3/5, arrays/text, periods and separators |
| F12 | Missing | Seed740012;100 EAN13; independent check digit + known valid/invalid controls |
| F13 | TC10 behavior and inputs correspond | Reuse constraints in a separately executed report method;100 UTC dates;seed4160 |
| F14 | TC12 behavior and inputs correspond | Reuse constraints in a separately executed report method;100 UUIDs;seed4160 |
| F15 | Missing; TC02 cannot substitute | New100 safeEmail method;seed4160; its own observation and JUnit record |
| F16 | TC11 behavior and inputs correspond | Reuse constraints in a separately executed report method;100 IPv4;seed4160 |
| F17 | TC16 tests1..100 plus exhaustion; not equivalent | New ten unique randomDigit values0..9; no historical-output assertion |
| F18 | Missing | Integer0/100 with DEFAULT/PROVIDER;0.5 x200; provider count50..150 |
| F19 | Missing | 20 valid() even random digits, range0..9 |
| F20 | TC15 composite rows are not equivalent | Seed1234; two ten-randomDigit sequences compared in order |
| F21 | Missing | 20 bothify(INV-##??) strings |
| F22 | TC10 partial; sample count and endpoints differ | Separate20 dateTimeBetween calls with literal2020-01-01/2020-12-31 |
| N01 | TC20 has matching workload, mixed evidence naming | Dedicated result:100 warm-up,3x1000 rows[name,email,uuid]; timing and throughput |
| N02 | TC20 full-suite process peak is not isolated | New fresh PHP worker,256M config; same workload; allocated peak bytes/MiB |

## Retained supplemental tests

All20 old TC methods remain as a separate supplemental suite. Address/phone/float/date-format/en_US validation, composite-sequence replay,1..100 uniqueness/exhaustion, invalid parameters/formatters, sustained records, and the legacy benchmark remain useful but are not the report24. Old TC21-TC24 platform scenarios become compatibility evidence/context, not new F/N logical IDs. Historical evidence is unchanged.

## Explicit choices where the PDF does not specify an input

- Default locale=en_US, timezone=UTC. F17-F19/F21-F22 use new reproducibility seed4160; this does not claim a historical seed or identical past outputs.
- F03: start2020-02-28 00:00:00,end2020-03-01 23:59:59 UTC; equal endpoint2020-02-29 12:00:00 UTC.
- F04: the PDF names eight partitions but omits exact pairs/counts. New pairs are[1,100],[-100,-1],[-10,10],[0,0],[42,42],[100,1],[PHP_INT_MIN,PHP_INT_MIN],[PHP_INT_MAX,PHP_INT_MAX];20 draws per partition. This is a new documented partition selection, not reconstruction of780 historical assertions.
- F08: source[alpha,beta,gamma,delta,epsilon]; each specified count/mode once. Six draws with duplicates must contain a repeated value by the pigeonhole principle. Six without duplicates must throw. No assumption that a short allowed-duplicate sample must happen to repeat.
- F11: one call per count/mode, reseeded to740011 before paired array/text calls so the exact separator can be independently checked.
- F12: known valid4006381333931; known invalid4006381333932 (changed check digit). They are new explicit control fixtures; the PDF did not supply its historical fixtures.
- F22: use literal date-only endpoints in UTC. The end parses to2020-12-31 00:00:00; it is not silently expanded to23:59:59. F13 explicitly uses the latter endpoint.
- N01/N02: seed4160 before warm-up, reset4160 before the three measured batches; retain one1000-record batch at a time, validate after timing, then release it. Worker/validation overhead and retention are recorded, without a speed/memory SLA.

Ownership is report responsibility only. Every new run records its actual OS/execution context separately; running another tester's case on this Mac does not reproduce their Linux/Windows historical execution.
