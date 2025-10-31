<?php

namespace Tourze\ProductAutoDownBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

/**
 * @extends AbstractCrudController<AutoDownLog>
 */
#[AdminCrud(routePath: '/product/auto-down-log', routeName: 'product_auto_down_log')]
#[Autoconfigure(public: true)]
final class AutoDownLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AutoDownLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('自动下架日志')
            ->setEntityLabelInPlural('自动下架日志管理')
            ->setPageTitle('index', '自动下架日志列表')
            ->setPageTitle('detail', '自动下架日志详情')
            ->setHelp('index', '查看商品自动下架的执行日志，包括成功、失败和跳过的记录')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['spuId', 'description'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');
        yield IntegerField::new('spuId', 'SPU ID');
        yield AssociationField::new('config', '配置')
            ->formatValue(function (?AutoDownTimeConfig $value) {
                if (null !== $value) {
                    return sprintf('配置-%d', $value->getId());
                }

                return null;
            })
        ;
        yield ChoiceField::new('action', '操作动作')
            ->setFormType(EnumType::class)
            ->setFormTypeOptions([
                'class' => AutoDownLogAction::class,
                'choice_label' => fn (AutoDownLogAction $choice) => $choice->getLabel(),
            ])
            ->formatValue(fn (?AutoDownLogAction $value) => $value?->getLabel())
        ;
        yield TextareaField::new('description', '描述信息')
            ->hideOnIndex()
            ->setMaxLength(500)
        ;
        yield CodeEditorField::new('context', '上下文信息')
            ->setLanguage('javascript')
            ->hideOnIndex()
        ;
        yield DateTimeField::new('createTime', '创建时间');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(NumericFilter::new('spuId', 'SPU ID'))
            ->add(EntityFilter::new('config', '配置'))
            ->add(ChoiceFilter::new('action', '操作动作')->setChoices([
                '已安排' => AutoDownLogAction::SCHEDULED,
                '已执行' => AutoDownLogAction::EXECUTED,
                '已跳过' => AutoDownLogAction::SKIPPED,
                '执行出错' => AutoDownLogAction::ERROR,
                '已取消' => AutoDownLogAction::CANCELED,
            ]))
        ;
    }
}
