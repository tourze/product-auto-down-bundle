<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;
use Tourze\ProductAutoDownBundle\Repository\AutoDownLogRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AutoDownLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownLogRepositoryTest extends AbstractRepositoryTestCase
{
    private AutoDownLogRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(AutoDownLogRepository::class);
    }

    protected function createNewEntity(): object
    {
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        $log = new AutoDownLog();
        $log->setSpuId($spu->getId());
        $log->setConfig($config);
        $log->setAction(AutoDownLogAction::SCHEDULED);
        $log->setDescription('测试日志实体');

        return $log;
    }

    /**
     * @return ServiceEntityRepository<AutoDownLog>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindBySpuIdWithExistingRecordsShouldReturnOrderedResults(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建多个日志记录，添加时间间隔确保排序正确
        $log1 = $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '第一条日志');
        usleep(1000); // 1毫秒间隔，确保创建时间不同

        $log2 = $this->createAutoDownLog($config, AutoDownLogAction::EXECUTED, '第二条日志');
        usleep(1000);

        $log3 = $this->createAutoDownLog($config, AutoDownLogAction::SKIPPED, '第三条日志');

        // Act
        $results = $this->repository->findBySpuId($spu->getId());

        // Assert
        $this->assertCount(3, $results);

        // 验证按创建时间倒序排列（最新的在前）
        $this->assertGreaterThanOrEqual($results[0]->getCreateTime(), $results[1]->getCreateTime());
        $this->assertGreaterThanOrEqual($results[1]->getCreateTime(), $results[2]->getCreateTime());

        // 验证包含所有创建的记录
        $resultIds = array_map(fn ($log) => $log->getId(), $results);
        $expectedIds = [$log1->getId(), $log2->getId(), $log3->getId()];
        $this->assertEquals([], array_diff($expectedIds, $resultIds));
    }

    public function testFindBySpuIdWithNonExistentSpuShouldReturnEmptyArray(): void
    {
        // Act
        $results = $this->repository->findBySpuId(99999);

        // Assert
        $this->assertEmpty($results);
    }

    public function testFindBySpuIdWithLimitShouldRespectLimit(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建5个日志记录
        for ($i = 1; $i <= 5; ++$i) {
            $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, "日志 {$i}");
        }

        // Act
        $results = $this->repository->findBySpuId($spu->getId(), 3);

        // Assert
        $this->assertCount(3, $results);
    }

    public function testFindByConfigIdShouldReturnRelatedLogs(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu1 = $this->createSpu($user);
        $spu2 = $this->createSpu($user);
        $config1 = $this->createAutoDownTimeConfig($user, $spu1);
        $config2 = $this->createAutoDownTimeConfig($user, $spu2);

        // 为不同配置创建日志，添加时间间隔确保排序正确
        $log1 = $this->createAutoDownLog($config1, AutoDownLogAction::SCHEDULED, '配置1的日志');
        usleep(1000); // 时间间隔

        $log2 = $this->createAutoDownLog($config2, AutoDownLogAction::EXECUTED, '配置2的日志');
        usleep(1000);

        $log3 = $this->createAutoDownLog($config1, AutoDownLogAction::SKIPPED, '配置1的另一条日志');

        // Act
        $configId = $config1->getId();
        $this->assertNotNull($configId, 'Config ID should not be null');
        $results = $this->repository->findByConfigId($configId);

        // Assert
        $this->assertCount(2, $results);

        // 验证按创建时间倒序排列（最新的在前）
        $this->assertGreaterThanOrEqual($results[0]->getCreateTime(), $results[1]->getCreateTime());

        // 验证包含正确的记录（config1的两条记录）
        $resultIds = array_map(fn ($log) => $log->getId(), $results);
        $this->assertContains($log1->getId(), $resultIds);
        $this->assertContains($log3->getId(), $resultIds);
        $this->assertNotContains($log2->getId(), $resultIds); // config2的记录不应该包含在内
    }

    public function testFindByConfigIdWithNonExistentConfigShouldReturnEmptyArray(): void
    {
        // Act
        $results = $this->repository->findByConfigId(99999);

        // Assert
        $this->assertEmpty($results);
    }

    public function testFindByActionShouldReturnMatchingLogs(): void
    {
        // Arrange - 清空表以隔离测试数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . AutoDownLog::class)->execute();
        $em->clear();

        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建不同动作的日志
        $scheduledLog1 = $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '已安排1');
        $scheduledLog2 = $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '已安排2');
        $executedLog = $this->createAutoDownLog($config, AutoDownLogAction::EXECUTED, '已执行');

        // Act
        $results = $this->repository->findByAction(AutoDownLogAction::SCHEDULED);

        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals(AutoDownLogAction::SCHEDULED, $results[0]->getAction());
        $this->assertEquals(AutoDownLogAction::SCHEDULED, $results[1]->getAction());
    }

    public function testFindByActionWithLimitShouldRespectLimit(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建多个相同动作的日志
        for ($i = 1; $i <= 4; ++$i) {
            $this->createAutoDownLog($config, AutoDownLogAction::ERROR, "错误 {$i}");
        }

        // Act
        $results = $this->repository->findByAction(AutoDownLogAction::ERROR, 2);

        // Assert
        $this->assertCount(2, $results);
    }

    public function testCountByActionsShouldReturnCorrectCounts(): void
    {
        // Arrange - 清空表以隔离测试数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . AutoDownLog::class)->execute();
        $em->clear();

        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建不同数量的各种动作日志
        $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '安排1');
        $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '安排2');
        $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '安排3');
        $this->createAutoDownLog($config, AutoDownLogAction::EXECUTED, '执行1');
        $this->createAutoDownLog($config, AutoDownLogAction::EXECUTED, '执行2');
        $this->createAutoDownLog($config, AutoDownLogAction::ERROR, '错误1');

        // Act
        $counts = $this->repository->countByActions();

        // Assert
        $this->assertEquals(3, $counts['scheduled'] ?? 0);
        $this->assertEquals(2, $counts['executed'] ?? 0);
        $this->assertEquals(1, $counts['error'] ?? 0);
        $this->assertEquals(0, $counts['skipped'] ?? 0);
        $this->assertEquals(0, $counts['canceled'] ?? 0);
    }

    public function testCountByActionsWithEmptyTableShouldReturnEmptyArray(): void
    {
        // Arrange - 清空表中的数据
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . AutoDownLog::class)->execute();
        $em->clear();

        // Act
        $counts = $this->repository->countByActions();

        // Assert
        $this->assertEmpty($counts);
    }

    public function testSaveWithoutFlushShouldNotPersistToDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);
        $log = new AutoDownLog();
        $log->setSpuId($spu->getId());
        $log->setConfig($config);
        $log->setAction(AutoDownLogAction::SCHEDULED);
        $log->setDescription('测试保存不刷新');

        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->save($log, false);

        // Assert
        $this->assertEquals($initialCount, $this->repository->count([]));
    }

    public function testSaveWithFlushShouldPersistToDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);
        $log = new AutoDownLog();
        $log->setSpuId($spu->getId());
        $log->setConfig($config);
        $log->setAction(AutoDownLogAction::EXECUTED);
        $log->setDescription('测试保存并刷新');

        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->save($log, true);

        // Assert
        $this->assertEquals($initialCount + 1, $this->repository->count([]));
        $this->assertNotNull($log->getId());
    }

    public function testRemoveWithFlushShouldDeleteFromDatabase(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);
        $log = $this->createAutoDownLog($config, AutoDownLogAction::CANCELED, '待删除的日志');

        // 确保实体已被持久化到数据库
        $this->repository->save($log, true);
        $logId = $log->getId();

        $initialCount = $this->repository->count([]);

        // Act
        $this->repository->remove($log, true);

        // Assert
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($logId));
    }

    public function testCleanupOldLogsShouldDeleteOldRecords(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建旧日志（手动设置创建时间）
        $oldLog = $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '旧日志');
        $em = self::getEntityManager();
        $em->createQuery('UPDATE ' . AutoDownLog::class . ' l SET l.createTime = :oldTime WHERE l.id = :id')
            ->setParameter('oldTime', new \DateTimeImmutable('-100 days'))
            ->setParameter('id', $oldLog->getId())
            ->execute()
        ;

        // 创建新日志
        $newLog = $this->createAutoDownLog($config, AutoDownLogAction::EXECUTED, '新日志');

        $initialCount = $this->repository->count([]);

        // Act
        $deletedCount = $this->repository->cleanupOldLogs(90);

        // 清除EntityManager缓存，因为DQL删除不会更新身份映射
        $em = self::getEntityManager();
        $em->clear();

        // Assert
        $this->assertEquals(1, $deletedCount);
        $this->assertEquals($initialCount - 1, $this->repository->count([]));
        $this->assertNull($this->repository->find($oldLog->getId()));
        $this->assertNotNull($this->repository->find($newLog->getId()));
    }

    public function testCleanupOldLogsWithDefaultParameterShouldUse90Days(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);

        // 创建刚好91天前的日志
        $log = $this->createAutoDownLog($config, AutoDownLogAction::ERROR, '91天前的日志');
        $em = self::getEntityManager();
        $em->createQuery('UPDATE ' . AutoDownLog::class . ' l SET l.createTime = :oldTime WHERE l.id = :id')
            ->setParameter('oldTime', new \DateTimeImmutable('-91 days'))
            ->setParameter('id', $log->getId())
            ->execute()
        ;

        // Act
        $deletedCount = $this->repository->cleanupOldLogs();

        // Assert
        $this->assertEquals(1, $deletedCount);
    }

    public function testCleanupOldLogsWithNoOldRecordsShouldReturnZero(): void
    {
        // Arrange
        $user = self::createNormalUser();
        $spu = $this->createSpu($user);
        $config = $this->createAutoDownTimeConfig($user, $spu);
        $this->createAutoDownLog($config, AutoDownLogAction::SCHEDULED, '新日志');

        // Act
        $deletedCount = $this->repository->cleanupOldLogs(30);

        // Assert
        $this->assertEquals(0, $deletedCount);
    }

    /**
     * 创建测试用的SPU
     */
    private function createSpu(UserInterface $user): Spu
    {
        $spu = new Spu();
        $spu->setTitle('测试商品');
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
    private function createAutoDownTimeConfig(UserInterface $user, Spu $spu): AutoDownTimeConfig
    {
        $config = new AutoDownTimeConfig();
        $config->setSpu($spu);
        $config->setAutoTakeDownTime(new \DateTimeImmutable('+1 day'));
        $config->setCreatedBy($user->getUserIdentifier());
        $config->setUpdatedBy($user->getUserIdentifier());

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        return $config;
    }

    /**
     * 创建测试用的自动下架日志
     */
    private function createAutoDownLog(AutoDownTimeConfig $config, AutoDownLogAction $action, string $description): AutoDownLog
    {
        $log = new AutoDownLog();
        $spu = $config->getSpu();
        $this->assertNotNull($spu, 'Spu should not be null');
        $log->setSpuId($spu->getId());
        $log->setConfig($config);
        $log->setAction($action);
        $log->setDescription($description);

        $em = self::getEntityManager();
        $em->persist($log);
        $em->flush();

        return $log;
    }
}
