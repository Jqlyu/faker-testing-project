# Bug Report and Observations

截至本次实际执行，**没有确认新的 Faker 功能缺陷**。新增系统测试 20/20 passed；原有测试 2076 passed、2 skipped、0 failed。仅适用于已执行的 macOS/PHP 7.4.33 基线，不代表其他平台或未覆盖 API 没有 Bug。

## ENV-001 — Original suite exceeds default PHP memory limit

| Field | Evidence |
|---|---|
| Classification | 环境/资源配置限制，不是已确认 Faker 功能缺陷 |
| Reproduction | PHP 7.4.33；memory_limit=128M；原有 PHPUnit 5.7.27；执行全部 test/Faker |
| Expected | 在足够资源下执行原有完整套件并生成有效报告 |
| Actual | Text.php:84 分配词链表时内存耗尽；JUnit 未完成，无法确定完整通过/失败数 |
| Severity | 阻断该配置的测试执行；不作产品缺陷严重度评级 |
| Environment | macOS 26.3 / arm64，原始 PHP 默认 128M |
| Follow-up | 只将测试子进程限制调整为 512M，完整套件执行完成，约 168.53MB 峰值 |
| Logs | results/evidence 的中断运行 phpunit.log、runner-error.log、environment.json |

这个观察不能推出 Faker 对 128M 所有调用都失败；它发生在完整上游测试的特定累计内存状态。本机新增系统套件峰值 8MB，未触及该限制。

## COVERAGE-001 — Upstream skips remain unverified

图片下载测试由上游主动跳过；et_EE bankAccountNumber 未实现而跳过。详细名称/原因见 EXISTING_TESTS.md 及原生 upstream-events.json。没有本次新发现的产品缺陷证据，也不算通过。

## TOOL-001 — Legacy JUnit omits skipped cases

旧 PHPUnit 5 原生 JUnit 的总记录只有 2076，终端显示 2078 和 skipped=2。测试项目报告读取现已使用 PHPUnit 原生 JSON event 补足 2 个跳过项，并有独立自检。它属于报告工具行为，不属于 Faker 缺陷。早期诊断运行的 summary 已标注计数来源和可用性，不用于课程最终通过结论。

## New defect template

未来若真实发现 Bug，按如下字段填写并附原始证据，不人为制造缺陷：Bug ID、标题、复现步骤、版本/commit/seed/OS、预期结果及依据、实际输出/异常、严重程度及理由、最小复现命令、JUnit 和日志位置、是否在第二次运行复现。
