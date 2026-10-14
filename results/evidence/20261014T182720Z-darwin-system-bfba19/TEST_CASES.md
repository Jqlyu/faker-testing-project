# Test cases and actual results

Generated from real JUnit evidence: `results/evidence/20261014T182720Z-darwin-system-bfba19/junit.xml`.

Execution: 2026-10-14T18:27:20+00:00; OS: Darwin; PHP: 7.4.33. Not Run means no execution evidence is available.

These are 24 scenarios: 20 PHPUnit methods and four platform scenarios. A Passed TC20 establishes measurement and output validity, not a performance requirement.

| ID | Test Objective | Preconditions | Input | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|---|
| TC01 | Name generation | en_US, seed 4160 | 100 name values | Nonempty UTF-8 strings; no unresolved formatter | 400 assertions; 0.004216 s | Passed |
| TC02 | Email syntax | en_US, seed 4160 | 100 email values | Every string passes FILTER_VALIDATE_EMAIL | 200 assertions; 0.005286 s | Passed |
| TC03 | Address generation | en_US, seed 4160 | 100 address values | Nonempty UTF-8 strings containing a digit | 500 assertions; 0.001372 s | Passed |
| TC04 | Phone basic format | en_US, seed 4160 | 100 phoneNumber values | Allowed characters; 10 or 11 main-number digits | 600 assertions; 0.000589 s | Passed |
| TC05 | Integer range | en_US, seed 4160 | numberBetween(1,100), 500 samples | Integers in inclusive range [1,100] | 2500 assertions; 0.002858 s | Passed |
| TC06 | Equal integer bounds | en_US, seed 4160 | Both bounds=-10,0,42,2147483647 | Exactly the specified bound | 4 assertions; 0.000069 s | Passed |
| TC07 | Float range and precision | en_US, seed 4160 | randomFloat(2,-10.25,20.75), 500 samples | Finite floats in range, at most two decimals | 3500 assertions; 0.003460 s | Passed |
| TC08 | Numeric boundaries | en_US, seed 4160 | Reversed bounds; strict 1/9 digits; zero, 100 rounds | Range normalized; correct digit count; zero returned | 1200 assertions; 0.001285 s | Passed |
| TC09 | Date format and calendar validity | UTC, fixed maximum | date(Y-m-d,2024-12-31 23:59:59), 100 samples | Valid calendar date; Y-m-d; does not exceed maximum | 500 assertions; 0.000749 s | Passed |
| TC10 | Explicit date interval | UTC, fixed timestamps | dateTimeBetween within 2020, 100 samples | DateTime object in inclusive interval with UTC timezone | 600 assertions; 0.001062 s | Passed |
| TC11 | IPv4 validity | en_US, seed 4160 | 100 ipv4 values | Every string passes IPv4 validation | 200 assertions; 0.000197 s | Passed |
| TC12 | UUID format | en_US, seed 4160 | 100 uuid values | 8-4-4-4-12 lowercase hex; v3; RFC variant | 100 assertions; 0.000279 s | Passed |
| TC13 | en_US localization | Factory::create(en_US) | 100 names, postcodes and state abbreviations | Actual en_US providers; US postal format; two-letter state | 602 assertions; 0.000929 s | Passed |
| TC14 | fi_FI localization | Factory::create(fi_FI), seed 4160 | 100 names, addresses and postcodes | Actual fi_FI providers; UTF-8; five-digit postcode | 902 assertions; 0.002238 s | Passed |
| TC15 | Seed reproducibility | UTC, fixed dates, sequential generators | 20 composite rows; seeds 4160 twice and 4161 | Same seed gives exact same sequence; other seed differs | 3 assertions; 0.001401 s | Passed |
| TC16 | Uniqueness and exhaustion | Fresh unique generator; maxRetries=10000 | 100 numberBetween(1,100), then one extra | Exactly integers 1..100 once; extra call raises OverflowException | 503 assertions; 0.004066 s | Passed |
| TC17 | Invalid parameter behavior | en_US | randomNumber(5,200) | InvalidArgumentException with fixed-width guidance | 2 assertions; 0.000091 s | Passed |
| TC18 | Missing formatter behavior | en_US | formatterThatDoesNotExist4160() | InvalidArgumentException naming the unknown formatter | 2 assertions; 0.000059 s | Passed |
| TC19 | Sustained generation | en_US, seed 4160 | 1000 records: name,address,email,IPv4,UUID | All 5000 generated fields satisfy their individual constraints | 11000 assertions; 0.027909 s | Passed |
| TC20 | Performance baseline | PHP 7.4, en_US, seed 4160, UTC | 100 warmup rows; 3 x 1000 timed rows; 3 fields/row | 9000 measured calls; valid output; positive timings saved; no speed SLA | 18004 assertions; 0.044673 s | Passed |
| TC21 | Windows installation and execution | Windows, locked dependencies, PHP 7.4.33 | Install; run TC01-TC20 | Installation succeeds and all 20 cases pass; complete evidence | No execution evidence | Not Run |
| TC22 | macOS installation and execution | macOS, locked dependencies, PHP 7.4.33 | Install; run TC01-TC20 | Installation succeeds and all 20 cases pass; complete evidence | Darwin; PHP 7.4.33; 20/20 passed; exit 0 | Passed |
| TC23 | Linux installation and execution | Linux, locked dependencies, PHP 7.4.33 | Install; run TC01-TC20 | Installation succeeds and all 20 cases pass; complete evidence | No execution evidence | Not Run |
| TC24 | Three-OS result consistency | One full run per OS, same code, runtime and lock | Compare summary.json and environment.json for three runs | All 20 case identities/statuses agree and pass; environment controls agree | No execution evidence | Not Run |
