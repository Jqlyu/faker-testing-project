# Faker 跨平台自动化系统测试

LUT University · CT60A4160 Fundamentals of Software Testing。被测软件为老师指定的 [fzaninotto/Faker](https://github.com/fzaninotto/Faker)，不修改其业务源码。

固定基线：Faker **1.9.2** / commit `848d8125239d7dbf8ab25cb7f054f1a630e68c2e`，PHP **7.4.33**，Composer **2.10.3**，新增测试使用 PHPUnit **9.6.38**。全部 29 个 Composer 包及源码引用已锁定在 `composer.lock`。

当前真实结果与保存的运行编号见 [results/evidence/README.md](results/evidence/README.md)。本机 macOS 已执行；Windows、Linux 及三系统比较在取得真实报告前是 **Not Run**。GitHub 仓库：[Jqlyu/faker-testing-project](https://github.com/Jqlyu/faker-testing-project)。工作流随 push 自动触发；远程三平台的实际状态请查看 [Actions](https://github.com/Jqlyu/faker-testing-project/actions)。当前归档表只记录上传前的本机结果。

## 目录和主要文件

```text
Faker-Testing-Project/
├── composer.json / composer.lock    # 依赖要求 / 精确依赖版本
├── sut.json                         # 被测版本、commit 和验证过的源码摘要
├── phpunit.xml                     # 新增 20 个系统测试的配置
├── phpunit-infrastructure.xml      # 独立的报告工具检查，不计入课程 24 场景
├── tests/
│   ├── bootstrap.php               # Composer 自动加载、UTC、C locale
│   ├── System/FakerSystemTest.php   # TC01–TC20，调用 Faker 公共 API
│   ├── Infrastructure/ReportingTest.php # 失败、空报告、跳过计数等自检
│   └── upstream-bootstrap.php      # 原有测试单独运行的自动加载
├── scripts/
│   ├── download-tools.php          # 下载固定工具并验证 SHA256
│   ├── install.php                 # 从 lock 安装、检查实际平台、保存安装日志
│   ├── run-tests.php               # 调用 PHPUnit，保存环境、日志和结果
│   ├── common.php                  # 小型文件、进程及原生报告读取辅助函数
│   ├── case-catalog.php            # 24 场景的预期结果和表格生成
│   ├── fetch-upstream.php          # 克隆并验证固定的原仓库
│   ├── archive-evidence.php        # 保留课程证据，拒绝覆盖已有证据
│   └── compare-results.php         # TC21–TC24，比较三平台真实报告
├── tools/versions.json             # Composer / 原有 PHPUnit 的版本、URL、SHA256
├── tools/*.phar                    # 本地下载的工具，Git 忽略
├── upstream/faker/                 # 原仓库的干净克隆，Git 忽略
├── vendor/                         # Composer 依赖，Git 忽略
├── .github/workflows/cross-platform.yml # 三平台矩阵和比较任务
├── .gitattributes                  # 统一 LF，避免 Windows 行尾影响证据摘要
├── results/                        # 每次运行独立目录
│   └── evidence/                   # 可随课程仓库提交的真实证据快照
└── docs/                           # 测试计划、用例、环境、原有测试、缺陷、报告素材
```

## 本机 macOS：现在就运行

这台 Apple Silicon Mac 已安装 PHP 7.4.33。以下只设置当前终端的 PATH，不改变其他项目的 PHP 配置：

```bash
export PATH="/opt/homebrew/opt/php@7.4/bin:/opt/homebrew/bin:$PATH"
cd "$HOME/Desktop/Faker-Testing-Project"
php -v
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

另一台有 Homebrew 的 Apple Silicon Mac 安装方式如下。没有 Homebrew 时先按 [Homebrew 官方安装说明](https://brew.sh/) 安装；Xcode 命令行工具可用 `xcode-select --install` 安装。

```bash
brew tap shivammathur/php
brew install shivammathur/php/php@7.4
export PATH="$(brew --prefix php@7.4)/bin:$PATH"
php -v
cd "$HOME/Desktop/Faker-Testing-Project"
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

本机安装时发现 `/opt/homebrew` 仅有未完成的初始化目录；本次补齐了 Homebrew，并安装 PHP 及其依赖，未启动 PHP 服务器。Homebrew PHP tap 的构建修订号也记录在环境文档中。不同电脑的安装布局应以 `brew --prefix` 为准。当前 tap 对 Intel Mac 的支持有限，请在 Intel 设备上另行确认 PHP 7.4 发行包并记录实际版本，不能把本机 arm64 结果作为 Intel 结果。

## Windows：PowerShell 安装和执行

以下适用于 **Windows x64**。先安装 Git，以及 [PHP Windows 安装要求](https://www.php.net/manual/en/install.windows.php)所需的 Visual C++ Runtime。使用官方历史发行包；目录来自当前用户目录，不写死用户名。

```powershell
$phpDir = Join-Path $env:LOCALAPPDATA "FakerTesting\php-7.4.33"
$phpZip = Join-Path $env:TEMP "faker-php-7.4.33.zip"
Invoke-WebRequest "https://windows.php.net/downloads/releases/archives/php-7.4.33-nts-Win32-vc15-x64.zip" -OutFile $phpZip
Expand-Archive $phpZip -DestinationPath $phpDir
Copy-Item "$phpDir\php.ini-development" "$phpDir\php.ini"
Add-Content "$phpDir\php.ini" "`nextension_dir=`"ext`"`nextension=mbstring`nextension=intl`nextension=openssl`ndate.timezone=UTC"
$env:Path = "$phpDir;$env:Path"
php -v
php -m
Set-Location "$env:USERPROFILE\Desktop\Faker-Testing-Project"
php scripts/download-tools.php
php scripts/install.php
php scripts/run-tests.php
```

`Expand-Archive` 面向首次安装；已有目录时先确认其内容。如果 HTTPS 下载提示证书问题，请配置可信的 CA 文件并检查代理证书，不要关闭 TLS 验证。Windows 手动步骤尚未在 Windows 设备上执行。

## Ubuntu Linux：安装和执行

Ubuntu 默认 PHP 版本通常不满足本项目的历史基线。下面示例使用维护者的 [PHP PPA](https://launchpad.net/~ondrej/+archive/ubuntu/php)，适用于该 PPA 当前支持的 Ubuntu LTS 版本（例如 24.04），需要管理员权限。

```bash
sudo apt-get update
sudo apt-get install -y software-properties-common ca-certificates git unzip
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get install -y php7.4-cli php7.4-xml php7.4-mbstring php7.4-intl php7.4-curl
php7.4 -v
cd "$HOME/Faker-Testing-Project"
php7.4 scripts/download-tools.php
php7.4 scripts/install.php
php7.4 scripts/run-tests.php
```

先通过复制项目或 `git clone` 将目录放到上述位置。使用显式 `php7.4` 可以避免系统同时安装的 PHP 8 干扰测试。以上步骤尚未在 Ubuntu 实际执行。若仓库不再提供 7.4，记录安装错误，选择提供该版本的隔离设备或 runner，不用 `--ignore-platform-reqs` 强行绕过。

## 全部、单个及原有测试

三个系统都可使用 PHP 解释器启动 PHPUnit；无需依赖 Unix 的可执行脚本：

```bash
php scripts/run-tests.php
php scripts/run-tests.php --filter=TC01
php scripts/run-tests.php --filter=TC15
php vendor/bin/phpunit
php vendor/bin/phpunit --filter=TC17
```

推荐 `run-tests.php`：它在终端显示通过/失败数量，为每次运行创建新目录并保留环境。直接 `php vendor/bin/phpunit` 仍会写 `results/junit-latest.xml`，但不会生成完整环境快照，也不保存多次历史。

Composer 等价命令：

```bash
php tools/composer.phar test
php tools/composer.phar test -- --filter=TC01
```

原有测试独立使用其兼容的 PHPUnit 5.7.27；从同一个 Faker commit 运行，不修改上游文件。旧版 XML bootstrap 配置原本要求该仓库自己的 vendor，所以我们用独立 bootstrap 指向项目已安装的同版本 Faker。测试进程内存设为 512M，保留全部原有断言：

```bash
php scripts/fetch-upstream.php
php scripts/download-tools.php
php scripts/run-tests.php --suite=upstream
```

报告工具自检与课程测试分开：

```bash
php vendor/bin/phpunit -c phpunit-infrastructure.xml
```

Ubuntu 将以上命令的 `php` 换成 `php7.4`。遇到测试失败，不删除测试或修改 Faker；查看报告的失败原因并修复环境或记录真实缺陷。脚本向终端返回非零退出码，GitHub Actions 会显示失败。

## 查看和保存报告

每次完整运行自动生成：

- `results/<run-id>/junit.xml`：PHPUnit 原生机器可读测试结果。
- `phpunit.log`：完整输出，包括失败和错误信息。
- `summary.json`：从原生报告提取的每个用例、计数、状态、运行时间和退出码。
- `environment.json`：OS/版本、架构、PHP、PHPUnit、Composer、Faker commit、29 个依赖版本、lock/代码摘要、运行参数。
- `performance.json`：TC20 的预热、9000 次调用、三组耗时、中位数、吞吐量和输出摘要；单个其他测试没有此文件。
- `TEST_CASES.md`：该次运行的 24 场景表。完整运行还会更新 `docs/TEST_CASES.md`；单个用例不覆盖全套用例表。
- 原有测试还生成 `upstream-events.json`，补足 PHPUnit 5 原生 JUnit 遗漏的跳过记录。该日志是旧框架的原生功能，其弃用提示保留在日志中。

安装命令另存 `results/setup-*/composer-install.log`、`platform-requirements.log` 和 `installation.json`。如果 PHP 在报告写完之前中断，`summary.json` 标为 `Error`，无法确认的计数为 `null`；部分进度只存在日志里，不能宣称全部测试已运行。

证据保留命令（将示例运行目录换成终端实际打印的目录）：

```bash
php scripts/archive-evidence.php results/YOUR_RUN_ID
```

`results/evidence/` 会被 Git 跟踪，其他临时运行目录不提交。它包含本次真实通过、原有跳过及一次内存中断的证据。提交前可检查环境文件里的本机路径是否需要按课程要求脱敏；保留原始私有快照以供核对。

## 上传 GitHub、启动 Actions、下载三系统报告

本项目已关联私有仓库 [Jqlyu/faker-testing-project](https://github.com/Jqlyu/faker-testing-project)，默认分支为 `main`。以后在当前目录修改代码后，用下面的命令保存并上传：

```bash
git status
git add .
git commit -m "Update Faker tests and documentation"
git push origin main
```

Windows 可在 PowerShell 中执行同样的 Git 命令。Git 用户名/邮箱或登录未设置时按 Git 的提示配置，不要把访问令牌写入代码。

Push、Pull Request 和手动触发都可运行工作流。GitHub 仓库 → **Actions** → **Faker cross-platform system tests** → **Run workflow** → 选择 `main`。手动运行要求 workflow 文件已在默认分支上。

矩阵使用 `windows-latest`、`macos-latest`、`ubuntu-latest`，统一 PHP 7.4.33 和同一 lock；Actions 本身固定为 commit。`fail-fast: false` 保证一个平台失败时其他平台继续运行；上传步骤 `always()` 保存已产生的日志，比较步骤缺少任何平台不会显示通过。

运行页面底部 **Artifacts** 下载：

- `faker-results-windows-latest`
- `faker-results-macos-latest`
- `faker-results-ubuntu-latest`
- `faker-cross-platform-comparison`

每个平台单独打开对应 job 可看到安装及测试日志。下载后的 ZIP 各解压到 `results/imported/` 下不同子目录；每个 OS 只放该次选定的一份全套运行报告，再执行：

```bash
php scripts/compare-results.php results/imported results/comparison
```

比较检查 20 个用例的身份与状态、PHP/PHPUnit、Faker 源码、lock、测试代码、种子、时区等。它不要求三个平台生成相同随机字符串，也不把性能差异直接认定为失败。没有三个平台完整的真实证据时 TC24 为 `Not Run`/`Error`，并返回非零。

汇总后的 `results/comparison/TEST_CASES.md` 是三平台汇总表；可复制到 `docs/TEST_CASES.md` 用于课程报告。重新跑单个平台时默认表只反映本次运行，所以最终提交前应明确选择汇总表。

Artifacts 配置保留 30 天，受仓库保留政策约束；课程提交前下载并归档。CI runner 属于真实 OS 的自动化环境，但不能代替同学的实体 Windows、macOS、Linux 安装记录；按各平台的手动步骤另外运行、保留报告。

## 如何写 Final Report

按 [docs/FINAL_REPORT_NOTES.md](docs/FINAL_REPORT_NOTES.md) 的八部分组织报告，引用 [TEST_PLAN](docs/TEST_PLAN.md)、[TEST_CASES](docs/TEST_CASES.md)、[ENVIRONMENT](docs/ENVIRONMENT.md)、[EXISTING_TESTS](docs/EXISTING_TESTS.md)、[BUG_REPORT](docs/BUG_REPORT.md)。

报告区分：24 个设计场景、20 个新增自动化方法、Faker 原有 2078 个测试实例、6 个报告工具自检。通过数不能混加成一个课程测试覆盖率。当前只能写“本机 macOS 已验证”；TC21、TC23、TC24 等取得真实结果后再更新。保留 seed、版本、commit、JUnit、失败/跳过原因和每次运行编号，让老师可以复现。

Composer schema 验证命令：`php tools/composer.phar validate`。精确 Faker 版本会产生 Composer 的通用 semver 建议，这是本项目为复现主动选择的固定版本，不是安装失败。PHP 7.4 和原 Faker 已结束常规维护，只用于本课程历史兼容性基线；没有证明其对现代 PHP 或生产环境安全性适用。

## 添加未来的用例

在 `tests/System/` 添加以 `testTC25...` 命名的方法（或新 Test.php 文件），再在 `scripts/case-catalog.php` 登记 TC25 的目标、条件、输入和预期结果。TC21–TC24 保留为平台场景。全套运行和比较会自动按登记的系统用例检查完整性，不需要改写测试总数；未登记或漏执行的用例会明确失败。最后重跑全套、更新计划和归档新证据。
