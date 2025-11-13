<?php

namespace Tourze\ProductAutoDownBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

/**
 * @extends ServiceEntityRepository<AutoDownLog>
 */
#[AsRepository(entityClass: AutoDownLog::class)]
class AutoDownLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutoDownLog::class);
    }

    /**
     * 根据SPU ID查找日志
     *
     * @return AutoDownLog[]
     * @phpstan-return array<AutoDownLog>
     */
    public function findBySpuId(int $spuId, int $limit = 100): array
    {
        $result = $this->createQueryBuilder('l')
            ->where('l.spuId = :spuId')
            ->setParameter('spuId', $spuId)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        return $result;
    }

    /**
     * 根据配置ID查找日志
     *
     * @return AutoDownLog[]
     * @phpstan-return array<AutoDownLog>
     */
    public function findByConfigId(int $configId, int $limit = 100): array
    {
        $result = $this->createQueryBuilder('l')
            ->join('l.config', 'c')
            ->where('c.id = :configId')
            ->setParameter('configId', $configId)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        return $result;
    }

    /**
     * 根据动作类型查找日志
     *
     * @return AutoDownLog[]
     * @phpstan-return array<AutoDownLog>
     */
    public function findByAction(AutoDownLogAction $action, int $limit = 100): array
    {
        $result = $this->createQueryBuilder('l')
            ->where('l.action = :action')
            ->setParameter('action', $action)
            ->orderBy('l.createTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        return $result;
    }

    /**
     * 统计各种操作的数量
     *
     * @return array<string, int>
     */
    public function countByActions(): array
    {
        $result = $this->createQueryBuilder('l')
            ->select('l.action, COUNT(l.id) as count')
            ->groupBy('l.action')
            ->getQuery()
            ->getArrayResult()
        ;

        /** @var array<string, int> $counts */
        $counts = [];
        foreach ($result as $row) {
            if (!is_array($row) || !isset($row['action']) || !isset($row['count'])) {
                continue;
            }
            // 确保action是枚举字符串值而不是枚举对象
            $actionValue = $row['action'];
            if ($actionValue instanceof AutoDownLogAction) {
                $actionValue = $actionValue->value;
            }
            if (is_string($actionValue) && is_numeric($row['count'])) {
                $counts[$actionValue] = (int) $row['count'];
            }
        }

        return $counts;
    }

    /**
     * 保存实体
     */
    public function save(AutoDownLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(AutoDownLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 清理旧日志
     */
    public function cleanupOldLogs(int $daysOld = 90): int
    {
        $cutoffDate = new \DateTimeImmutable(sprintf('-%d days', $daysOld));

        $result = $this->createQueryBuilder('l')
            ->delete()
            ->where('l.createTime < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }
}
