<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;
use Tourze\ProductAutoDownBundle\Repository\AutoDownLogRepository;
use Tourze\ProductAutoDownBundle\Service\AutoDownLogService;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AutoDownLogService::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownLogServiceTest extends AbstractIntegrationTestCase
{
    private AutoDownLogService $service;

    private AutoDownLogRepository $logRepository;

    protected function onSetUp(): void
    {
        $this->service = self::getService(AutoDownLogService::class);
        $this->logRepository = self::getService(AutoDownLogRepository::class);
    }

    public function testLogScheduledWithBasicParametersShouldCreateLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $description = '商品已安排自动下架';
        $context = ['user_id' => 123, 'reason' => '库存不足'];

        // Act
        $result = $this->service->logScheduled($config, $description, $context);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertNotNull($result->getId());
        $spu = $config->getSpu();
        $this->assertNotNull($spu, 'Config SPU should not be null');
        $this->assertEquals($spu->getId(), $result->getSpuId());
        $this->assertSame($config, $result->getConfig());
        $this->assertEquals(AutoDownLogAction::SCHEDULED, $result->getAction());
        $this->assertEquals($description, $result->getDescription());
        $this->assertEquals($context, $result->getContext());
        $this->assertNotNull($result->getCreateTime());
    }

    public function testLogScheduledWithNullParametersShouldCreateMinimalLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();

        // Act
        $result = $this->service->logScheduled($config, null, null);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertEquals(AutoDownLogAction::SCHEDULED, $result->getAction());
        $this->assertNull($result->getDescription());
        $this->assertNull($result->getContext());
    }

    public function testLogExecutedWithFullContextShouldCreateExecutedLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $description = '商品自动下架已执行';
        $context = ['execution_time' => '2024-12-31 10:00:00', 'success' => true];

        // Act
        $result = $this->service->logExecuted($config, $description, $context);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertEquals(AutoDownLogAction::EXECUTED, $result->getAction());
        $this->assertEquals($description, $result->getDescription());
        $this->assertEquals($context, $result->getContext());
    }

    public function testLogSkippedWithReasonShouldCreateSkippedLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $description = '商品已下架，跳过自动下架';
        $context = ['current_status' => 'offline', 'skip_reason' => 'already_offline'];

        // Act
        $result = $this->service->logSkipped($config, $description, $context);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertEquals(AutoDownLogAction::SKIPPED, $result->getAction());
        $this->assertEquals($description, $result->getDescription());
        $this->assertEquals($context, $result->getContext());
    }

    public function testLogErrorWithExceptionDetailsShouldCreateErrorLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $description = '下架操作失败';
        $context = [
            'error_message' => 'Database connection failed',
            'error_code' => 500,
            'stack_trace' => 'Exception in AutoDownService::execute()',
        ];

        // Act
        $result = $this->service->logError($config, $description, $context);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertEquals(AutoDownLogAction::ERROR, $result->getAction());
        $this->assertEquals($description, $result->getDescription());
        $this->assertEquals($context, $result->getContext());
    }

    public function testLogCanceledWithCancelReasonShouldCreateCanceledLogRecord(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $description = '用户取消自动下架';
        $context = ['canceled_by' => 'admin', 'cancel_time' => '2024-12-31 09:00:00'];

        // Act
        $result = $this->service->logCanceled($config, $description, $context);

        // Assert
        $this->assertInstanceOf(AutoDownLog::class, $result);
        $this->assertEquals(AutoDownLogAction::CANCELED, $result->getAction());
        $this->assertEquals($description, $result->getDescription());
        $this->assertEquals($context, $result->getContext());
    }

    public function testLogScheduledShouldPersistToDatabase(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $initialCount = $this->logRepository->count([]);

        // Act
        $result = $this->service->logScheduled($config, '测试日志持久化');

        // Assert
        $this->assertEquals($initialCount + 1, $this->logRepository->count([]));

        // 验证数据库中的记录
        $persistedLog = $this->logRepository->find($result->getId());
        $this->assertNotNull($persistedLog);
        $this->assertEquals($result->getAction(), $persistedLog->getAction());
        $this->assertEquals($result->getDescription(), $persistedLog->getDescription());
    }

    public function testMultipleLogCallsShouldCreateSeparateRecords(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $initialCount = $this->logRepository->count([]);

        // Act
        $scheduledLog = $this->service->logScheduled($config, '已安排');
        $executedLog = $this->service->logExecuted($config, '已执行');
        $canceledLog = $this->service->logCanceled($config, '已取消');

        // Assert
        $this->assertEquals($initialCount + 3, $this->logRepository->count([]));

        // 验证三条记录都不同
        $this->assertNotEquals($scheduledLog->getId(), $executedLog->getId());
        $this->assertNotEquals($executedLog->getId(), $canceledLog->getId());
        $this->assertNotEquals($scheduledLog->getAction(), $executedLog->getAction());
        $this->assertNotEquals($executedLog->getAction(), $canceledLog->getAction());
    }

    public function testLogWithComplexContextArrayShouldPreserveStructure(): void
    {
        // Arrange
        $config = $this->createAutoDownTimeConfig();
        $complexContext = [
            'user' => ['id' => 123, 'name' => 'admin'],
            'settings' => ['auto_retry' => true, 'max_attempts' => 3],
            'metadata' => [
                'version' => '1.0.0',
                'environment' => 'test',
                'features' => ['logging', 'retry', 'notification'],
            ],
        ];

        // Act
        $result = $this->service->logScheduled($config, '复杂上下文测试', $complexContext);

        // Assert
        $this->assertEquals($complexContext, $result->getContext());

        // 验证持久化后上下文结构完整
        self::getEntityManager()->clear();
        $persistedLog = $this->logRepository->find($result->getId());
        $this->assertNotNull($persistedLog, 'Persisted log should not be null');
        $this->assertEquals($complexContext, $persistedLog->getContext());
    }

    /**
     * 创建测试用的自动下架时间配置
     */
    private function createAutoDownTimeConfig(): AutoDownTimeConfig
    {
        $user = self::createNormalUser();

        // 创建SPU
        $spu = new Spu();
        $spu->setTitle('测试商品');
        $spu->setCreatedBy($user->getUserIdentifier());
        $spu->setUpdatedBy($user->getUserIdentifier());
        self::getEntityManager()->persist($spu);

        // 创建配置
        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime(new \DateTimeImmutable('+1 day'));
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($config);
        self::getEntityManager()->flush();

        return $config;
    }
}
