# Testing Environment

当前固定基线和实际运行环境必须分别记录。最新 PDF 提及的历史设备/结果属于报告来源，不能填写为新实现的环境；状态矛盾见 [REPORT_CORRECTIONS.md](REPORT_CORRECTIONS.md)。安装和执行命令已从项目根 README 迁到 [RUN_GUIDE.md](RUN_GUIDE.md)。

## Baseline for the new report implementation

| Component | Required baseline / control | Evidence source |
|---|---|---|
| Software under test | fzaninotto/Faker v1.9.2 | `sut.json`, installed package |
| Faker commit | `848d8125239d7dbf8ab25cb7f054f1a630e68c2e` | source tree identity / environment snapshot |
| PHP | 7.4.33, 64 bit | actual `PHP_VERSION`, binary, OS/architecture |
| Composer | 2.10.3 | verified PHAR, installation logs |
| Report and supplemental PHPUnit | 9.6.38 | Composer dependency / actual runner |
| Original upstream PHPUnit | 5.7.27 | separate verified PHAR |
| Dependencies | 29 locked packages | `composer.lock`, [DEPENDENCIES.md](DEPENDENCIES.md), `environment.json` |
| Process timezone / locale | UTC / C | actual runtime controls |
| Faker locale | en_US by default; F05 fi_FI | per-case observation |
| Seeds | F01–F12: 740001–740012; F13–F16: 4160; F20: 1234; other choices documented | [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md), observation/measurement metadata |
| Memory configuration | report/supplemental 256M; upstream 512M; N02 fresh worker 256M | actual ini/worker snapshot; configuration is not a measured result |

Faker 1.9.2 declares PHP `^5.3.3 || ^7.0`; that does not include PHP 8. The initial master HEAD `5ffe7db6c80f441f150fc88008d64e64af66634b` had different constraints, so the fixed release/commit is the authority here. No PHP 8 compatibility conclusion follows from this baseline. Faker's archived repository and PHP 7.4 are historical course dependencies; no replacement with fakerphp/faker is implied.

The final local and CI runs use commit `9178ced170507cc49b6d20f274996abbf1cf7a1f`, which corrects the test contract fingerprint to exclude only `.DS_Store` and `Thumbs.db`. Earlier local Finder metadata had made its contract hash differ from CI despite identical Git test code. Those actual Passed records remain preserved as earlier attempts, without asserting equal hashes. The final local-Darwin and three GitHub CI contexts now all record `bce51e785ade59283ea5338a72c95cc26e864cbd000fa8a00e4ba3a18f36028c`; native Windows/Linux still have no new run. Faker's strict SUT source hashing is unchanged. See [TOOL-002](BUG_REPORT.md#tool-002--desktop-metadata-entered-the-test-contract-fingerprint).

## Separate execution contexts

