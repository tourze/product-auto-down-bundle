<?php

namespace Tourze\ProductAutoDownBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @extends AbstractCrudController<AutoDownTimeConfig>
 */
#[AdminCrud(routePath: '/product/auto-down-config', routeName: 'product_auto_down_config')]
#[Autoconfigure(public: true)]
final class AutoDownTimeConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AutoDownTimeConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('自动下架配置')
            ->setEntityLabelInPlural('自动下架配置管理')
            ->setPageTitle('index', '自动下架配置列表')
            ->setPageTitle('new', '创建自动下架配置')
            ->setPageTitle('edit', '编辑自动下架配置')
            ->setPageTitle('detail', '自动下架配置详情')
            ->setHelp('index', '管理商品自动下架时间配置，支持设置、查看和取消自动下架')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['spu.title', 'spu.gtin'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->setMaxLength(9999);
        yield AssociationField::new('spu', 'SPU商品')
            ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
                return $queryBuilder
                    ->where('entity.valid = :valid')
                    ->setParameter('valid', true)
                ;
            })
            ->setFormTypeOption('choice_label', function (Spu $spu) {
                return sprintf('[%d] %s', $spu->getId(), $spu->getTitle());
            })
            ->formatValue(function (?Spu $value) {
                if ($value instanceof Spu) {
                    return sprintf('[%d] %s', $value->getId(), $value->getTitle());
                }

                return $value;
            })
        ;
        yield DateTimeField::new('autoTakeDownTime', '自动下架时间')
            ->setHelp('设置商品自动下架的具体时间')
        ;
        yield ChoiceField::new('isActive', '状态')
            ->setChoices([
                '有效' => '1',
                '已取消' => '0',
            ])
            ->formatValue(fn (?bool $value) => (true === $value) ? '有效' : '已取消')
        ;
        yield DateTimeField::new('createTime', '创建时间')->hideOnForm();
        yield DateTimeField::new('updateTime', '更新时间')->hideOnForm();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('spu', 'SPU商品'))
            ->add(ChoiceFilter::new('isActive', '状态')->setChoices([
                '有效' => '1',
                '已取消' => '0',
            ]))
        ;
    }
}
