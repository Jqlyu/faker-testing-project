# Run Guide — report F01–F22 / N01–N02

本项目为 LUT CT60A4160 的 Faker v1.9.2 测试提供可重复执行的代码和证据。当前课程主套件按最新 Group666 PDF 的 **22 个功能用例 F01–F22、2 个非功能用例 N01–N02**组织。原 TC01–TC20 保留为 supplemental 套件；旧 TC21–TC24 是平台记录/比较场景，不再计作本报告的四个功能用例。

固定基线为 Faker 1.9.2 / commit `848d8125239d7dbf8ab25cb7f054f1a630e68c2e`、PHP 7.4.33、Composer 2.10.3、报告套件 PHPUnit 9.6.38。`composer.lock` 固定 29 个包。没有修改 Faker 的业务源码。

本次报告对齐代码使用 `codex/report-case-alignment` 分支；[草稿 PR #1](https://github.com/Jqlyu/faker-testing-project/pull/1) 审阅合并前，首次克隆和 CI 手动运行均选择该分支。合并前的 `main` 可能仍是旧 TC 主套件。以下 clone 命令已明确分支，不把未合并版本当作 main。

PDF 中的 PASS、示例输出、断言数和耗时属于原报告，不能复制为这次执行结果。原成员 F 编号代码没有随当前仓库提供，本次根据报告设计重新实现；对应关系和未明确输入的选择见 [REPORT_CASE_MAPPING.md](REPORT_CASE_MAPPING.md)。本机重跑其他成员负责的用例仍只证明本机环境。报告原文需要修正的地方见 [REPORT_CORRECTIONS.md](REPORT_CORRECTIONS.md)。

2026-10-16 的真实新执行已在本机 macOS 26.3 / arm64 / PHP 7.4.33 完成：[完整报告运行](../results/evidence/20261016T192024Z-darwin-report-5cde49/summary.json)为 24/24 Passed、5973 个父 PHPUnit 断言，另有 54000 个子 worker 校验断言；[Jiaqing 六项运行](../results/evidence/20261016T192025Z-darwin-report-09eaaf/summary.json)为 6/6 Passed、1383 个父断言，另有 54000 个子 worker 断言。六项运行的 N01 中位数为 0.011390125 s、87795.34904138454 records/s，N02 为 6291456 bytes / 6 MiB、256M 配置。它们都是 `local-Darwin` 新证据；**原生 Windows/Linux 尚未执行本次报告套件**。完整数字和运行区分见 [FINAL_REPORT_NOTES.md](FINAL_REPORT_NOTES.md)。

新 [GitHub Actions run 38954165253](https://github.com/Jqlyu/faker-testing-project/actions/runs/38954165253) 的 Windows/macOS/Linux CI 已各完成 24/24 Passed，三平台比较 Passed。三个 CI OS 是 72 次用例执行，报告仍是 24 个逻辑用例。当前所有新运行、下载校验和比较证据见 [REPORT_ALIGNMENT_20261016.md](../results/evidence/REPORT_ALIGNMENT_20261016.md)；旧 evidence README 仅索引历史 TC/upstream 快照。CI Windows 通过不代表用户原生 Windows 11 已执行，下一节原生安装/截图步骤仍需在相应设备完成。

## Project files and suites

| Location | Purpose |
|---|---|
| `composer.json`, `composer.lock`, `sut.json` | 固定依赖和被测版本/源码身份 |
| `tests/Report/`, `phpunit.xml` | 报告 F01–F22/N01–N02；三个测试类，每个逻辑 ID 独立执行 |
| `tests/Support/ReportTestCase.php` | 报告观察与证据辅助 |
| `tests/Supplemental/`, `phpunit-supplemental.xml` | 保留的 TC01–TC20，单独运行 |
| `tests/upstream-bootstrap.php`, `phpunit-infrastructure.xml` | 原有测试 bootstrap 及独立工具自检 |
| `scripts/run-tests.php` | 统一运行入口；默认运行报告 24 用例，保存实际环境和原生 PHPUnit 报告 |
| `scripts/download-tools.php`, `scripts/install.php` | 下载固定工具并验证 SHA256；从 lock 安装并检查平台要求 |
| `scripts/compare-results.php` | 比较同版本、同用例、同控制参数的三平台真实报告 |
| `scripts/fetch-upstream.php` | 克隆并验证固定 commit 的原 Faker 仓库 |
| `scripts/archive-evidence.php` | 保存证据副本，拒绝覆盖已有归档 |
| `.github/workflows/cross-platform.yml` | 三平台 CI 和报告比较 |
| `results/<run-id>/` | 每次运行独立目录 |
| `results/evidence/` | 历史及本次运行归档；当前索引为 REPORT_ALIGNMENT_20261016.md，旧 README 是历史索引，应保留 |
| `docs/TEST_CASES.md` | 当前生成的报告 24 用例表；以其中标注的 run ID/context 为准 |

`vendor/`、`tools/*.phar` 和 `upstream/faker/` 由安装命令生成，保持依赖/上游自己的 README。项目根 README 的安装说明已迁到本文；历史证据内的旧链接按当时快照保留，阅读时改用本文，不修改归档文件。

## First-time Windows x64 setup (PowerShell)

以下命令用于 Windows x64 的原生 PowerShell，不能把 WSL 当作 Windows 结果。先安装 [Git for Windows](https://git-scm.com/download/win)，并安装 **x64 Microsoft Visual C++ Redistributable**。PHP 的 [Windows 安装说明](https://www.php.net/manual/en/install.windows.manual.php)列出运行库要求并推荐 NTS 用于 CLI；VC++ 安装包见 [Microsoft 官方下载页](https://learn.microsoft.com/en-us/cpp/windows/latest-supported-vc-redist)。安装后重新打开 PowerShell，运行 `git --version`。

使用官方 [PHP 历史发行目录](https://windows.php.net/downloads/releases/archives/)中的 `php-7.4.33-nts-Win32-vc15-x64.zip`。下面是首次安装流程；如目标目录已经存在，先查看已有安装，复用正确的 7.4.33 或另选空目录。

```powershell
$phpDir = Join-Path $env:LOCALAPPDATA "FakerTesting\php-7.4.33"
$phpZip = Join-Path $env:TEMP "faker-php-7.4.33.zip"
if (Test-Path $phpDir) { throw "PHP target already exists: $phpDir. Inspect it before continuing." }
New-Item -ItemType Directory -Path (Split-Path $phpDir) -Force | Out-Null
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
Invoke-WebRequest "https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip" -OutFile $phpZip -UseBasicParsing
Expand-Archive -LiteralPath $phpZip -DestinationPath $phpDir
Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini"
$caFile = Join-Path $phpDir "extras\ssl\cacert.pem"
Invoke-WebRequest "https://curl.se/ca/cacert.pem" -OutFile $caFile -UseBasicParsing
@"

extension_dir="$phpDir\ext"
extension=openssl
extension=mbstring
extension=intl
extension=curl
date.timezone=UTC
openssl.cafile="$caFile"
curl.cainfo="$caFile"
"@ | Add-Content -LiteralPath "$phpDir\php.ini" -Encoding UTF8
$env:Path = "$phpDir;$env:Path"
php -v
php --ini
php -m
$loadedExtensions = @(php -m)
foreach ($extensionName in @("openssl", "mbstring", "intl", "dom", "json", "xml", "xmlwriter", "tokenizer", "Phar")) {
    if ($loadedExtensions -notcontains $extensionName) { throw "Missing PHP extension: $extensionName" }
}
```

确认 `php -v` 是 **7.4.33**、`php --ini` 指向上面的文件、扩展检查没有抛出错误。PHP 目录需在当前 PATH 中，以便加载随包附带的 DLL；`extension_dir` 用绝对路径，避免切换工作目录后找不到扩展。新 PowerShell 窗口需重新设置 `$phpDir` 和 `$env:Path`，或通过 Windows 环境变量界面添加该目录。

CA 文件来自 [curl 的 Mozilla CA 导出](https://curl.se/docs/caextract.html)；[`openssl.cafile`](https://www.php.net/manual/en/openssl.configuration.php) 和 [`curl.cainfo`](https://www.php.net/manual/en/curl.configuration.php)均指向该可信文件。若学校/公司代理使用私有 CA，按其 IT 指引配置包含相应根证书的受信任 CA 文件，保留证书验证。`certificate verify failed` 时检查实际加载的 ini、CA 路径及代理设置，不关闭 TLS 校验。`php.ini` 后加的设置仅适用于上述未启用扩展的全新 development 配置；已有配置应编辑现有项，避免重复加载。

克隆到显式目录后安装。仓库为私有仓库时按 Git 登录提示操作，不把 token 写入文件。

```powershell
$projectDir = Join-Path $env:USERPROFILE "Desktop\Faker-Testing-Project"
git clone --branch codex/report-case-alignment https://github.com/Jqlyu/faker-testing-project.git "$projectDir"
Set-Location -LiteralPath $projectDir
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

已有项目目录时不再次 clone 到其中，先进入该目录并检查工作区；如有自己的未提交改动，先保存后再切换。取回远程分支并更新到当前报告代码：

```powershell
Set-Location -LiteralPath "$projectDir"
git status
git fetch origin
git switch codex/report-case-alignment
git pull --ff-only origin codex/report-case-alignment
php scripts/download-tools.php
php scripts/install.php
```

Git 拒绝切换或快进时先保留其输出，不 reset/丢弃已有工作。`install.php` 使用 lock、检查真实平台要求并保存安装日志；不使用 `--ignore-platform-reqs`。首次下载或依赖安装失败时先保存错误输出并处理环境。

## Jiaqing Lyu: six report cases and screenshots

六项责任用例为 F13（日期）、F14（UUID）、F15（safeEmail）、F16（IPv4）、N01（吞吐量）、N02（隔离内存）。安装后在原生 Windows PowerShell 逐项执行，命令和末尾显示的证据目录一起截图：

```powershell
php scripts/run-tests.php --filter=F13
php scripts/run-tests.php --filter=F14
php scripts/run-tests.php --filter=F15
php scripts/run-tests.php --filter=F16
php scripts/run-tests.php --filter=N01
php scripts/run-tests.php --filter=N02
```

每条命令生成新的独立 run 目录。截图应同时显示命令、实际状态/计数和该目录；另外保存 `php -v`、`php --ini` 和实际 Windows 版本的环境截图。Windows 可按 **Win+Shift+S** 框选命令与结果，在截图工具中另存 PNG；建议在该次目录内新建 `screenshots/`，按 `F13-console.png`、`F14-console.png` 等命名，测量文件截图另存 `N01-values.png`、`N02-values.png`。窗口显示不全时分两张保存，不裁掉失败/错误信息。F13–F16 的详细样本在该次 `observations/<ID>.json`，JUnit 对应记录应为同一个 F 编号。截图补充原始文件，不能替代原始报告。

N01 必须打开该次 `measurements/N01.json`，记录三批各自耗时、中位耗时和 records/second；N02 必须打开其独立运行的 `measurements/N02.json`，记录 allocated peak bytes、MiB、实际 memory limit 和 worker 记录。PowerShell 示例（把路径替换成脚本实际打印的目录）：

```powershell
Get-Content -LiteralPath "results\YOUR_N01_RUN_ID\measurements\N01.json"
Get-Content -LiteralPath "results\YOUR_N02_RUN_ID\measurements\N02.json"
Get-ChildItem -Recurse -LiteralPath "results\YOUR_N02_RUN_ID"
```

截取实际数值并把完整对应目录一起保存；数值不能从 PDF、上次 `performance.json` 或 PHPUnit 的整套运行时间/8 MB 内存行补写。N01/N02 成功只表示工作负载完成、输出通过校验、取得有效测量；**performance criteria not assessed**，因为报告没有预先定义速度/内存验收阈值。`256M` 是进程配置上限，不能写成“内存目标已达成”。

相同六项也可一次运行；完整的当前报告套件运行 24 项：

```powershell
php scripts/run-tests.php --tester="Jiaqing Lyu"
php scripts/run-tests.php --cases=F13,F14,F15,F16,N01,N02
php scripts/run-tests.php
```

`--filter` 接受一个完整 F/N ID；`--tester`、`--cases`、`--filter` 每次只能选择一种。`--tester` 是选择报告责任分组，不改变实际执行者、OS 或环境。macOS 上选择该分组仍是 macOS 新执行，不能标注为 Windows。

## macOS setup and execution

本机历史 PHP 来自 `shivammathur/php/php@7.4`，Apple Silicon 安装路径为 `/opt/homebrew/opt/php@7.4/bin`。已有该安装时：

```bash
export PATH="/opt/homebrew/opt/php@7.4/bin:/opt/homebrew/bin:$PATH"
cd "$HOME/Desktop/Faker-Testing-Project"
php -v
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

新机器先按 [Homebrew 官方说明](https://brew.sh/)安装 Homebrew（需要时执行 `xcode-select --install`），再安装历史 PHP；该第三方 tap 的可用性以实际安装结果为准：

```bash
brew tap shivammathur/php
brew install shivammathur/php/php@7.4
export PATH="$(brew --prefix php@7.4)/bin:$PATH"
git clone --branch codex/report-case-alignment https://github.com/Jqlyu/faker-testing-project.git "$HOME/Desktop/Faker-Testing-Project"
cd "$HOME/Desktop/Faker-Testing-Project"
php -v
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

实际版本必须为 7.4.33，并记录架构和构建。不同安装位置以 `brew --prefix` 为准，arm64 结果不能代表 Intel Mac。

## Ubuntu setup and execution

需要 Git、CA 证书以及 PHP 7.4 CLI/扩展。以下示例使用 [Ondřej Surý PHP PPA](https://launchpad.net/~ondrej/+archive/ubuntu/php)，需管理员权限和受该源支持的 Ubuntu 版本。安装源如果不再提供 7.4，保留错误并选择支持这一基线的环境，不绕过 Composer 平台要求。

```bash
sudo apt-get update
sudo apt-get install -y software-properties-common ca-certificates git unzip
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php7.4-cli php7.4-xml php7.4-mbstring php7.4-intl php7.4-curl
git clone --branch codex/report-case-alignment https://github.com/Jqlyu/faker-testing-project.git "$HOME/Faker-Testing-Project"
cd "$HOME/Faker-Testing-Project"
php7.4 -v
php7.4 scripts/download-tools.php
php7.4 scripts/install.php
php7.4 scripts/run-tests.php
```

后续通用命令里的 `php` 在 Ubuntu 换成 `php7.4`，避免系统 PHP 8 干扰。安装示例本身不证明在 Ubuntu/Windows 已执行，实际状态只来自原始运行目录。

## Supplemental, upstream and infrastructure checks

```bash
php scripts/run-tests.php --suite=supplemental
php scripts/run-tests.php --suite=supplemental --filter=TC01
php scripts/fetch-upstream.php
php scripts/run-tests.php --suite=upstream
php vendor/bin/phpunit -c phpunit-infrastructure.xml
php tools/composer.phar validate
```

supplemental 保留旧 20 个 TC 方法，包括地址、电话、浮点数、非法参数和持续记录等额外检查；其 PASS 不增加 F/N 的完成数。upstream 使用独立 PHPUnit 5.7.27 和 512M 测试进程限制，不重写原测试或断言，详见 [EXISTING_TESTS.md](EXISTING_TESTS.md)。工具自检使用合成夹具，不是 Faker 或 OS 的通过证据。Composer 等价入口是 `php tools/composer.phar test`，参数用 `--` 传给 runner。

直接运行 PHPUnit 不生成 runner 的完整环境/样本/历史证据，因此课程截图和报告使用 `scripts/run-tests.php`。失败时保留真实输出和非零退出码，不删测试、改 Faker 或伪造 PASS。

## Evidence files and interpretation

每次报告运行在新的 `results/<run-id>/` 下保存以下文件；筛选运行只包含选中用例的实际结果，不能代替完整 24 项的执行：

| File | Meaning |
|---|---|
| `environment.json` | 实际 OS/架构、执行上下文、PHP/工具/依赖版本、Faker commit、lock/测试摘要和运行参数 |
| `summary.json` | 原生报告解析的身份、计数、状态、退出码及本次运行范围 |
| `junit.xml` | PHPUnit 原生结果 |
| `phpunit.log`, `console.log` | 测试输出及运行器/证据目录输出；失败信息保留 |
| `REPORT_TABLE.md`, `TEST_CASES.md` | 相同八列英文报告表：ID、Tester、Feature being tested、Preconditions、Inputs、Expected Output、Actual Output、Purpose and notes |
| `observations/F13.json` 等 | 对应功能用例实际样本和校验观察；F14/F15 各有自己的记录 |
| `measurements/N01.json`, `measurements/N02.json` | 相应选中非功能用例的数值、工作负载和测量口径；包括 median_seconds、records_per_second 或 peak_allocated_memory_bytes/peak_allocated_memory_mib，以及 memory_limit、worker_pid、worker_validation_assertions、workload |
| `worker-N01.log`, `worker-N02.log` | N01/N02 子进程的实际输出/错误，保留用于核对隔离执行；正常完成时可以为空 |

安装另存 `results/setup-*/composer-install.log`、`platform-requirements.log`、`installation.json`。upstream 另存 `upstream-events.json`，补足 PHPUnit 5 JUnit 遗漏的 skipped；旧 listener 的弃用消息原样保留。supplemental 的旧 `performance.json` 属于 TC20，不能重新标记为 N02 隔离测量。

summary 的 `statistics` 分别记录 24 个设计 ID、本次选中/执行数、父 PHPUnit 方法/断言、benchmark worker 断言和实际平台执行数。子进程中的 PHPUnit Assert 校验不包含在父 JUnit 断言数中，不把两种口径相加后标为 PHPUnit 的原生断言总数。N02 worker 加载 Composer、Faker、PHPUnit assertion 类和其依赖，但不启动 PHPUnit runner；峰值包含这些 PHP 分配，非 Faker 独占内存或 OS RSS。

未执行的用例显示 `Not Run`；报告中断/损坏时无法确认的计数为 `null`，不得推算完成。表格不是另一套结果源，应能追溯到同目录的 JUnit/summary/观察及测量文件。归档时使用终端真实打印的目录：

```bash
php scripts/archive-evidence.php results/YOUR_RUN_ID
```

`results/evidence/` 已有历史成功、跳过和内存中断记录，全部保留。提交前按课程要求检查环境文件里的本机路径，私有原始快照仍应保留以供核对。

## GitHub Actions and comparison

仓库为 [Jqlyu/faker-testing-project](https://github.com/Jqlyu/faker-testing-project)，工作流和执行记录见 [Actions](https://github.com/Jqlyu/faker-testing-project/actions)。工作流可由 push、pull request 或在 Actions 页面选择 **Faker cross-platform report tests → Run workflow** 触发；合并前在分支选择框选 `codex/report-case-alignment`。手动触发要求支持 workflow_dispatch 的 workflow 文件已在默认分支；若默认分支还显示旧工作流名称，可从同一 workflow 文件进入后选报告分支，或使用报告分支的 push/PR 触发。实际远程状态以 Actions 的 run/commit 为准，不从本机结果推断。

矩阵使用 Windows/macOS/Ubuntu 托管 runner，统一固定 PHP、Composer 和 lock，保留每个 OS 的真实环境、run/commit 和 Artifact。下载选定同一次工作流的三个平台报告，分别解压到 `results/imported/` 的不同子目录，每个平台只选择一份完整的报告套件运行：

```bash
php scripts/compare-results.php results/imported results/comparison-ci-YOUR_UNIQUE_ID --context=github-actions
```

`--context=github-actions` 为默认比较口径，需要同一 GitHub workflow run 和 commit 的三个完整平台报告；本机三个系统另用独立输入/输出目录：

```bash
php scripts/compare-results.php results/imported-local results/comparison-local-YOUR_UNIQUE_ID --context=local-device
```

将 `YOUR_UNIQUE_ID` 换成本次唯一的时间/运行编号，给每次比较选择新目录。输出目录已经含有 `comparison.json` 或 `comparison.md` 时，比较脚本拒绝覆盖并退出 2；旧比较证据保留。即使上次因缺平台而 Not Run，也用新目录重做比较。

比较表明确分六个上下文：`local-Windows`、`local-Darwin`、`local-Linux`、`github-Windows`、`github-Darwin`、`github-Linux`。`Darwin` 对应 macOS；每次比较要求所选口径下的三个 OS，不能把本机 Mac 和 CI Windows/Linux 拼成一次通过。输出 `comparison.json` 和 `comparison.md`，其分析不增加第 25 个课程用例。

比较需核对 24 个 F/N 身份与状态、原生证据一致性、版本/代码/lock 和 seed/timezone 等控制参数；不要求随机样本、断言数或性能数值跨机器完全相同。缺失、筛选或不兼容报告不能生成三平台通过结论。同一 context 提供多份完整报告也会报错，所以每个 context 只选一份。CI Windows 是托管 Windows 执行，不能替代成员原生 Windows 11 安装记录；Linux VM、原生 macOS、GitHub runner 各自记录。

Artifacts 通常保留 30 天，以工作流和仓库政策为准；课程提交前下载并保存完整目录。报告引用明确的 run ID、workflow URL 和执行上下文，不拼接不同来源的状态。写作口径见 [FINAL_REPORT_NOTES.md](FINAL_REPORT_NOTES.md)、[ENVIRONMENT.md](ENVIRONMENT.md) 和 [TEST_PLAN.md](TEST_PLAN.md)。
