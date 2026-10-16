# Final Report Notes — LUT CT60A4160

按最新 PDF 的 F01–F22/N01–N02 组织报告，引用本次实际证据并注明这是根据报告设计的新实现。原成员 F 编号代码未随当前仓库提供，不能把重实现写成其原程序复跑。PDF 保持原样；应修改的论述列在 [REPORT_CORRECTIONS.md](REPORT_CORRECTIONS.md)。执行方法见 [RUN_GUIDE.md](RUN_GUIDE.md)。

## 1. Software and testing scope

对象为 fzaninotto/Faker v1.9.2，MIT，commit `848d8125239d7dbf8ab25cb7f054f1a630e68c2e`。系统边界是开发者安装并使用真实公共 API。报告主套件为 22 个功能用例和 2 个非功能用例；不把旧 TC 平台场景、原有测试或工具检查加进这 24 项。范围和限制见 [TEST_PLAN.md](TEST_PLAN.md)。

## 2. Testing strategy

类型/格式/区间/结构约束、等价类、边界值、独立 IP/Luhn/EAN 校验、固定版本/时区和每项 seed。输入没有在 PDF 明确的地方采用事先公开的选择，见 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md)。F13/F14/F16 复用相关旧约束但单独执行，F15 独立调用 safeEmail；旧 TC02 的 email 结果不能作 F15 证据。

## 3. Testing environment and tools

新实现基线为 PHP 7.4.33、Composer 2.10.3、PHPUnit 9.6.38 和锁定 29 个依赖；upstream 独立 PHPUnit 5.7.27。以对应 `environment.json` 写实际 OS、架构、PHP binary/build、执行上下文和版本。PDF 的 Linux VM、Windows CI、Windows 11 standalone 和本机 macOS 分开，不能从成员责任倒推出执行环境。详见 [ENVIRONMENT.md](ENVIRONMENT.md)。

## 4. Existing tests

明确区分两项研究：PDF 在 Linux/PHPUnit 9.6.38 下选两个文件（87 tests、127 assertions、16 errors、21 warnings），以及本仓库历史 macOS/PHPUnit 5.7.27 完整运行（2078 实例，2076 passed、2 skipped、0 failed/errors）。后者不是前者的复现，也不证明 PDF 的错误消失。原有用例未修改，框架兼容错误不直接视作 Faker 产品缺陷。详见 [EXISTING_TESTS.md](EXISTING_TESTS.md)。

## 5. Written tests

报告方法在 `tests/Report/FirstFunctionalReportTest.php`、`AdditionalFunctionalReportTest.php` 和 `NonFunctionalReportTest.php`，共对应 24 个 F/N ID；公共证据辅助在 `tests/Support/ReportTestCase.php`。旧 20 个方法位于 `tests/Supplemental/FakerSystemTest.php`，通过 `--suite=supplemental` 单独运行。上游和报告基础设施也单独运行、单独计数。

N01 为 100 行预热、3×1000 行，每行 name/email/uuid，生成/retention 计时外做校验；记录各批 seconds、median_seconds、records_per_second。N02 必须是新的 256M PHP worker，使用同工作负载并记录 peak_allocated_memory_bytes 和 peak_allocated_memory_mib。报告 `memory_limit`、retention 和 worker/validation 口径：worker 加载 Composer/Faker/PHPUnit Assert 及依赖，不启动 PHPUnit runner；峰值不是 Faker 独占内存或 OS RSS。每个 worker 的 27,000 校验断言与父 JUnit 断言分开，不能混作一个原生 PHPUnit 总数。成功表示测量有效，performance criteria not assessed；不存在预设的速度/内存 SLA，256M 不是内存目标。

## 6. Test cases and actual results

