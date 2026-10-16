# Report Corrections — latest Group666 PDF

本文件记录最新 PDF 内部的待修正项，不编辑 PDF，不把历史 PASS 当作本次执行。来源为报告第 3–6、7–11、19–26、27–28 页；以详细 F01–F22/N01–N02 表的设计为实现目标。原成员的 F 编号源代码未在当前仓库提供，所以本仓库补充的是**按报告设计的新实现**，不是重新运行原成员代码。实现前的对应和输入选择见 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md)。

## Claims requiring correction

| Location / claim | Evidence conflict | Supported wording / required action |
|---|---|---|
| pp3–4 “all 24 newly designed test cases passed” / “completed and passed” | pp25–26 尚无 N01 时间/吞吐量数值；N02 明确 Not Run；p28 也限定为 22 reported functional passes | 说明原报告列出 22 项功能通过但 F14/F15 需确认、非功能证据未完整；本次新增执行另列日期/run ID/context，不把新证据追溯写成原报告已具备 |
| p25 N01 Windows CI execution completed | 同表说明 numerical timing/throughput 要从 performance.json 核对；p27/p28 明确缺少测量数字 | 只有“原报告称运行完成”；不能填入速度、使用 PHPUnit 总耗时或写性能达标。新 N01 引用自己 measurements/N01.json 的三批时间、中位数、records/second |
| p26 N02 peak memory | Actual Output 为 Not Run as an isolated benchmark，已有 full-suite peak 无法独立归因 | 保留历史 N02 Not Run；新执行必须用 fresh PHP worker，记录 allocated peak bytes/MiB、memory_limit、workload 和开销。旧 TC20/全套峰值不得改名为 N02 |
| p23 F14 UUID Windows CI | 原表承认 UUID-specific output/assertion details need confirmation against run log；p28 再次要求核对 | 需对应 Windows CI 原始 UUID 样本和 JUnit 记录；相关旧 TC12 不能单靠名字认证历史 F14。新 F14 单独执行，只证明其实际 OS/context |
| p23 F15 safeEmail Windows CI | 原表承认输出/断言细节待核对；旧 TC02 是普通 email，不能等价替代 | 需 safeEmail 的真实样本、reserved-domain 校验和自己的记录。新 F15 明确 safeEmail，不继承历史 PASS |
| pp6–11 common tools / selected upstream study | 旧 Faker 的 PHPUnit requirement 为 ^4.8.35 或 ^5.7；PDF 实际使用 9.6.38 时出现 16 errors/21 warnings | 明确这是旧测试与新 runner 的兼容研究，不代表完整原套件结果或已确认 Faker 缺陷；与本仓库历史 PHPunit 5.7.27 完整运行分开 |
| p24 F18 corrected initial expectation | 初始整数 weight 1 的测试预期有误；修正后通过，无 Faker 源码修改 | 写测试设计修正，不写“修复 Faker 产品缺陷” |
| pp5,19–20 PHPUnit 0.031s / 8.00 MB / 2685 assertions | 这些属于原报告六项 macOS PHPUnit 执行，非 N01/N02 workload isolation | 保留为历史表述，不能复制为这次断言数、吞吐量或 N02 峰值 |

即使本次新实现的 24 项在 macOS 全部通过，也不能消除历史 N02 Not Run，不能确认历史 Windows CI F14/F15，也不能把原生 Windows 11 写作已复现。应添加新的 run ID、日期、代码版本和实际环境来报告补充执行。

## Version and execution-context boundaries

PDF 的共同基线为 Faker 1.9.2、PHP 7.4.33、Composer 2.10.3，自动化使用 PHPUnit 9.6.38；F17–F22 原文为 Windows 11 standalone PHP script。其 Ubuntu 24.04.5 LTS ARM64 VM、macOS、Windows CI 和 Windows 11 不是同一个执行环境。原文中 “Windows-related” 责任分配不能替代具体设备或 CI 证据。

本仓库新实现固定相同 Faker commit 和工具基线，以 PHPUnit 9.6.38 统一报告方法，upstream 完整运行另用 PHPUnit 5.7.27。这个组织方式是新的实现决策，不追溯声称原成员也用了它。每次运行的 environment.json、参数和 GitHub metadata 优先于复制来的环境表。CI Windows、native Windows PowerShell、WSL/Linux、Linux VM 和 native macOS 分别标注；跨平台比较需三份完整同基线新报告。

## Inputs that must remain explicit

