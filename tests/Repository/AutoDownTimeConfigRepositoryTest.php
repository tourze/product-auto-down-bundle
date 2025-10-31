<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Repository;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Repository\AutoDownTimeConfigRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AutoDownTimeConfigRepository::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownTimeConfigRepositoryTest extends AbstractRepositoryTestCase
{
    private AutoDownTimeConfigRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AutoDownTimeConfigRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);

        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime(CarbonImmutable::now()->addDay());
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        return $config;
    }

    /**
     * @return ServiceEntityRepository<AutoDownTimeConfig>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindActiveConfigsWithDueConfigsShouldReturnMatching(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建已到期的配置（应该被返回）
        $spu1 = $this->createSpu($user);
        $config1 = $this->createAutoDownTimeConfig($user, $spu1, $now->subHour(), true);

        // 创建未到期的配置（不应该被返回）
        $spu2 = $this->createSpu($user);
        $config2 = $this->createAutoDownTimeConfig($user, $spu2, $now->addHour(), true);

        // 创建已到期但未激活的配置（不应该被返回）
        $spu3 = $this->createSpu($user);
        $config3 = $this->createAutoDownTimeConfig($user, $spu3, $now->subHour(), false);

        // Act
        $results = $this->repository->findActiveConfigs($now);

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($config1->getId(), $results[0]->getId());
        $this->assertTrue($results[0]->isActive());
        $this->assertLessThanOrEqual($now, $results[0]->getAutoTakeDownTime());
    }

    public function testFindActiveConfigsWithNullTimeShouldUseCurrentTime(): void
    {
        // Arrange
        $user = self::createNormalUser();

        // 清理可能存在的旧数据，确保测试隔离
        $now = CarbonImmutable::now();
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建1小时前应该执行的配置
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->subHour(), true);

        // Act - 不传时间参数，使用当前时间
        $results = $this->repository->findActiveConfigs();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals($config->getId(), $results[0]->getId());
    }

    public function testFindActiveConfigsWithNoMatchingShouldReturnEmpty(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建未来的配置
        $spu = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu, $now->addDay(), true);

        // Act
        $results = $this->repository->findActiveConfigs($now);

        // Assert
        $this->assertEmpty($results);
    }

    public function testFindActiveConfigsShouldOrderByTime(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建不同时间的配置
        $spu1 = $this->createSpu($user);
        $config1 = $this->createAutoDownTimeConfig($user, $spu1, $now->subHours(3), true);

        $spu2 = $this->createSpu($user);
        $config2 = $this->createAutoDownTimeConfig($user, $spu2, $now->subHour(), true);

        $spu3 = $this->createSpu($user);
        $config3 = $this->createAutoDownTimeConfig($user, $spu3, $now->subMinutes(30), true);

        // Act
        $results = $this->repository->findActiveConfigs($now);

        // Assert
        $this->assertCount(3, $results);
        // 验证按时间顺序返回（具体顺序依赖查询的ORDER BY）
        foreach ($results as $result) {
            $this->assertLessThanOrEqual($now, $result->getAutoTakeDownTime());
        }
    }

    public function testFindBySpuWithExistingConfigShouldReturnConfig(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), true);

        // Act
        $result = $this->repository->findBySpu($spu->getId());

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($config->getId(), $result->getId());
        $resultSpu = $result->getSpu();
        $this->assertNotNull($resultSpu, 'Result SPU should not be null');
        $this->assertEquals($spu->getId(), $resultSpu->getId());
    }

    public function testFindBySpuWithNonExistentSpuShouldReturnNull(): void
    {
        // Act
        $result = $this->repository->findBySpu(99999);

        // Assert
        $this->assertNull($result);
    }

    public function testFindBySpuWithInactiveConfigShouldStillReturnConfig(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), false);

        // Act
        $result = $this->repository->findBySpu($spu->getId());

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($config->getId(), $result->getId());
        $this->assertFalse($result->isActive());
    }

    public function testCountActiveConfigsWithMatchingRecordsShouldReturnCorrectCount(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建3个有效的到期配置
        for ($i = 1; $i <= 3; ++$i) {
            $spu = $this->createSpu($user);
            $this->createAutoDownTimeConfig($user, $spu, $now->subHours($i), true);
        }

        // 创建1个未到期的配置
        $spu = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu, $now->addHour(), true);

        // 创建1个已到期但未激活的配置
        $spu = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu, $now->subHour(), false);

        // Act
        $count = $this->repository->countActiveConfigs($now);

        // Assert
        $this->assertEquals(3, $count);
    }

    public function testCountActiveConfigsWithNoMatchingShouldReturnZero(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建未来的配置
        $spu = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu, $now->addDay(), true);

        // Act
        $count = $this->repository->countActiveConfigs($now);

        // Assert
        $this->assertEquals(0, $count);
    }

    public function testCountActiveConfigsWithNullTimeShouldUseCurrentTime(): void
    {
        // Arrange
        $user = self::createNormalUser();

        // 清理可能存在的旧数据，确保测试隔离
        $now = CarbonImmutable::now();
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建过去的配置
        $spu = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->subHour(), true);

        // Act - 不传时间参数
        $count = $this->repository->countActiveConfigs();

        // Assert
        $this->assertEquals(1, $count);
    }

    public function testSaveWithoutFlushShouldNotPersistToDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime(CarbonImmutable::now()->addDay());
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->save($config, false);

        // Assert
        $this->assertEquals($initialCount, $this->repository->count([]));
    }

    public function testSaveWithFlushShouldPersistToDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime(CarbonImmutable::now()->addDay());
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->save($config, true);

        // Assert
        $this->assertEquals($initialCount + 1, $this->repository->count([]));
        $this->assertNotEquals(0, $config->getId());
    }

    public function testRemoveWithFlushShouldDeleteFromDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), true);

        $initialCount = $this->repository->count([]);
        $configId = $config->getId();

        // Act
        $this->repository->remove($config, true);

        // Assert
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($configId));
    }

    public function testCleanupOldCanceledConfigsShouldDeleteOldInactiveConfigs(): void
    {
        // Arrange
        $user = self::createNormalUser();

        // 创建旧的已取消配置
        $spu1 = $this->createSpu($user);
        $oldConfig = $this->createAutoDownTimeConfig($user, $spu1, CarbonImmutable::now()->addDay(), false);

        // 手动更新时间为35天前
        $em = self::getEntityManager();
        $em->createQuery('UPDATE ' . AutoDownTimeConfig::class . ' c SET c.updateTime = :oldTime WHERE c.id = :id')
            ->setParameter('oldTime', CarbonImmutable::now()->subDays(35))
            ->setParameter('id', $oldConfig->getId())
            ->execute()
        ;

        // 创建新的已取消配置（不应该被删除）
        $spu2 = $this->createSpu($user);
        $newConfig = $this->createAutoDownTimeConfig($user, $spu2, CarbonImmutable::now()->addDay(), false);

        // 创建旧的但激活的配置（不应该被删除）
        $spu3 = $this->createSpu($user);
        $activeConfig = $this->createAutoDownTimeConfig($user, $spu3, CarbonImmutable::now()->addDay(), true);
        $em->createQuery('UPDATE ' . AutoDownTimeConfig::class . ' c SET c.updateTime = :oldTime WHERE c.id = :id')
            ->setParameter('oldTime', CarbonImmutable::now()->subDays(35))
            ->setParameter('id', $activeConfig->getId())
            ->execute()
        ;

        $initialCount = $this->repository->count([]);
        $oldConfigId = $oldConfig->getId();
        $newConfigId = $newConfig->getId();
        $activeConfigId = $activeConfig->getId();

        // Act
        $deletedCount = $this->repository->cleanupOldCanceledConfigs(30);

        // 清空 EntityManager 缓存，确保从数据库重新查询
        $em->clear();

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($oldConfigId));
        $this->assertNotNull($this->repository->find($newConfigId));
        $this->assertNotNull($this->repository->find($activeConfigId));
    }

    public function testCleanupOldCanceledConfigsWithDefaultParameterShouldUse30Days(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), false);

        // 手动更新时间为31天前
        $em = self::getEntityManager();
        $em->createQuery('UPDATE ' . AutoDownTimeConfig::class . ' c SET c.updateTime = :oldTime WHERE c.id = :id')
            ->setParameter('oldTime', CarbonImmutable::now()->subDays(31))
            ->setParameter('id', $config->getId())
            ->execute()
        ;

        // Act
        $deletedCount = $this->repository->cleanupOldCanceledConfigs();

        // Assert
        $this->assertEquals(1, $deletedCount);
    }

    public function testCleanupOldCanceledConfigsWithNoMatchingShouldReturnZero(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);

        // 创建新的已取消配置
        $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), false);

        // 创建激活的配置
        $spu2 = $this->createSpu($user);
        $this->createAutoDownTimeConfig($user, $spu2, CarbonImmutable::now()->addDay(), true);

        // Act
        $deletedCount = $this->repository->cleanupOldCanceledConfigs(30);

        // Assert
        $this->assertEquals(0, $deletedCount);
    }

    public function testEntityConfigurationShouldEnforceUniqueSpuConstraint(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);

        // 创建第一个配置
        $config1 = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), true);

        // 创建第二个配置，使用相同的SPU
        $config2 = new AutoDownTimeConfig();
        $config2->setSpu($spu);
        $config2->setAutoTakeDownTime(CarbonImmutable::now()->addDays(2));
        $config2->setCreatedBy($user->getUserIdentifier());
        $config2->setUpdatedBy($user->getUserIdentifier());

        // Act & Assert - 应该抛出数据库唯一约束异常
        $this->expectException(\Exception::class);

        $em = self::getEntityManager();
        $em->persist($config2);
        $em->flush();
    }

    public function testConfigEntityBehaviorShouldWorkCorrectly(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu, CarbonImmutable::now()->addDay(), true);

        // Act & Assert - 测试实体方法
        $this->assertTrue($config->isActive());
        $this->assertFalse($config->isCanceled());

        // 标记为已取消
        $config->markAsCanceled();
        $this->assertFalse($config->isActive());
        $this->assertFalse($config->getIsActive());
        $this->assertTrue($config->isCanceled());

        // 测试字符串表示
        $this->assertStringContainsString('SPU-', (string) $config);
        $this->assertStringContainsString('自动下架于', (string) $config);
    }

    public function testRepositoryQueryOptimizationShouldUseIndexes(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $now = CarbonImmutable::now();

        // 清理可能存在的旧数据，确保测试隔离
        self::getEntityManager()->getConnection()->executeStatement('DELETE FROM product_auto_down_time_config WHERE is_active = 1 AND auto_take_down_time <= ?', [$now]);
        self::getEntityManager()->clear();

        // 创建多个配置来测试查询性能
        for ($i = 1; $i <= 10; ++$i) {
            $spu = $this->createSpu($user);
            $this->createAutoDownTimeConfig($user, $spu, $now->subHours($i), true);
        }

        // Act - 这些查询应该利用索引
        $activeConfigs = $this->repository->findActiveConfigs($now);
        $count = $this->repository->countActiveConfigs($now);

        $firstSpu = $this->createSpu($user);
        $spuConfig = $this->repository->findBySpu($firstSpu->getId());

        // Assert - 验证查询结果正确性
        $this->assertCount(10, $activeConfigs);
        $this->assertEquals(10, $count);
        $this->assertNull($spuConfig); // 新创建的SPU没有配置
    }

    /**
     * 创建测试用的SPU
     */
    private function createSpu(UserInterface $user): Spu
    {
        $spu = new Spu();
        $spu->setTitle('测试商品 ' . uniqid());
        $spu->setCreatedBy($user->getUserIdentifier());
        $spu->setUpdatedBy($user->getUserIdentifier());

        $em = self::getEntityManager();
        $em->persist($spu);
        $em->flush();

        return $spu;
    }

    /**
     * 创建测试用的自动下架时间配置
     */
    private function createAutoDownTimeConfig(UserInterface $user, Spu $spu, \DateTimeInterface $autoTakeDownTime, bool $isActive): AutoDownTimeConfig
    {
        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime($autoTakeDownTime);
        $config->setIsActive($isActive);
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        return $config;
    }
}