2026-10-16 新实现已在 **local-Darwin / macOS 26.3 / arm64 / PHP 7.4.33** 完成以下独立运行；代码基线为 `9178ced170507cc49b6d20f274996abbf1cf7a1f`。以下为本次真实数据，不来自 PDF 或旧 TC 表。当前 [TEST_CASES.md](TEST_CASES.md) 采用完整运行；筛选六项/单项只支持各自范围，不把三个运行加为 31 个课程用例。

| Current run | Actual methods/result | Parent PHPUnit assertions | Separate worker assertions |
|---|---|---:|---:|
| [Full: 20261016T192024Z-darwin-report-5cde49](../results/evidence/20261016T192024Z-darwin-report-5cde49/summary.json) | 24/24 Passed; 0 failed/errors/skipped | 5973 | 54000 |
| [Jiaqing six: 20261016T192025Z-darwin-report-09eaaf](../results/evidence/20261016T192025Z-darwin-report-09eaaf/summary.json) | 6/6 Passed: F13/F14/F15/F16/N01/N02 | 1383 | 54000 |
| [Single F13: 20261016T192026Z-darwin-report-359025](../results/evidence/20261016T192026Z-darwin-report-359025/summary.json) | 1/1 Passed | 600 | 0 |

完整运行 [N01 measurement](../results/evidence/20261016T192024Z-darwin-report-5cde49/measurements/N01.json) 的三批为 0.011278417 / 0.010914375 / 0.011824333 s，中位数 0.011278417 s，吞吐量 88664.92522842523 records/s；[N02 measurement](../results/evidence/20261016T192024Z-darwin-report-5cde49/measurements/N02.json) 的 allocated peak 为 **6291456 bytes / 6 MiB**，实际配置为 256M。

个人六项运行使用自己的 [N01 measurement](../results/evidence/20261016T192025Z-darwin-report-09eaaf/measurements/N01.json)：三批 0.011390125 / 0.010932375 / 0.011538667 s，中位数 **0.011390125 s**，吞吐量 **87795.34904138454 records/s**。对应 [N02 measurement](../results/evidence/20261016T192025Z-darwin-report-09eaaf/measurements/N02.json) 也是 **6291456 bytes / 6 MiB**，256M 配置。两次测量各自完成并校验输出，performance criteria not assessed；数字限于各自机器和工作负载，不表述为速度/内存 SLA 达标。

独立报告基础设施检查为 73 tests / 308 assertions，通过日志与其他工具验证记录保存在 [完整运行 verification 目录](../results/evidence/20261016T192024Z-darwin-report-5cde49/verification)。这些检查使用工具夹具，不增加 F/N 用例数或 Faker 断言数。

