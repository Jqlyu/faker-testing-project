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

文档迁移时，新报告实现的实际总数、断言数、运行时间和 N01/N02 数值待真实执行；不能从 PDF 或历史 TC 表填写。最终以一份明确的完整 run ID 或三平台比较报告更新 [TEST_CASES.md](TEST_CASES.md)。筛选六项/单项运行只支持自己的范围，不应累计成未经完整执行的 24/24。

英文八列表头为 ID | Tester | Feature being tested | Preconditions | Inputs | Expected Output | Actual Output | Purpose and notes。Actual Output 只引用实际 JUnit/summary、observations、measurements 和日志；未运行为 Not Run，中断/损坏报告无法确认计数为 null。报告人员负责分组与实际执行上下文分别记录。

原有 `results/evidence/` 保持历史快照，其中 TC01–TC20 的 20/20、41322 assertions 及 TC21–TC24 状态只适用于当时执行，不可改名成 F/N 结果。PDF 的 2685 assertions、0.031s 和 8 MB 也不是本次的数字。

提交前需要据实补齐：

- 本次完整报告 run ID、日期、OS/context、24 项身份/状态、JUnit 断言与失败/错误/跳过数。
- N01 三次时间、中位数及 records/second；N02 独立 worker 的 peak bytes/MiB、memory limit 和测量范围。
- 原生 Windows 六项截图及完整运行目录；F14/F15 自己的功能样本和 JUnit 记录。
- 真实 Windows/Linux 全套运行、三平台比较和对应 GitHub workflow URL/artifacts；缺失项保留 Not Run。

## 7. Bugs, risks and limitations

确认缺陷需要可复现的实际行为与预期依据；暂未发现不等于证明无缺陷。PDF 的 F18 曾因 optional 整数权重 1 的错误预期而失败，修正预期后通过，不应报告为已修复 Faker Bug。本仓库历史 upstream 128M 内存中断属于环境/资源观察，不能写成 N02 的隔离峰值。详见 [BUG_REPORT.md](BUG_REPORT.md)。

抽样有限，重复 API 不等于独立的全部 API 覆盖；归档库和历史 PHP 不支持现代运行时/生产安全推论。未测源代码覆盖率，不用文件数、测试数、assertion 数推算覆盖率。不同机器时间和随机输出不能直接作性能/功能退化判据。

## 8. Supported conclusion

只陈述对应真实运行完成了哪些 F/N 用例、其约束和测量结果，并限定版本、工作负载和 OS/context。不得沿用 PDF 前文“all 24 passed”的笼统结论；其 N01 数值、N02 Not Run、F14/F15 Windows CI 待确认与该结论冲突。新 macOS 重跑即使全部通过，也不能认证历史 Windows CI 或原生 Windows 11。未有三份完整兼容报告前不声明三平台全套兼容；没有 SLA 就不宣称性能达标。
