# Real execution evidence

这些记录来自 2026-10-14 上传前的真实本机 macOS 执行。记录当时 Windows、Linux 和 GitHub Actions 尚未执行；TC24 Not Run。上传后的远程状态见 [Actions](https://github.com/Jqlyu/faker-testing-project/actions)，不改变这些历史记录。

| Run | Purpose | Total | Passed | Failed/errors | Skipped | PHPUnit time |
|---|---|---|---|---|---|---|
| [20261014T182958Z-darwin-system-ffd173](20261014T182958Z-darwin-system-ffd173/summary.json) | 最终代码完整运行 / 重复性验证 | 20 | 20 | 0 | 0 | 00:00.122 |
| [20261014T182959Z-darwin-system-ae2849](20261014T182959Z-darwin-system-ae2849/summary.json) | 最终代码完整运行 / 重复性验证 | 20 | 20 | 0 | 0 | 00:00.100 |
| [20261014T181847Z-darwin-upstream-0202a6](20261014T181847Z-darwin-upstream-0202a6/summary.json) | 原有完整套件，512M | 2078 | 2076 | 0 | 2 | 621 ms |
| [20261014T181459Z-darwin-upstream-79ccc2](20261014T181459Z-darwin-upstream-79ccc2/summary.json) | 默认 128M 中断，报告未完成 | Unavailable | Unavailable | Unavailable | Unavailable | Unavailable |

较早的两份通过记录也保留在本目录：20261014T182719Z-darwin-system-dc7be5、20261014T182720Z-darwin-system-bfba19。它们属于较早的报告脚本版本，最终报告优先引用上表最后两份系统记录。

原有内存中断记录的 summary 补充了说明：JUnit 截断导致无法取得完整计数，原始终端进度、日志和时间未改变。该次旧 runner 未保留 PHPUnit 子进程退出码，记录为 null；runner 返回码是 2。

完整成功的原有套件没有 Faker 失败，两个跳过项保留在 upstream-events.json。旧 JSON logger 的弃用提示保留在日志中。

## Reproducibility and performance

最终代码测试摘要：`02ffb87fb6109aefa8e9acf83ecc1912044a7fbf6c2c5540f5d2087803b35652`。

两次 seed=4160 的 benchmark 输出摘要均为 `a3964d4dc351f55ea6dfa99ebcc600cdb197a6e1b424bd4e864398b043831d0b`。这只验证同一控制环境下的重复性，没有比较未经执行的 OS。

| Run | 3 samples, median seconds per 1000 rows | Median rows/s | Peak process memory |
|---|---|---|---|
| 20261014T182958Z-darwin-system-ffd173 | 0.011208542 | 89217.67 | 8388608 bytes |
| 20261014T182959Z-darwin-system-ae2849 | 0.011152375 | 89667.00 | 8388608 bytes |

测量条件：每行 name/email/uuid，100 行预热，3 组各 1000 行，共 9000 次计时调用；断言不在计时区间内。性能没有设定 SLA，也不能用上表推断其他机器性能。每份 environment.json 含真实 OS/PHP/依赖及运行参数。

本机环境：macOS 26.3 / Darwin 25.3.0 / arm64 / PHP 7.4.33 / Composer 2.10.3 / PHPUnit 9.6.38；原有套件 PHPUnit 5.7.27。项目 Git 刚初始化且未提交，所以 project_commit 为 null；Faker 被测 commit 已固定，测试与依赖摘要已保存。

UTC 时间在 run ID/JSON 中；对应 Helsinki 当地时间：
- 20261014T182958Z-darwin-system-ffd173: 2026-10-14T21:29:58+03:00
- 20261014T182959Z-darwin-system-ae2849: 2026-10-14T21:29:59+03:00

## Installation, platform comparison and tool verification

- [安装日志与平台检查](setup-20261014T181459Z-c46837/installation.json)：从 lock 安装并检查实际扩展成功。
- [最终仅有 Mac 的平台比较](final-platform-comparison/comparison.md)：TC22 Passed；TC21、TC23、TC24 Not Run；比较返回 1。
- [最终 24 场景表](final-platform-comparison/TEST_CASES.md)：状态来自真实报告。
- [工具验证](validation/qa.json)：12 个项目 PHP 文件语法、Composer schema、actionlint 1.7.12、报告工具 6 项检查均成功。Composer 精确版本的通用建议保留。
- [报告工具自检 JUnit](validation/infrastructure-junit.xml)：6 个自检 / 20 assertions，与 Faker 课程用例分开。

不计入课程通过数的一个诊断：用不存在的过滤条件运行 PHPUnit 时，原生输出为 No tests executed，runner 返回 2、counts=null。其用途是确认零用例不会错误显示成功。

同学可以将自己三系统的完整报告复制到独立导入目录后比较。这里保存多次 Mac 运行；直接对整个 evidence/ 做三平台比较会因重复 Mac 报告而拒绝，必须明确选择每个 OS 的一份报告。
