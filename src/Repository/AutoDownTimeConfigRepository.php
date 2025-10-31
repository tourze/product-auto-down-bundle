<?php

namespace Tourze\ProductAutoDownBundle\Repository;

use Carbon\CarbonImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;

/**
 * @extends ServiceEntityRepository<AutoDownTimeConfig>
 */
#[AsRepository(entityClass: AutoDownTimeConfig::class)]
class AutoDownTimeConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutoDownTimeConfig::class);
    }

    /**
     * 查找需要执行下架的配置
     *
     * @return AutoDownTimeConfig[]
     * @phpstan-return array<AutoDownTimeConfig>
     */
    public function findActiveConfigs(?\DateTimeInterface $now = null): array
    {
        $now ??= CarbonImmutable::now();

        $result = $this->createQueryBuilder('c')
            ->where('c.isActive = :isActive')
            ->andWhere('c.autoTakeDownTime <= :now')
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @phpstan-ignore return.type */
        return $result;
    }

    /**
     * 根据SPU ID查找配置
     * @phpstan-return AutoDownTimeConfig|null
     */
    public function findBySpu(int $spuId): ?AutoDownTimeConfig
    {
        $result = $this->createQueryBuilder('c')
            ->join('c.spu', 's')
            ->where('s.id = :spuId')
            ->setParameter('spuId', $spuId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        assert($result instanceof AutoDownTimeConfig || null === $result);

        return $result;
    }

    /**
     * 查找有效配置数量
     */
    public function countActiveConfigs(?\DateTimeInterface $now = null): int
    {
        $now ??= CarbonImmutable::now();

        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.isActive = :isActive')
            ->andWhere('c.autoTakeDownTime <= :now')
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        assert(is_numeric($result));

        return (int) $result;
    }

    /**
     * 保存实体
     */
    public function save(AutoDownTimeConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(AutoDownTimeConfig $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 清理已取消的旧配置
     */
    public function cleanupOldCanceledConfigs(int $daysOld = 30): int
    {
        $cutoffDate = CarbonImmutable::now()->subDays($daysOld);

        $result = $this->createQueryBuilder('c')
            ->delete()
            ->where('c.isActive = :isActive')
            ->andWhere('c.updateTime < :cutoffDate')
            ->setParameter('isActive', false)
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }
}
