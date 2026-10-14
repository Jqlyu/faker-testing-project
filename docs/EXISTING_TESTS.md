# Existing Faker Tests

研究范围：固定 v1.9.2 的 `test/Faker/`、`src/Faker/`、`composer.json`、`phpunit.xml.dist`、`readme.md`。GitHub 页面当前 master 与 release 的依赖约束存在差异，本文以固定 release 为准。

## Structure and framework

- `test/Faker/` 有 130 个 `*Test.php` 文件（加上数据提供器后，实际执行 2078 个测试实例）；`test/` 总计 132 个文件，其他包含辅助代码。
- 原有开发依赖要求 PHPUnit `^4.8.35 || ^5.7`、ext-intl 和 PHPCS `^2.9.2`。
- `phpunit.xml.dist` 使用 vendor/autoload.php，测试目录为 test/Faker，并配置旧版 coverage whitelist。
- 源码核心：Factory 选择及回退本地化 provider；Generator 管理 provider 和 formatter；UniqueGenerator/ValidGenerator/DefaultGenerator 为修饰器；Calculator 有校验算法；Provider 为基础和本地化生成器；ORM 目录为外部数据层适配器。
- 覆盖 Generator/Factory、Base 数字与字符串、日期、Internet、地址、人名、电话、支付、条码、UUID、各地区 provider、唯一值/有效值修饰器和校验算法。覆盖不能推导为所有公开 API、所有 OS、所有依赖的系统兼容性验证。

源码不依赖 PHPUnit；新增项目只安装 Faker 的运行包和独立 PHPUnit 9.6.38。上游的 `setUp()` 无返回类型、`assertInternalType`、`setExpectedException` 和 `@expectedException` 等旧写法不能直接作为 PHPUnit 9 测试运行，因此原有套件固定使用独立 PHPUnit 5.7.27，不对原有测试作语法改写。

## Execution and results

本机 macOS 26.3 / arm64 / PHP 7.4.33 / intl 已加载。我们用单独 bootstrap 加载 Composer 正式包及原有 test namespace；没有编辑 Faker 源文件或原有测试。执行：

```bash
php scripts/fetch-upstream.php
php scripts/download-tools.php
php scripts/run-tests.php --suite=upstream
```

真实最终运行：**2078 个实例；2076 passed；0 failed；0 errors；2 skipped**。详细运行编号和原始日志见 [evidence index](../results/evidence/README.md)。这是无失败但有明确跳过的执行，不能称为 2078 个全部通过。

跳过项由原仓库主动设置：

| Test | Actual reason | Interpretation |
|---|---|---|
| ImageTest::testDownloadWithDefaults | Skipped due to unstable service prior 1.9.0 release | 没有验证图片下载服务的当前可用性 |
| PaymentTest::testBankAccountNumber, et_EE data set | bankAccountNumber not implemented for et_EE | 不把未实现地区的方法当作已验证通过 |

首次用默认 128M 内存运行，在 Text provider 构建词链表时内存耗尽，JUnit 未完成。重跑时只调整 PHP 测试进程 `memory_limit=512M`，未删除测试或改变任何断言。最终峰值约 168.53 MB（PHPUnit 原生输出）；环境限制及失败日志保留在证据目录，不作为已确认 Faker 功能 Bug。

PHPUnit 5 的 JUnit 省略 skipped，用其原生 `--log-json` 保存名字与原因；该旧 JSON listener 本身会产生弃用提示，原样留在 phpunit.log。summary 的 2078 总数来自 JUnit 的 2076 记录加原生事件的 2 个 skipped，未重新开发测试逻辑。

## Differences from written tests

上游主要按 provider、算法及地区组织，许多直接调用 provider；我们新增 20 个系统方法从 Factory 初始化后的消费者 API 验证端到端约束，另外由自动化环境与报告比较覆盖 TC21–TC24。重点新增锁定运行环境、安装检查、持续混合数据生成、可复现 seed、性能基线及三平台证据管理。

报告基础设施有另一个配置、6 个自检；这些使用明确标注的临时合成 XML/事件，验证错误处理和跳过计数，不作为 Faker 或 OS 的执行证据。

## Remaining limitations in existing tests

例如 TextTest::testTextMaxLength 的断言直接比较 `$length` 与生成的字符串，未显式使用 strlen；这反映其测试断言质量的局限，不等同于证实 realText 业务缺陷。本项目的约束断言独立编写，不假定上游通过就意味着所有功能正确。
