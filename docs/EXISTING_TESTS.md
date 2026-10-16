# Existing Faker Tests

研究基线为固定 Faker v1.9.2 的 `test/Faker/`、`src/Faker/`、`composer.json`、`phpunit.xml.dist` 和上游自己的 `readme.md`。上游文件保持原样；项目根 README 的移除不涉及这些文件。当前运行入口见 [RUN_GUIDE.md](RUN_GUIDE.md)。

## Structure and framework

- 固定源码有 130 个 `*Test.php` 文件；PDF 静态统计 415 个显式 test 方法，不能等同于数据提供器展开后的实际执行数量。
- 原有开发依赖要求 PHPUnit `^4.8.35 || ^5.7`、ext-intl 和 PHPCS `^2.9.2`；`phpunit.xml.dist` 使用其 vendor/autoload.php 和旧 coverage whitelist。
- 核心包括 Factory/Generator、UniqueGenerator/ValidGenerator/DefaultGenerator、Calculator、通用和地区 provider、外部 ORM 适配器。
- 覆盖 provider/算法/地区的源码组织和测试文件数不等于源代码覆盖率，也不能证明所有公开 API/OS 组合经过验证。

上游的无返回类型 setUp、assertInternalType、setExpectedException 和 @expectedException 等与新 PHPUnit 不完全兼容。当前仓库完整 upstream 执行使用独立 PHPUnit 5.7.27；新报告和 supplemental 使用 PHPUnit 9.6.38。没有为兼容新 runner 改写上游断言。

## PDF: selected Linux study with PHPUnit 9.6.38

PDF 第 7–11 页记录 Ubuntu 24.04.5 LTS ARM64 VM、PHP 7.4.33 下的两个文件研究：

| Original file | Tests | Assertions | Errors | Warnings | Reported outcome |
|---|---:|---:|---:|---:|---|
| DefaultGeneratorTest.php | 3 | 4 | 0 | 0 | Pass, exit 0 |
| BaseTest.php | 84 | 123 | 16 | 21 | Fail, exit 2 |
| Total | 87 | 127 | 16 | 21 | Not fully passed |

这是 PDF 的历史结果，原始 Linux 执行文件/日志未随当前代码提供，不是本次新执行。报告把错误归为移除的 assertInternalType、assertContains 参数兼容性和旧 @expectedException；assertRegExp 产生弃用警告。这些主要描述旧测试与新 runner 的兼容性，不能单凭 error/warning 认定 Faker 生成逻辑有 Bug。仅两文件不能推导完整上游通过率。

## Repository: historical full macOS study with PHPUnit 5.7.27

本仓库已有的独立完整运行来自 macOS 26.3 / arm64 / PHP 7.4.33 / intl，使用同 commit 的安装包和单独 bootstrap 加载原有 test namespace：

```bash
php scripts/fetch-upstream.php
php scripts/download-tools.php
php scripts/run-tests.php --suite=upstream
```

不可改写的 [evidence index](../results/evidence/README.md) 记录 **2078 个实例，2076 passed，0 failed，0 errors，2 skipped**。这不是 2078 全部通过，也不是上述 Linux/PHPUnit 9.6 两文件研究的复现。不要把两个环境的计数合并。

| Skipped test | Upstream reason | Remaining limitation |
|---|---|---|
| ImageTest::testDownloadWithDefaults | Skipped due to unstable service prior 1.9.0 release | 未验证在线图片服务当前可用性 |
| PaymentTest::testBankAccountNumber, et_EE data set | bankAccountNumber not implemented for et_EE | 未验证该地区未实现的方法 |

历史首次在默认 128M 下于 Text provider 词链表分配时内存耗尽，JUnit 未完成。只把测试子进程限制改为 512M 后完整运行，原生 PHPUnit 峰值约 168.53 MB；前后日志保留。这是该完整测试进程的资源观察，不是 N02 工作负载隔离测量，也不是所有 Faker 调用都需该内存。

PHPUnit 5 JUnit 未包含两个 skipped，runner 使用其原生 `--log-json` 事件补全名称和原因；旧 JSON listener 弃用提示保留在日志。summary 的 2078 为 2076 JUnit 记录加两个原生 skipped，不能从部分 JUnit 猜测其他结果。

## Relation to the current report cases

新 `tests/Report/` 按 F01–F22/N01–N02 设计消费者 API 检查，原 TC01–TC20 移到 supplemental 套件独立保存。其行为映射见 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md)。上游、supplemental、报告 24 项和报告工具自检分别计数；合成基础设施夹具不证明 Faker 或 OS 执行。

例如上游 TextTest::testTextMaxLength 的断言直接比较 `$length` 与字符串而非显式 strlen，是断言质量限制，不能直接写成已确认 realText 缺陷。当前新实现使用独立约束，不假定上游通过就代表所有功能正确。任何新 upstream 运行均应引用新的 run ID 和实际 runner，保留旧结果。
