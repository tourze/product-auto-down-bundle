<?php

namespace Tourze\ProductAutoDownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Exception\AutoDownServiceException;
use Tourze\ProductAutoDownBundle\Exception\SpuNotFoundException;
use Tourze\ProductAutoDownBundle\Repository\AutoDownTimeConfigRepository;
use Tourze\ProductCoreBundle\Entity\Spu;

#[WithMonologChannel(channel: 'product_auto_down')]
class AutoDownService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AutoDownTimeConfigRepository $configRepository,
        private readonly AutoDownLogService $logService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 配置SPU的自动下架时间
     */
    public function configureAutoTakeDownTime(Spu $spu, \DateTimeInterface $autoTakeDownTime): AutoDownTimeConfig
    {
        $config = $this->configRepository->findBySpu($spu->getId());
        if (null === $config) {
            $config = new AutoDownTimeConfig();
            $config->setSpu($spu);
        }

        $config->setAutoTakeDownTime($autoTakeDownTime);
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->logService->logScheduled($config, sprintf('设置SPU-%d自动下架时间为%s', $spu->getId(), $autoTakeDownTime->format('Y-m-d H:i:s')));

        return $config;
    }

    /**
     * 取消SPU的自动下架
     */
    public function cancelAutoTakeDown(int $spuId): bool
    {
        $config = $this->configRepository->findBySpu($spuId);
        if (null === $config || !$config->isActive()) {
            return false;
        }

        $config->markAsCanceled();
        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->logService->logCanceled($config, sprintf('取消SPU-%d的自动下架', $spuId));

        return true;
    }

    /**
     * 执行自动下架任务
     */
    public function executeAutoTakeDown(?\DateTimeInterface $now = null): int
    {
        $now ??= new \DateTimeImmutable();
        $configs = $this->configRepository->findActiveConfigs($now);
        $executedCount = 0;

        foreach ($configs as $config) {
            try {
                $this->processSingleConfig($config);
                ++$executedCount;
            } catch (\Throwable $e) {
                $this->logger->error('自动下架SPU失败', [
                    'spu_id' => $config->getSpu()?->getId(),
                    'config_id' => $config->getId(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $this->logService->logError($config, '自动下架执行失败: ' . $e->getMessage(), [
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                ]);
            }
        }

        return $executedCount;
    }

    /**
     * 处理单个配置
     */
    private function processSingleConfig(AutoDownTimeConfig $config): void
    {
        $spu = $config->getSpu();
        if (null === $spu) {
            throw AutoDownServiceException::spuNotFound();
        }

        if (false === $spu->isValid()) {
            $this->logService->logSkipped($config, sprintf('SPU-%d已经下架，跳过执行', $spu->getId()));

            return;
        }

        $spu->setValid(false);

        $this->entityManager->persist($spu);
        $this->entityManager->flush();

        $this->logService->logExecuted($config, sprintf('成功将SPU-%d下架', $spu->getId()));
    }

    /**
     * 获取有效配置数量
     */
    public function countActiveConfigs(?\DateTimeInterface $now = null): int
    {
        return $this->configRepository->countActiveConfigs($now);
    }

    /**
     * 清理旧的已取消配置
     */
    public function cleanupOldConfigs(int $daysOld = 30): int
    {
        return $this->configRepository->cleanupOldCanceledConfigs($daysOld);
    }
}
