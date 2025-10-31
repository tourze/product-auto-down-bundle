<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Service;

use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;

#[Autoconfigure(public: true)]
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('商品管理')) {
            $item->addChild('商品管理');
        }

        $productMenu = $item->getChild('商品管理');

        if (null === $productMenu) {
            return;
        }

        $productMenu
            ->addChild('自动下架配置')
            ->setUri($this->linkGenerator->getCurdListPage(AutoDownTimeConfig::class))
            ->setAttribute('icon', 'fas fa-clock')
        ;

        $productMenu
            ->addChild('自动下架日志')
            ->setUri($this->linkGenerator->getCurdListPage(AutoDownLog::class))
            ->setAttribute('icon', 'fas fa-list-alt')
        ;
    }
}
