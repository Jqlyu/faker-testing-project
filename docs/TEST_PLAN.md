# Test Plan — report F01–F22 / N01–N02

对象为 [fzaninotto/Faker](https://github.com/fzaninotto/Faker) v1.9.2，commit `848d8125239d7dbf8ab25cb7f054f1a630e68c2e`。系统边界为开发者通过 Composer 安装并调用公开 Factory/Generator API，不修改 Faker 业务源码。课程主套件对应最新 Group666 PDF 第 19–26 页的 **24 个逻辑用例：F01–F22 和 N01–N02**。实现前的映射及模糊输入选择见 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md)；执行入口见 [RUN_GUIDE.md](RUN_GUIDE.md)。

## Scope and responsibility

| Report cases | Report responsibility | Features |
|---|---|---|
| F01–F06 | Zhiyan Chang | gender/name、safeEmail、日期边界、整数分区、fi_FI、UUID |
| F07–F12 | Haochang Shi | regexify、randomElements、IPv4/IPv6、信用卡/Luhn、paragraphs、EAN-13 |
| F13–F16 | Jiaqing Lyu | UTC 日期、UUID、safeEmail、IPv4，各有独立方法及观察 |
| F17–F22 | Jingyi Zhu | unique randomDigit、optional、valid、seed replay、bothify、日期范围 |
| N01–N02 | Jiaqing Lyu | 固定工作负载的吞吐量和新 PHP 子进程的 allocated peak memory |

责任分组不能代替执行环境记录。报告提及的 macOS、Ubuntu VM、Windows CI 和原生 Windows 11 分别保留为原报告背景；本次新实现只报告实际运行的 OS/context。原 TC01–TC20 作为独立 supplemental 套件保留，其中地址/电话/浮点数/异常/持续记录等额外覆盖不计入这 24 项。旧 TC21–TC24 只作为平台安装与比较背景。Faker 原有测试、报告工具自检也分别计数。

不覆盖全部 provider/地区、ORM/ODM、数据库、在线图片服务可用性、所有 PHP 版本、32 位/大端、密码学随机性、真实邮箱投递或地址/电话归属。格式和校验位合法不能证明现实数据有效。

## Strategy and techniques

使用真实 Composer 包和公共 API，无 mock。各用例按映射单独 seed，使用 UTC、C 进程 locale 及所需 Faker locale；F01–F12 使用 740001–740012，F13–F16 使用 4160，F20 使用 1234，其余新补充 seed 以映射和观察记录为准。F05 单独 fi_FI，其余默认 en_US。不能继续写“所有用例统一 seed 4160”。

断言检查类型、格式、区间、结构、版本/variant、约束和异常。独立校验器验证 IP、Luhn、EAN-13，EAN-13 另有正反控制。边界值包括日期相等/倒序端点、整数正/负/跨零/单值/极值、randomElements 的 0/3/5/6 和两种重复模式、paragraphs 的 0/1/3/5、optional 的整数 0/100 与小数 0.5。有限样本不证明均匀性或覆盖所有边界。

F13/F14/F16 可复用旧检查的约束，但必须独立执行、使用自己的 F 编号和结果。F15 必须调用 `safeEmail()`，不能用旧 TC02 的普通 email 检查替代。F17 为 0–9 的十个 unique digit；旧 1–100 唯一性/耗尽测试不是同一输入。F20 重播十个 randomDigit，不能替换成旧复合记录 replay。F22 保留字面 date-only 端点，不把 12 月 31 日自动扩展到 23:59:59。

## Non-functional measurement protocol

N01、N02 均为 seed 4160、100 行预热、三批各 1000 行、每行 name/email/uuid；预热后重置 seed 4160。每批保留到校验结束后释放，记录 retention 和测量开销。N01 仅对生成及 batch retention 计时，校验在计时外，保存三次 seconds、中位数及 `1000 / median_seconds` 的 records/second。N02 在新 PHP worker、`memory_limit=256M` 下执行同工作负载，记录 `memory_get_peak_usage(true)` 的 bytes/MiB；worker 加载 Composer/Faker/PHPUnit Assert 及依赖但不启动 PHPUnit runner。峰值在完成校验/哈希/释放后、序列化报告之前采集，包含此前进程的 PHP 分配，不是 Faker 独占内存、整套 PHPUnit 峰值或 OS RSS。

N01/N02 的通过条件是工作负载完成、输出约束成立、测量结构和值有效。报告没有预设速度/内存 SLA，所以测量完成与 **performance criteria not assessed** 同时成立；256M 是配置上限，不能作为后来指定的验收目标。原 TC20 full-suite peak 和 PDF 中的 PHPUnit 0.031s / 8 MB 不能作为 N02 数值。

## Tools and reproducibility

PHP 7.4.33、Composer 2.10.3、PHPUnit 9.6.38、固定 lock 和 Faker commit 是本次基线。默认 `phpunit.xml` 运行 `tests/Report/` 的报告方法；`phpunit-supplemental.xml` 独立运行 `tests/Supplemental/FakerSystemTest.php`。原有测试使用独立 PHPUnit 5.7.27 和 bootstrap；其历史结果与 PDF 在 PHPUnit 9.6 下仅运行两个文件的研究分开。详见 [EXISTING_TESTS.md](EXISTING_TESTS.md)。

每次运行保留原生 JUnit、summary、日志、实际环境、观察、测量和八列英文 `REPORT_TABLE.md`（`TEST_CASES.md` 为同表别名）。完整表头为：ID、Tester、Feature being tested、Preconditions、Inputs、Expected Output、Actual Output、Purpose and notes。设计中的期望与真实 Actual Output 分开，不能从 PDF 的 PASS 填充本次结果。

## Entry, completion and evidence

进入条件为兼容 PHP/扩展、工具 SHA256 验证、lock 安装及平台要求检查成功。完整报告运行需 24 个 F/N 身份全部执行、报告齐全，且按其各自条件判定；筛选六项/单项不能声明完成 24 项。新报告套件的实际运行数据在生成前保持待执行，不继承旧 TC 或 PDF 的通过数。

跨平台完成需三份来自实际 Windows/macOS/Linux 的完整同基线报告，并通过比较。比较默认 `--context=github-actions`，本机设备使用 `--context=local-device`；六个 local-/github- 平台上下文分别显示，同一比较只要求一个 context 的三个 OS。CI 还需同一 workflow run/commit，不能与本机结果拼接。缺失的用例/平台为 Not Run，运行中断或损坏报告无法确认的计数为 null；失败/错误及非零退出码全部保留。对每个结论引用明确 run ID/context，父 PHPUnit 断言与子 worker 断言分别统计，证据归档不覆盖旧结果。

## Risks

归档库、历史 PHP、旧 runner 的结论限于固定基线。抽样不能替代完整状态空间、统计随机性测试或安全审计。runner 镜像和安装源可变化，lock 不能固定底层 OS。性能只描述实际机器/工作负载；原成员代码缺失以及 PDF 内部状态矛盾见 [REPORT_CORRECTIONS.md](REPORT_CORRECTIONS.md)，不能用本次重跑反向认证历史执行。
