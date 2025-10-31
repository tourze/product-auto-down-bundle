<?php

namespace Tourze\ProductAutoDownBundle\Command;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\ProductAutoDownBundle\Service\AutoDownService;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;

#[AsCronTask(expression: '* * * * *')]
#[AsCommand(name: self::NAME, description: '自动下架商品')]
#[WithMonologChannel(channel: 'product_auto_down')]
final class AutoTakeDownSpuCommand extends Command
{
    public const NAME = 'product:auto-take-down-spu';

    public function __construct(
        private readonly AutoDownService $autoDownService,
        private readonly LoggerInterface $logger,
        ?string $name = null,
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $executedCount = $this->autoDownService->executeAutoTakeDown();
            $activeCount = $this->autoDownService->countActiveConfigs();

            $output->writeln(sprintf('本次执行下架了 %d 个SPU，还剩 %d 个有效配置', $executedCount, $activeCount));

            $this->logger->info('自动下架任务执行完成', [
                'executed_count' => $executedCount,
                'active_count' => $activeCount,
            ]);
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>自动下架任务执行失败: %s</error>', $exception->getMessage()));
            $this->logger->error('自动下架任务执行失败', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
