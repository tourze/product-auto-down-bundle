<?php

namespace Tourze\ProductAutoDownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

class AutoDownLogService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 记录已安排日志
     *
     * @param array<string, mixed>|null $context
     */
    public function logScheduled(AutoDownTimeConfig $config, ?string $description = null, ?array $context = null): AutoDownLog
    {
        return $this->createLog($config, AutoDownLogAction::SCHEDULED, $description, $context);
    }

    /**
     * 记录已执行日志
     *
     * @param array<string, mixed>|null $context
     */
    public function logExecuted(AutoDownTimeConfig $config, ?string $description = null, ?array $context = null): AutoDownLog
    {
        return $this->createLog($config, AutoDownLogAction::EXECUTED, $description, $context);
    }

    /**
     * 记录已跳过日志
     *
     * @param array<string, mixed>|null $context
     */
    public function logSkipped(AutoDownTimeConfig $config, ?string $description = null, ?array $context = null): AutoDownLog
    {
        return $this->createLog($config, AutoDownLogAction::SKIPPED, $description, $context);
    }

    /**
     * 记录执行错误日志
     *
     * @param array<string, mixed>|null $context
     */
    public function logError(AutoDownTimeConfig $config, ?string $description = null, ?array $context = null): AutoDownLog
    {
        return $this->createLog($config, AutoDownLogAction::ERROR, $description, $context);
    }

    /**
     * 记录已取消日志
     *
     * @param array<string, mixed>|null $context
     */
    public function logCanceled(AutoDownTimeConfig $config, ?string $description = null, ?array $context = null): AutoDownLog
    {
        return $this->createLog($config, AutoDownLogAction::CANCELED, $description, $context);
    }

    /**
     * 创建日志记录
     *
     * @param array<string, mixed>|null $context
     */
    private function createLog(
        AutoDownTimeConfig $config,
        AutoDownLogAction $action,
        ?string $description = null,
        ?array $context = null,
    ): AutoDownLog {
        $log = new AutoDownLog();
        $log->setSpuId($config->getSpu()?->getId());
        $log->setConfig($config);
        $log->setAction($action);
        $log->setDescription($description);
        $log->setContext($context);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }
}
