# Product Auto Down Bundle

[English](README.md) | [中文](README.zh-CN.md)

商品自动下架 Bundle，为 Symfony 应用程序提供自动化的商品下架功能。您可以设置商品在指定时间自动下架。

## 功能特性

- **定时下架**：设置商品在指定时间自动下架
- **命令行工具**：提供控制台命令执行和管理下架操作
- **管理界面**：集成 EasyAdmin 管理配置
- **日志系统**：完整记录所有下架操作
- **批量处理**：高效批量执行计划下架
- **清理工具**：自动清理旧的配置记录

## 安装

```bash
composer require tourze/product-auto-down-bundle
```

## 配置

### 1. 注册 Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\ProductAutoDownBundle\ProductAutoDownBundle::class => ['all' => true],
];
```

### 2. 运行数据库迁移

```bash
# 生成并执行迁移
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 3. 加载演示数据（可选）

```bash
# 加载演示数据
php bin/console doctrine:fixtures:load --group=ProductAutoDownBundle
```

## 使用方法

### 控制台命令

Bundle 提供了管理自动下架的命令：

```bash
# 执行计划下架
php bin/console product:auto-take-down-spu

# 指定执行时间
php bin/console product:auto-take-down-spu --datetime="2024-12-31 23:59:59"

# 强制执行（跳过确认）
php bin/console product:auto-take-down-spu --force

# 详细输出
php bin/console product:auto-take-down-spu -v
```

**命令选项**：
- `--datetime` : 指定执行时间，格式：`Y-m-d H:i:s`
- `--force` : 强制执行，跳过确认提示
- `-v|--verbose` : 显示详细的执行信息

### 服务 API

### AutoDownService

管理自动下架的主要服务：

```php
use Tourze\ProductAutoDownBundle\Service\AutoDownService;

class YourController
{
    public function __construct(
        private AutoDownService $autoDownService
    ) {}

    public function scheduleAutoDown(): void
    {
        // 配置 SPU 自动下架时间
        $config = $this->autoDownService->configureAutoTakeDownTime(
            spu: $spuEntity,
            autoTakeDownTime: new \DateTimeImmutable('+1 week')
        );

        // 取消 SPU 自动下架
        $success = $this->autoDownService->cancelAutoTakeDown(123);

        // 执行计划下架
        $executedCount = $this->autoDownService->executeAutoTakeDown();

        // 获取有效配置数量
        $count = $this->autoDownService->countActiveConfigs();

        // 清理旧配置
        $cleaned = $this->autoDownService->cleanupOldConfigs(30);
    }
}
```

### 仓储方法

```php
use Tourze\ProductAutoDownBundle\Repository\AutoDownTimeConfigRepository;
use Tourze\ProductAutoDownBundle\Repository\AutoDownLogRepository;

// 配置仓储
$configs = $configRepository->findActiveConfigs(); // 查找有效配置
$config = $configRepository->findBySpu(123);       // 根据 SPU 查找
$count = $configRepository->countActiveConfigs();  // 统计有效配置数量

// 日志仓储
$logs = $logRepository->findBySpuId(123);         // 查找 SPU 日志
$logs = $logRepository->findByAction($action);    // 根据操作类型查找
$stats = $logRepository->countByActions();        // 操作统计
```

## 管理界面

Bundle 集成了 EasyAdmin 进行配置管理：

- **自动下架配置**: `/admin/product/auto-down-config`
  - 查看和管理自动下架配置
  - 关联 SPU 实体
  - 启用/禁用配置

- **下架日志**: `/admin/product/auto-down-logs`
  - 查看详细执行日志
  - 跟踪 SPU 下架历史
  - 按操作和时间筛选

## 数据库结构

### AutoDownTimeConfig (配置表)

| 字段 | 类型 | 说明 |
|------|------|------|
| id | int | 主键 ID |
| spu | Spu | 关联的 SPU 实体 |
| autoTakeDownTime | DateTimeImmutable | 预定下架时间 |
| isActive | bool | 是否有效 |
| createTime | DateTimeImmutable | 创建时间 |
| updateTime | DateTimeImmutable | 更新时间 |

### AutoDownLog (日志表)

| 字段 | 类型 | 说明 |
|------|------|------|
| id | string | 唯一日志 ID |
| config | AutoDownTimeConfig | 关联配置 |
| spuId | int | 商品 SPU ID |
| action | AutoDownLogAction | 日志操作类型 |
| message | string | 日志消息 |
| context | array | 额外上下文数据 |
| createTime | DateTimeImmutable | 日志创建时间 |
| createdFromIp | string | 来源 IP 地址 |

### AutoDownLogAction (操作类型)

| 值 | 说明 | 含义 |
|----|------|------|
| SCHEDULED | 已计划 | 配置已创建/更新 |
| EXECUTED | 已执行 | 下架成功执行 |
| CANCELED | 已取消 | 配置已取消 |
| SKIPPED | 已跳过 | 下架跳过（已下架） |
| ERROR | 错误 | 执行过程中发生错误 |

## 异常处理

Bundle 提供特定的异常进行错误处理：

```php
use Tourze\ProductAutoDownBundle\Exception\SpuNotFoundException;
use Tourze\ProductAutoDownBundle\Exception\AutoDownServiceException;

try {
    $this->autoDownService->configureAutoTakeDownTime($spu, $time);
} catch (SpuNotFoundException $e) {
    // SPU 不存在
} catch (AutoDownServiceException $e) {
    // 一般服务错误
}
```

## 定时执行

使用 cron 或 Symfony Cron Jobs Bundle 配置命令自动运行：

```bash
# crontab -e
# 每小时执行一次
0 * * * * cd /var/www/project && php bin/console product:auto-take-down-spu
```

使用 Symfony Cron Jobs Bundle：

```php
// config/packages/cron_jobs.yaml
cron_jobs:
    auto_take_down_spu:
        command: 'product:auto-take-down-spu'
        schedule: '0 * * * *'  # 每小时
        description: '执行计划的商品下架'
```

## 测试

### 单元测试

```bash
# 运行所有测试
./vendor/bin/phpunit packages/product-auto-down-bundle/tests

# 运行特定测试文件
./vendor/bin/phpunit packages/product-auto-down-bundle/tests/Service/AutoDownServiceTest.php

# 生成覆盖率报告
./vendor/bin/phpunit packages/product-auto-down-bundle/tests --coverage-html=coverage
```

### 代码质量

```bash
# PHPStan 静态分析
./vendor/bin/phpstan analyse packages/product-auto-down-bundle --level=8

# 代码风格检查
./vendor/bin/php-cs-fixer fix packages/product-auto-down-bundle/src --dry-run
```

## 许可证

MIT License

## 支持

Tourze Team