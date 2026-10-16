# Current report-alignment evidence — 16 October 2026

This index covers newly implemented/executed F01–F22/N01–N02, based on the supplied PDF design. It does not replace the [historical index](README.md) or reproduce teammates' original scripts/results. [Mapping](../../docs/REPORT_CASE_MAPPING.md), [report corrections](../../docs/REPORT_CORRECTIONS.md) and [installation/run/screenshots](../../docs/RUN_GUIDE.md).

Test implementation commit: `4595ec4616e25bd364bea0a90b29bc64ed7f1393`. Source identity is additionally recorded by each environment's `test_contract_sha256`, Faker source hash and lock hash. Subsequent evidence/document commits do not change the executed contract. PDF filename/hash are in [REPORT_SOURCE.json](../../docs/REPORT_SOURCE.json). PHP7.4.33, original fzaninotto/Faker1.9.2 and PHPUnit9.6.38 were used in every current report run below.

| Actual execution context | Report result | Native PHPUnit assertions | N01 median batch seconds | N01 records/s | N02 PHP allocated peak | Run / English eight-column table |
|---|---|---:|---:|---:|---:|---|
| Local macOS26.3, arm64 |24/24 Passed|5973|0.010850542|92161.29|6291456 bytes /6MiB|[20261016T191427Z-darwin-report-8fc862](20261016T191427Z-darwin-report-8fc862/REPORT_TABLE.md)|
| GitHub Windows CI; PHP reports build26100; runner image win25-vs2026 |24/24 Passed|5973|0.029310200|34117.82|6291456 bytes /6MiB|[20261016T191433Z-windows-report-e68a07](20261016T191433Z-windows-report-e68a07/REPORT_TABLE.md)|
| GitHub macOS26.6.2 CI, arm64 |24/24 Passed|5973|0.023542042|42477.20|6291456 bytes /6MiB|[20261016T191431Z-darwin-report-dc014e](20261016T191431Z-darwin-report-dc014e/REPORT_TABLE.md)|
| GitHub Linux CI; ubuntu24 image, x86_64 |24/24 Passed|5973|0.026879418|37203.19|6291456 bytes /6MiB|[20261016T191414Z-linux-report-e4575b](20261016T191414Z-linux-report-e4575b/REPORT_TABLE.md)|
| Native local Windows |Not Run|Unavailable|Unavailable|Unavailable|Unavailable|Still requires a real device execution|
| Native local Linux / teammate VM |Not Run|Unavailable|Unavailable|Unavailable|Unavailable|CI does not reproduce that device/VM|

Each full run has24 logical report cases,24 test methods and5973 parent JUnit assertions. N01/N02 separately each validate27000 assertions in isolated workers (54000 per full run); these are not added to parent JUnit counts. Across the four full actual contexts above, there are96 report-case executions of the same24 designs. The CI-only comparison covers3 platform runs/72 case executions. Repeated filtered runs below are additional evidence, not new logical cases or new platforms.

N01 uses100 discarded warm-up rows and3×1000 retained/validated rows of name/email/uuid, en_US/UTC/seed4160 (reset after warm-up). Only generation/retention is timed;1000/median gives throughput. N02 uses a fresh PHP CLI worker with256M limit and the same one-batch retention workload. Its peak includes PHP runtime allocation, Composer/Faker and PHPUnit assertion dependencies, warm-up, generation and validation up to the documented capture point. It excludes later JSON serialization/writes. It is not OS RSS or Faker-only memory. **Both measurements completed; performance acceptance is not assessed because no threshold was specified.** Different hardware/builds affect timing; these values are observations, not a ranking or an SLA.

The old PHP Windows uname description says “Windows Server2016” despite build26100 and the win25-vs2026 runner image. The original text is retained in environment.json; it is not used to claim a Windows11 or teammate-device execution.

## Jiaqing's six and filtering evidence

[Personal six run](20261016T191428Z-darwin-report-e11a77/REPORT_TABLE.md): F13/F14/F15/F16/N01/N02 all Passed;6 methods,1383 parent assertions plus54000 worker assertions, one local macOS execution. N01 batches `[0.010949458,0.010477625,0.011132334]` seconds; median0.010949458s;91328.72 records/s. N02=6291456bytes/6MiB;256M configured limit.

[Single F13 run](20261016T191428Z-darwin-report-88f007/REPORT_TABLE.md):1 method,600 parent assertions; other23 report cases are Not Run in that table. Both `--tester="Jiaqing Lyu"` and explicit `--cases=F13,F14,F15,F16,N01,N02` were actually exercised. [Supplemental regression](20261016T190913Z-darwin-supplemental-bc813e/summary.json):20 old TC methods/41322 assertions Passed, counted separately from the report.

Every report run directory contains `environment.json`, `summary.json`, `junit.xml`, `phpunit.log`, `console.log`, both table aliases and observations. Benchmark runs also contain `measurements/N01.json`, `measurements/N02.json`, `worker-N01.log`, `worker-N02.log`. For screenshots use the actual current console output; use the matching measurement JSON for the numeric table. No screenshots have been fabricated.

## CI provenance and validation

[Successful workflow38953376535](https://github.com/Jqlyu/faker-testing-project/actions/runs/38953376535); [draft PR1](https://github.com/Jqlyu/faker-testing-project/pull/1), unmerged. [Downloaded-artifact manifest](ci-38953376535/download-manifest.json) records SHA256 values, each verified against the GitHub artifact digest. [Workflow metadata](ci-38953376535/workflow.json), [jobs](ci-38953376535/jobs.json), per-context setup logs and infrastructure JUnit are preserved in [CI provenance](ci-38953376535/PROVENANCE.md).

[Original CI comparison](ci-38953376535/original-comparison/comparison.md) is unchanged. [Recomparison](ci-38953376535/recomparison/comparison.md) validates native JUnit against summary, observation and full measurement schema,24 unique IDs, controlled versions/lock/source/test hashes, same CI run/commit, and separate contexts. CI comparison Passed; [local-device comparison](ci-38953376535/local-device-comparison/comparison.md) correctly remains Not Run because native Windows/Linux evidence is missing.

[Local verification checks](20261016T191427Z-darwin-report-8fc862/verification/checks.json) include syntax for20 project PHP files, Composer validation/platform checks, actionlint and [72 infrastructure tests/300 assertions](20261016T191427Z-darwin-report-8fc862/verification/infrastructure.log). Infrastructure fixtures are explicitly synthetic and temporary; their platform-shaped data are not execution evidence. Composer's exact-version warning is intentional because the task pins Faker1.9.2. Dependency manifests and all46 previously tracked historical evidence files were checked byte-for-byte unchanged. The source PDF is unmodified. No current upstream rerun is claimed; historical upstream evidence retains its original context.
