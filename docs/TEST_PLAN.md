# Test Plan

## Software and objective

对象为 [fzaninotto/Faker](https://github.com/fzaninotto/Faker) 正式版 v1.9.2，commit `848d8125239d7dbf8ab25cb7f054f1a630e68c2e`。Faker 是面向开发者的数据生成库，系统边界为用户通过 Composer 安装并调用公开的 Factory/Generator API。目标是验证安装、公共 API、随机输出约束、异常、稳定性、基本性能和三平台兼容性，为 LUT CT60A4160 小组报告提供真实证据。

## Scope and exclusions

覆盖姓名、电子邮件、地址、电话、整数/浮点数、日期、IPv4、UUID、en_US/fi_FI、seed、unique 和未知 formatter；覆盖 1000 条持续记录及性能测量；覆盖锁定依赖的 Windows/macOS/Linux 安装与一致性比较。

不覆盖所有本地化 provider、ORM/ODM 适配器、数据库集成、在线图片服务可用性、所有 PHP 版本、32 位/大端机器、密码学随机性、真实邮箱投递、真实地址/电话号码归属及安全审计。不能从格式合法推出现实数据真实有效。

## Strategy and techniques

主策略是消费者视角的系统测试：使用真实 Composer 包、真实公共 API，不 mock、不替换业务逻辑。每个方法前重新 seed(4160)，固定 UTC/C locale，日期采用固定上限，避免日期变化导致结果漂移。抽样断言同时检查类型、格式、范围或约束，不仅检查“没有抛异常”。

边界值分析：整数相等上下界、零、负数、32 位最大正整数、倒序边界、严格 1/9 位数字；浮点数负/正边界及小数位；日期区间端点；有限唯一值空间用尽。抽样不能证明随机生成器覆盖了所有边界或具有统计均匀性。

等价类划分：合法的 en_US/fi_FI 格式、合法区间、同 seed/不同 seed、可用唯一值/耗尽、合法 boolean 参数/误用上界整数、有效 formatter/未知 formatter。倒序 numberBetween 是源码明确支持的行为，不作为非法输入。TC17 采用实际 API 拒绝的 randomNumber 第二参数，异常类型和消息均明确断言。

功能测试 TC01–TC18 检查生成结果和异常行为。非功能测试 TC19 验证每条记录的五类字段，TC20 测量性能基线：100 行预热、3 组各 1000 行、每行 name/email/uuid，共 9000 次计时调用；断言位于计时区间外，记录中位数、吞吐量、峰值进程内存和输出摘要，没有凭空设定速度 SLA。

TC21–TC23 由真实设备运行及 GitHub Actions matrix 实现；TC24 消费三份同版本的真实报告，检查版本/代码/控制参数以及用例状态一致性。性能数值和随机字符串不要求跨平台完全相同。托管 runner 的通过不能等同于所有实体设备均兼容。

## Tools and reproducibility

PHP 7.4.33 是 v1.9.2 Composer 约束内的兼容基线。新增测试选 PHPUnit 9.6.38，复用其 JUnit、断言及退出码；Composer lock 固定全部依赖；GitHub Actions 使用三系统 runner 和独立 Artifact。PHP 脚本仅补充环境采集、报告提取及简单比较，不实现自制测试执行器。

Faker 原有测试用其兼容的 PHPUnit 5.7.27，独立于新增测试。源码在 upstream/faker 是干净克隆，vendor 是同 commit 的正式包；运行时核对源树摘要。原有 PHPUnit 5 JUnit 遗漏 skipped，用其原生 JSON event log 补足，保留弃用提示。

## Entry, exit and evidence

进入条件：PHP/扩展可用、SHA256 验证工具、composer.lock 安装成功、实际平台要求检查成功。新增套件退出条件：TC01–TC20 全部实际执行并通过，报告齐全；跨平台退出条件：三系统均通过且 TC24 比较成功。未执行标为 Not Run；报告未完成导致无法确定的计数为 null；任何失败/错误均保留日志和非零退出码。

每次保存执行时间、OS/版本、PHP/PHPUnit/Composer、Faker/依赖、种子/时区、用例结果及失败原因、计数和运行耗时。课程证据选入 results/evidence/，原始和重跑结果各保留，不覆盖失败证据。

## Risks

归档软件和 PHP 7.4 不再得到通常的维护；只能对选定基线得出结论。抽样随机测试不能替代完整状态空间、统计测试或安全审计。平台镜像、工具下载源及 Homebrew/PPA 可能变化；精确版本、lock、源码摘要和实际环境必须与报告一起保留。性能只是本机少量测量，不能推广为跨机器 SLA。
