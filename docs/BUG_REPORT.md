# Bug Report and Observations

本仓库历史 macOS supplemental/upstream 执行没有确认新的 Faker 功能缺陷；其计数属于原运行，不能作为新 F01–F22/N01–N02 的结果。新报告套件实际结论待真实执行及对应证据。PDF 中的框架错误、F18 预期修正和证据缺口分别处理，不能统一标为 Faker Bug。

## ENV-001 — Historical upstream suite exceeds 128M configuration

| Field | Evidence |
|---|---|
| Classification | 环境/资源配置限制，非已确认 Faker 功能缺陷 |
| Reproduction | 历史 macOS 26.3 / arm64 / PHP 7.4.33；128M；完整 upstream PHPUnit 5.7.27 |
| Expected | 足够资源下执行完整原有套件并生成有效报告 |
| Actual | Text.php:84 构建词链表时内存耗尽；JUnit 未完成，不能确定全套通过/失败数 |
| Follow-up | 仅将测试进程设为 512M；完整运行 2076 passed、2 skipped；原生峰值约 168.53 MB |
| Evidence | [历史证据索引](../results/evidence/README.md)，中断/成功运行日志和环境 |

此观察限于完整套件累计状态，不能推出所有 Faker 调用在 128M 下失败。它也不能替代 N02 新 PHP worker 的 allocated peak bytes/MiB；supplemental 全套的 8 MB 或 PDF 的 8 MB 同样不能代入 N02。

## COMPAT-001 — PDF selected upstream tests with PHPUnit 9

PDF 在 Linux/PHPUnit 9.6.38 下运行两个旧测试文件，报告 BaseTest 16 errors、21 warnings，DefaultGeneratorTest 通过。移除的断言方法、参数要求变化和旧异常注解是框架兼容性观察，不是本仓库这次复现出的产品缺陷。其历史执行与本仓库 PHPUnit 5.7.27 完整运行分开，见 [EXISTING_TESTS.md](EXISTING_TESTS.md)。

## DESIGN-001 — PDF F18 optional weight expectation corrected

PDF 第 24 页说明初始整数 weight 1 的预期错误，改用整数 0/100 的 DEFAULT/PROVIDER 和小数 0.5×200 后通过。这是测试预期修正；未修改 Faker 源码，不能写成“发现并修复 Faker optional Bug”。新实现明确验证这三组条件并保存自己的输出。

## COVERAGE-001 — Upstream skips remain unverified

历史 upstream 的图片下载和 et_EE bankAccountNumber 由上游主动跳过，原因见 [EXISTING_TESTS.md](EXISTING_TESTS.md) 和原生 upstream-events.json。Skipped 不算通过，没有当前服务/地区功能验证结论。

## TOOL-001 — Legacy JUnit omits skipped records

旧 PHPUnit 5 的 JUnit 只有 2076 记录，终端包含 2078 和 2 skipped；当前报告读取使用原生 JSON 事件补足两个跳过项并有独立自检。此为报告工具行为，早期诊断 summary 不用于最终通过结论。基础设施合成 XML/事件只验证报告处理。

## EVIDENCE-001 — PDF result claims need correction

PDF 前文“all 24 passed”与 N01 数值待核对、N02 Not Run、F14/F15 Windows CI 证据未确认冲突。原报告属于历史来源，新 macOS 实现的通过不能认证旧 Windows 执行。需要具体日志/数值及上下文后才能更新相应结论；详见 [REPORT_CORRECTIONS.md](REPORT_CORRECTIONS.md)。

## New defect template

真实发现异常时保留 Bug ID、标题、复现步骤、F/N ID、版本/commit/seed、OS/context、预期及其依据、实际输出/异常、严重度理由、最小复现命令、完整 run ID/JUnit/观察/日志、重跑是否复现。测试断言或环境错误应据实分类，不人为制造产品缺陷。运行和截图步骤见 [RUN_GUIDE.md](RUN_GUIDE.md)。
