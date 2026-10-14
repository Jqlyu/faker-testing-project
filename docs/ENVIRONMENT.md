# Testing Environment

## Recorded baseline and compatibility

| Component | Baseline | Evidence/status |
|---|---|---|
| Faker repository | fzaninotto/Faker | 克隆及阅读完成；未修改业务源码 |
| Faker release | v1.9.2 | `848d8125239d7dbf8ab25cb7f054f1a630e68c2e` |
| PHP | 7.4.33 NTS, 64 bit | 本机已实际安装运行 |
| Composer | 2.10.3 | PHAR SHA256 验证；lock 安装完成 |
| New-test PHPUnit | 9.6.38 | Composer dev dependency |
| Upstream PHPUnit | 5.7.27 | 独立 PHAR，SHA256 验证 |
| Dependencies | 29 packages total | `composer.lock`；每次 environment.json 列出全部版本/引用 |
| Timezone / locale | UTC / C | 日期最大值固定；TC14 单独 fi_FI |
| Random seed | 4160 before every system method | seed 可重复性另含 4161 对照 |
| Memory limit | 新增 256M；原有 512M | 原有默认 128M 曾中断，日志保留 |

正式版 v1.9.2 的 composer.json 要求 `^5.3.3 || ^7.0`，不是任意 PHP >=5.3.3，也不包含 PHP 8。克隆最初看到的 master HEAD `5ffe7db6c80f441f150fc88008d64e64af66634b` 增加了 `^8.0`；为稳定测试基线，本项目选正式版 1.9.2，而非随 master 变化。没有运行 PHP 8 兼容性测试，不把 Composer 拒绝不支持的 PHP 版本归类为 Faker 功能缺陷。

GitHub 仓库于 2020-12-11 归档，Composer 标识该包 abandoned；本次安装成功，没有替换为 fakerphp/faker。归档信息来自 [原仓库](https://github.com/fzaninotto/Faker)，版本约束以本地固定 release 的 composer.json 为准。PHP 7.4 已不在 [PHP 常规支持版本](https://www.php.net/supported-versions.php)中。

## Real devices and CI

| Platform | Actual local environment | Execution status | Planned CI |
|---|---|---|---|
| macOS | macOS 26.3 (25D125), Darwin 25.3.0, arm64 | TC01–TC20、TC22 已执行 | macos-latest / PHP 7.4.33 |
| Windows | 未获得真实设备环境信息 | Not Run | windows-latest / PHP 7.4.33 |
| Linux | 未获得真实设备环境信息 | Not Run | ubuntu-latest / PHP 7.4.33 |
| Three-OS comparison | 当前仅有 macOS 报告 | Not Run | 独立 compare job |

本机 PHP 来自 `shivammathur/php/php@7.4`，Homebrew formula 修订 `7.4.33_15`，PHP 构建日期 Oct 9 2026。安装位置是本机事实，不是项目源码中的写死路径。新机器使用自己的 PATH/显式 PHP 程序。

操作系统版本和架构来自 PHP 的 php_uname；macOS 产品版本通过可选 sw_vers 补充。失败或不适用的可选信息保存 null，不补写虚构值。CPU 型号、全机总内存及容器/虚拟化详细配置没有自动采集，不能用这些数据作性能归因。

真实设备安装与复制即可运行的 macOS、Windows PowerShell、Ubuntu 命令见 [README](../README.md)。运行同一脚本、同一 lock；Ubuntu 显式用 php7.4，Windows 用 PHP 解释器执行 vendor/bin/phpunit。手动安装步骤不能代替实际执行结果。

## CI controls and limitations

三系统使用统一 PHP 7.4.33、精确 Composer 2.10.3、Composer lock 及 LF 行尾。记录 runner OS/image/run ID/commit（若可获得）。Action 本身固定 commit，Artifact 保存 30 天。安装失败会停止该平台执行；已有结果始终尝试上传；缺报告的比较返回非零。

GitHub remote 为 https://github.com/Jqlyu/faker-testing-project.git，远程执行状态见 [Actions](https://github.com/Jqlyu/faker-testing-project/actions)。本文件的本机结果和归档用例表属于上传前的执行记录；获得远程三平台结果后再更新，不提前报告三个平台通过。setup-php 或最新 runner 未来可能改变对旧 PHP 的支持；实际失败时记录环境错误并重新选择支持的兼容环境，不绕过平台要求。

## Dependency inventory

全部 29 个精确版本在 [DEPENDENCIES.md](DEPENDENCIES.md)。从 lock 安装而非反复 update；需要升级工具时应重建基线、重跑三个平台并重新归档。
