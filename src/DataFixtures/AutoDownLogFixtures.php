<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

#[When(env: 'test')]
#[When(env: 'dev')]
class AutoDownLogFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $logData = [
            [
                'configReference' => 'auto-down-config-0',
                'spuId' => 1,
                'action' => AutoDownLogAction::SCHEDULED,
                'message' => '创建SPU-1的自动下架配置',
                'context' => [
                    'take_down_time' => $now->modify('+1 hour')->format('Y-m-d H:i:s'),
                    'operator' => 'system',
                ],
            ],
            [
                'configReference' => 'auto-down-config-1',
                'spuId' => 2,
                'action' => AutoDownLogAction::SCHEDULED,
                'message' => '创建SPU-2的自动下架配置',
                'context' => [
                    'take_down_time' => $now->modify('+1 day')->format('Y-m-d H:i:s'),
                    'operator' => 'admin',
                ],
            ],
            [
                'configReference' => 'auto-down-config-2',
                'spuId' => 3,
                'action' => AutoDownLogAction::SCHEDULED,
                'message' => '创建SPU-3的自动下架配置',
                'context' => [
                    'take_down_time' => $now->modify('+1 week')->format('Y-m-d H:i:s'),
                    'operator' => 'admin',
                ],
            ],
            [
                'configReference' => 'auto-down-config-3',
                'spuId' => 4,
                'action' => AutoDownLogAction::EXECUTED,
                'message' => '成功执行SPU-4的自动下架',
                'context' => [
                    'executed_at' => $now->modify('-30 minutes')->format('Y-m-d H:i:s'),
                    'operator' => 'system',
                ],
            ],
            [
                'configReference' => 'auto-down-config-4',
                'spuId' => 5,
                'action' => AutoDownLogAction::CANCELED,
                'message' => '取消SPU-5的自动下架配置',
                'context' => [
                    'canceled_at' => $now->format('Y-m-d H:i:s'),
                    'operator' => 'admin',
                    'reason' => '商品策略调整',
                ],
            ],
            [
                'configReference' => 'auto-down-config-0',
                'spuId' => 1,
                'action' => AutoDownLogAction::SKIPPED,
                'message' => 'SPU-1已下架，跳过自动下架执行',
                'context' => [
                    'skipped_at' => $now->modify('-1 hour')->format('Y-m-d H:i:s'),
                    'operator' => 'system',
                    'reason' => 'spu_already_inactive',
                ],
            ],
            [
                'configReference' => 'auto-down-config-1',
                'spuId' => 2,
                'action' => AutoDownLogAction::ERROR,
                'message' => 'SPU-2自动下架执行失败',
                'context' => [
                    'error_at' => $now->modify('-2 hours')->format('Y-m-d H:i:s'),
                    'operator' => 'system',
                    'error_message' => 'Database connection timeout',
                    'error_code' => 'DB_TIMEOUT',
                ],
            ],
        ];

        foreach ($logData as $index => $data) {
            $config = $this->getReference($data['configReference'], AutoDownTimeConfig::class);

            $log = new AutoDownLog();
            $log->setConfig($config);
            $log->setSpuId($data['spuId']);
            $log->setAction($data['action']);
            $log->setDescription($data['message']);
            $log->setContext($data['context']);

            $manager->persist($log);
            $this->addReference('auto-down-log-' . $index, $log);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AutoDownTimeConfigFixtures::class,
        ];
    }
}