| Context | Available information | Interpretation |
|---|---|---|
| Historical repository macOS evidence | macOS 26.3 (25D125), Darwin 25.3.0, arm64, PHP 7.4.33; TC supplemental/upstream runs | See immutable [evidence index](../results/evidence/README.md); predates this report implementation |
| PDF macOS F01–F06 | reports six passes / 2685 assertions | Original report statement only; original member implementation is unavailable here |
| PDF Linux F07–F12 / selected upstream study | reports Ubuntu 24.04.5 LTS ARM64 VM, PHP 7.4.33, PHPUnit 9.6.38 | Do not transfer the VM identity or reported results to this Mac |
| PDF Windows CI F13–F16 / N01 | reported CI context; F14/F15 details need confirmation; N01 numeric values absent | CI evidence requires exact workflow/run artifacts; not proof of native Windows 11 |
| PDF Windows 11 F17–F22 | reports standalone PHP script, PHP 7.4.33 | Separate from PHPUnit CI; original script/device evidence absent from current code |
| New report F01–F22 / N01–N02, local-Darwin | macOS 26.3, arm64, PHP 7.4.33; full run `20261016T192024Z-darwin-report-5cde49`: 24/24 Passed | [Actual environment](../results/evidence/20261016T192024Z-darwin-report-5cde49/environment.json), 5973 parent assertions and separately 54000 worker assertions |
| New Jiaqing subset, local-Darwin | `20261016T192025Z-darwin-report-09eaaf`: 6/6 Passed; single F13 `20261016T192026Z-darwin-report-359025`: 1/1 Passed | Six: 1383 parent + separately 54000 worker assertions; single F13: 600 parent assertions; neither is a Windows execution |
| New native Windows / Linux report execution | Not Run; no current local-Windows/local-Linux full reports | Setup instructions do not establish execution |
| New GitHub Windows CI | `20261016T192055Z-windows-report-a44ee0`: 24/24 Passed; AMD64; ImageOS `win25-vs2026`, image `20261007.264.1` | [Actual environment](../results/evidence/20261016T192055Z-windows-report-a44ee0/environment.json); PHP's raw legacy text is build 26100 (Windows Server 2016), preserved as captured; do not rename to Windows 11 |
| New GitHub macOS CI | `20261016T192043Z-darwin-report-7c5360`: 24/24 Passed; macOS 26.6.2 / arm64; image `macos26` / `20260907.0351.1` | [Actual environment](../results/evidence/20261016T192043Z-darwin-report-7c5360/environment.json); distinct from local macOS 26.3 |
| New GitHub Linux CI | `20261016T192030Z-linux-report-93e7c3`: 24/24 Passed; x86_64; kernel 6.17.0-1022-azure; image `ubuntu24` / `20261012.334.1` | [Actual environment](../results/evidence/20261016T192030Z-linux-report-93e7c3/environment.json); distinct from PDF ARM64 Ubuntu VM |
| New GitHub Actions comparison | [Workflow run 38954165253](https://github.com/Jqlyu/faker-testing-project/actions/runs/38954165253) and local re-comparison Passed; same code commit `9178ced170507cc49b6d20f274996abbf1cf7a1f` | Current [evidence/provenance index](../results/evidence/REPORT_ALIGNMENT_20261016.md); 3 CI platform executions / 72 case executions, still 24 logical IDs |

The historical Mac PHP came from `shivammathur/php/php@7.4`, Homebrew formula revision `7.4.33_15`, build date Oct 9 2026. Its path is a machine fact, not a portable project setting. Newly generated `environment.json` is the authority for later execution, including actual PHP binary/build and architecture.

OS/architecture originate from PHP and optional native OS commands. Unavailable optional fields remain null. CPU model, total RAM and virtualization details must be added from actual machine evidence if they are needed to discuss performance; do not infer hardware from an OS label. A tester responsibility selection does not establish who operated the computer or which historical machine was used.

## CI controls and limitations

The workflow **Faker cross-platform report tests** targets Windows/macOS/Ubuntu with the fixed PHP/Composer/lock and records actual runner/run/commit context when available. Fixed Action references and LF source files improve reproducibility, but `*-latest` does not freeze OS image, hardware or installation-source availability. Preserve failed setup/test logs as well as successful artifacts. Missing, filtered or incompatible reports cannot support a three-platform pass.

Remote execution can be inspected on the repository's [Actions page](https://github.com/Jqlyu/faker-testing-project/actions); this update uses downloaded and verified artifacts from completed run 38954165253. Each CI OS records PHP 7.4.33 / PHPUnit 9.6.38, 5973 parent assertions and separately 54000 worker assertions. The six platform keys are `local-Windows`, `local-Darwin`, `local-Linux`, `github-Windows`, `github-Darwin`, `github-Linux`; Darwin means macOS. Comparison defaults to `--context=github-actions` and requires one workflow run/commit; `--context=local-device` separately requires three local OS reports. The completed CI comparison does not fill the missing native Windows/Linux slots. WSL/Linux and a Linux VM remain Linux executions, so their machine/virtualization details should also be recorded. The source PDF's common-version table is a stated baseline, not evidence that every historical run used every listed tool.

N01 records generation/retention timing and throughput; N02 records fresh-worker PHP allocated peak bytes/MiB, not total OS RSS or Faker-exclusive memory. The worker loads Composer, Faker, PHPUnit assertion classes and their dependencies but does not start a PHPUnit runner; its 27,000 validation assertions per workload are recorded separately from parent JUnit assertions. Workload, retention, worker overhead and validation affect interpretation. `memory_limit=256M` describes execution configuration and does not define a memory acceptance criterion. No speed/memory SLA has been specified.

Historical evidence may contain the deleted root README link. Those snapshots are preserved unchanged; current instructions are in [RUN_GUIDE.md](RUN_GUIDE.md).