上述本机最终运行是在摘要工具修正后重测：先前 `tests/.DS_Store` 被纳入 test contract hash，导致本机/CI 契约摘要不同。工具现只排除 `.DS_Store` / `Thumbs.db`，SUT 源码严格摘要不变；先前实际 Passed 归档全部保留为 earlier attempts，不回写摘要或数值。最终本机和下列三个 CI OS 已记录同一个修正后的契约摘要。该修正影响证据口径，不改变 Faker 用例设计，见 [BUG_REPORT.md](BUG_REPORT.md#tool-002--desktop-metadata-entered-the-test-contract-fingerprint)。

最终 [GitHub Actions run 38954165253](https://github.com/Jqlyu/faker-testing-project/actions/runs/38954165253) 在其 commit `9178ced170507cc49b6d20f274996abbf1cf7a1f` 的三个托管 OS 已全部成功，原生下载 artifact 的 digest 校验完成，github-actions 比较与下载后重新比较均 Passed。三个平台**各自**为 24/24 Passed、5973 个父 PHPUnit 断言，另有 54000 个 worker 断言；三平台共 72 次用例执行，逻辑设计仍为 24 项。全部归档、comparison 和 provenance 位置见 [当前证据索引](../results/evidence/REPORT_ALIGNMENT_20261016.md)。

| Actual CI context / run | N01 median seconds | N01 records/second | N02 allocated peak / configuration |
|---|---:|---:|---|
| [github-Windows: 20261016T192055Z-windows-report-a44ee0](../results/evidence/20261016T192055Z-windows-report-a44ee0/summary.json) | 0.0210051 | 47607.48580106736 | 6291456 bytes / 6 MiB; 256M |
| [github-Darwin: 20261016T192043Z-darwin-report-7c5360](../results/evidence/20261016T192043Z-darwin-report-7c5360/summary.json) | 0.015677917 | 63783.98354832469 | 6291456 bytes / 6 MiB; 256M |
| [github-Linux: 20261016T192030Z-linux-report-93e7c3](../results/evidence/20261016T192030Z-linux-report-93e7c3/summary.json) | 0.026351397 | 37948.652209975815 | 6291456 bytes / 6 MiB; 256M |

CI macOS 是 26.6.2 / arm64，与 local 26.3 / arm64 分开；Linux 是 x86_64 ubuntu24 runner，非 PDF 的 ARM64 VM。Windows 的 PHP 原始系统文本为 build 26100 (Windows Server 2016)，GitHub ImageOS 为 win25-vs2026；按原字段保留，不把它称作 Windows 11。上述时间描述不同 runner 的测量，不能据此断言 OS 速度优劣或达到性能 SLA。

英文八列表头为 ID | Tester | Feature being tested | Preconditions | Inputs | Expected Output | Actual Output | Purpose and notes。Actual Output 只引用实际 JUnit/summary、observations、measurements 和日志；未运行为 Not Run，中断/损坏报告无法确认计数为 null。报告人员负责分组与实际执行上下文分别记录。

原有 `results/evidence/` 保持历史快照，其中 TC01–TC20 的 20/20、41322 assertions 及 TC21–TC24 状态只适用于当时执行，不可改名成 F/N 结果。PDF 的 2685 assertions、0.031s 和 8 MB 也不是本次的数字。

尚需据实补齐的跨平台证据：

- 原生 Windows 六项截图及完整运行目录；F14/F15 自己的功能样本和 JUnit 记录。
- 原生 Windows/Linux 全套运行和 local-device 三平台比较；当前没有新 local-Windows/local-Linux 报告，保留 Not Run。

## 7. Bugs, risks and limitations

确认缺陷需要可复现的实际行为与预期依据；暂未发现不等于证明无缺陷。PDF 的 F18 曾因 optional 整数权重 1 的错误预期而失败，修正预期后通过，不应报告为已修复 Faker Bug。本仓库历史 upstream 128M 内存中断属于环境/资源观察，不能写成 N02 的隔离峰值。详见 [BUG_REPORT.md](BUG_REPORT.md)。

抽样有限，重复 API 不等于独立的全部 API 覆盖；归档库和历史 PHP 不支持现代运行时/生产安全推论。未测源代码覆盖率，不用文件数、测试数、assertion 数推算覆盖率。不同机器时间和随机输出不能直接作性能/功能退化判据。

## 8. Supported conclusion

可陈述：本次按报告设计的新实现已在 local-Darwin 和三个 GitHub CI OS 分别完成 24/24，通过相应功能约束和测量完整性检查；同一 workflow/commit 的 github-actions 三平台比较 Passed。个人六项和 F13 单项另有独立 local-Darwin 通过证据。N01/N02 的实际数字、worker 口径和 performance criteria not assessed 同时保留。

不得沿用 PDF 前文“all 24 passed”认证原历史执行；其 N01 数值缺失、N02 Not Run、F14/F15 Windows CI 待确认仍与该结论冲突。修订时可引用本次新的 Windows CI 独立记录补充结果，同时保留历史/新执行的界限。原生 Windows/Linux 尚未有本次执行证据，local-device 三平台比较仍未完成；CI 通过不能替代原生 Windows 11 设备执行，没有 SLA 就不宣称性能达标。
