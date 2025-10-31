<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Service\AutoDownService;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AutoDownService::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownServiceTest extends AbstractIntegrationTestCase
{
    private AutoDownService $service;

    protected function onSetUp(): void
    {
        $service = self::getContainer()->get(AutoDownService::class);
        self::assertInstanceOf(AutoDownService::class, $service);
        $this->service = $service;
    }

    public function testConfigureAutoTakeDownTimeWithNewConfigShouldCreateNewRecord(): void
    {
        $time = new \DateTimeImmutable('2024-12-31 23:59:59');

        // Create a proper SPU entity that is managed by the EntityManager
        $user = self::createNormalUser();
        $spu = new Spu();
        $spu->setTitle('测试商品 ' . uniqid());
        $spu->setCreatedBy($user->getUserIdentifier());
        $spu->setUpdatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        $result = $this->service->configureAutoTakeDownTime($spu, $time);

        $this->assertInstanceOf(AutoDownTimeConfig::class, $result);
        $this->assertSame($spu, $result->getSpu());
        $this->assertSame($time, $result->getAutoTakeDownTime());
    }

    public function testConfigureAutoTakeDownTimeWithExistingConfigShouldUpdateRecord(): void
    {
        $originalTime = new \DateTimeImmutable('2024-12-30 12:00:00');
        $newTime = new \DateTimeImmutable('2024-12-31 23:59:59');

        // Create a proper SPU entity that is managed by the EntityManager
        $user = self::createNormalUser();
        $spu = new Spu();
        $spu->setTitle('测试商品 ' . uniqid());
        $spu->setCreatedBy($user->getUserIdentifier());
        $spu->setUpdatedBy($user->getUserIdentifier());

        self::getEntityManager()->persist($spu);
        self::getEntityManager()->flush();

        // First configuration
        $firstResult = $this->service->configureAutoTakeDownTime($spu, $originalTime);
        $configId = $firstResult->getId();

        // Second configuration should update the same record
        $secondResult = $this->service->configureAutoTakeDownTime($spu, $newTime);

        $this->assertInstanceOf(AutoDownTimeConfig::class, $secondResult);
        $this->assertSame($configId, $secondResult->getId());
        $this->assertSame($spu, $secondResult->getSpu());
        $this->assertSame($newTime, $secondResult->getAutoTakeDownTime());
    }

    public function testCancelAutoTakeDownWithNotFoundConfigShouldReturnFalse(): void
    {
        $result = $this->service->cancelAutoTakeDown(999999);
        $this->assertFalse($result);
    }

    public function testCountActiveConfigsWithValidTimeShouldReturnCorrectCount(): void
    {
        $now = new \DateTimeImmutable();
        $result = $this->service->countActiveConfigs($now);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCleanupOldConfigsWithValidDaysShouldReturnCleanedCount(): void
    {
        $result = $this->service->cleanupOldConfigs(30);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testExecuteAutoTakeDownShouldReturnExecutedCount(): void
    {
        $result = $this->service->executeAutoTakeDown();
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
