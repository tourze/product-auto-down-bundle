<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductCoreBundle\Entity\Spu;

#[When(env: 'test')]
#[When(env: 'dev')]
class AutoDownTimeConfigFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        // 创建测试用的 SPU 实体
        $spus = [];
        for ($i = 1; $i <= 7; ++$i) {
            $spu = new Spu();
            $spu->setTitle('测试商品 ' . $i);
            $spu->setCreatedBy('system');
            $spu->setUpdatedBy('system');
            $manager->persist($spu);
            $spus[$i] = $spu;
        }

        // 先刷新 SPU 以获得 ID
        $manager->flush();

        // 为每个 SPU 添加引用
        foreach ($spus as $index => $spu) {
            $this->addReference('spu-' . $index, $spu);
        }

        $configs = [
            [
                'spuIndex' => 1,
                'autoTakeDownTime' => $now->modify('+1 hour'),
                'isActive' => true,
                'description' => '1小时后自动下架的有效配置',
            ],
            [
                'spuIndex' => 2,
                'autoTakeDownTime' => $now->modify('+1 day'),
                'isActive' => true,
                'description' => '1天后自动下架的有效配置',
            ],
            [
                'spuIndex' => 3,
                'autoTakeDownTime' => $now->modify('+1 week'),
                'isActive' => true,
                'description' => '1周后自动下架的有效配置',
            ],
            [
                'spuIndex' => 4,
                'autoTakeDownTime' => $now->modify('-1 hour'),
                'isActive' => true,
                'description' => '已过期但仍有效的配置（待执行）',
            ],
            [
                'spuIndex' => 5,
                'autoTakeDownTime' => $now->modify('+2 days'),
                'isActive' => false,
                'description' => '已取消的自动下架配置',
            ],
            [
                'spuIndex' => 6,
                'autoTakeDownTime' => $now->modify('+3 days'),
                'isActive' => true,
                'description' => '长期有效的自动下架配置',
            ],
            [
                'spuIndex' => 7,
                'autoTakeDownTime' => $now->modify('-2 hours'),
                'isActive' => false,
                'description' => '已过期且已取消的配置',
            ],
        ];

        foreach ($configs as $index => $data) {
            $spu = $spus[$data['spuIndex']];

            $config = new AutoDownTimeConfig();
            $config->setSpu($spu);
            $config->setAutoTakeDownTime($data['autoTakeDownTime']);
            $config->setIsActive($data['isActive']);

            $manager->persist($config);
            $this->addReference('auto-down-config-' . $index, $config);
        }

        $manager->flush();
    }
}