PDF 并未给出所有可执行输入；以下选择在实现前登记，不能声称恢复了原断言数或随机样本：

- 默认 Faker locale=en_US、timezone=UTC；F17–F19/F21–F22 新增 seed 4160，F20 按报告 seed 1234。
- F03 选 2020-02-28 00:00:00 至 2020-03-01 23:59:59 UTC，相等端点为 2020-02-29 12:00:00 UTC。
- F04 八个分区具体为 [1,100]、[-100,-1]、[-10,10]、[0,0]、[42,42]、[100,1]、[PHP_INT_MIN,PHP_INT_MIN]、[PHP_INT_MAX,PHP_INT_MAX]，每组 20 次；不重建 PDF 的 780 historical assertions。
- F08 的五源数组为 alpha/beta/gamma/delta/epsilon；count 0/3/5/6 和两种重复模式各执行。允许重复不代表每个短样本都必须碰巧重复；6 次来自 5 元素时才必有重复。
- F11 每个 count/mode 一次；成对 array/text 前同 seed 740011，以验证精确分隔符。
- F12 新明确控制为 4006381333931 有效、4006381333932 无效，不声称是原成员用的 fixture。
- F22 按字面 date-only 2020-01-01/2020-12-31；UTC 结束边界是 12 月 31 日 00:00:00。F13 的 23:59:59 是不同的明确输入。
- N01/N02 seed 4160 预热后重置 4160，每批 1000 行校验后释放；记录 retention 和 worker/validation 开销，没有速度/内存 SLA。256M 为配置，allocated peak 不是 OS RSS。

完整可执行选择以 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md) 和对应观察/测量 metadata 核对，不用本文件作为另一套结果数据。

## New execution status and evidence

本次新实现已有真实 local-Darwin 补充执行：[20261016T192024Z-darwin-report-5cde49](../results/evidence/20261016T192024Z-darwin-report-5cde49/summary.json) 为 24/24 Passed，5973 个父 PHPUnit 断言、另有 54000 个子 worker 断言。N01 中位数 0.011278417 s、88664.92522842523 records/s；N02 独立 worker 为 6291456 bytes / 6 MiB、256M 配置。[Jiaqing 六项独立运行](../results/evidence/20261016T192025Z-darwin-report-09eaaf/summary.json) 也为 6/6 Passed，1383 个父断言、另有 54000 个子 worker 断言；其 N01 有自己的时间，不能与完整运行混用。这些是 2026-10-16 的新 macOS 结果，历史 N02/F14/F15 Windows 证据缺口仍须按原上下文处理，原生 Windows/Linux 的本次报告套件仍为 Not Run。

报告结果只引用明确的新 run ID；筛选运行只支持自己的范围。八列英文 REPORT_TABLE.md 的 Actual Output 从同目录 JUnit/summary、observations、measurements 和日志生成，未运行为 Not Run，无法确认的计数为 null。

新的 [CI run 38954165253](https://github.com/Jqlyu/faker-testing-project/actions/runs/38954165253) 已在 Windows/macOS/Linux 托管 runner 各完成 24/24 Passed，github-actions 比较和下载后重比均 Passed。三个 OS 各有 5973 个父 PHPUnit 断言和单独的 54000 个 worker 断言，N02 均为 6291456 bytes / 6 MiB、256M 配置。新 Windows CI 的 F14/F15 现在各有自己的 UUID/safeEmail 观察和 JUnit，可在修订报告中引用这一新 run 补充真实证据；不能据此追溯声称原历史 run 已核实。CI Windows 的 PHP 原始系统文本包含旧 Server 2016 标签，实际记录另含 build 26100 和 ImageOS win25-vs2026；保留全部字段，不称为原生 Windows 11。新证据及 provenance 见 [REPORT_ALIGNMENT_20261016.md](../results/evidence/REPORT_ALIGNMENT_20261016.md)。

N01/N02 的 Passed 仅表示测量工作负载完成且输出/数值有效，需同时保留 **performance criteria not assessed**。没有预设 acceptance threshold，就不能从任何单次测量宣布速度或内存“达标”。

旧 TC01–TC20 和旧 TC21–TC24 归档仍是历史 supplemental/平台证据，原样保留。`results/evidence/README.md`、各次 TEST_CASES.md 和依赖/上游 README 不移除；它们出现的根 README 链接属于当时快照，当前操作说明位于 [RUN_GUIDE.md](RUN_GUIDE.md)。
