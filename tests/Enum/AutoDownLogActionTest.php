<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

/**
 * @internal
 */
#[CoversClass(AutoDownLogAction::class)]
final class AutoDownLogActionTest extends AbstractEnumTestCase
{
    public function testEnumCasesShouldContainAllExpectedValues(): void
    {
        $cases = AutoDownLogAction::cases();
        $this->assertCount(5, $cases);
        $this->assertContains(AutoDownLogAction::SCHEDULED, $cases);
        $this->assertContains(AutoDownLogAction::EXECUTED, $cases);
        $this->assertContains(AutoDownLogAction::SKIPPED, $cases);
        $this->assertContains(AutoDownLogAction::ERROR, $cases);
        $this->assertContains(AutoDownLogAction::CANCELED, $cases);
    }

    #[DataProvider('labelDataProvider')]
    public function testGetLabelWithDifferentActionsShouldReturnCorrectLabels(AutoDownLogAction $action, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $action->getLabel());
    }

    /**
     * @return array<string, array{AutoDownLogAction, string}>
     */
    public static function labelDataProvider(): array
    {
        return [
            'scheduled' => [AutoDownLogAction::SCHEDULED, '已安排'],
            'executed' => [AutoDownLogAction::EXECUTED, '已执行'],
            'skipped' => [AutoDownLogAction::SKIPPED, '已跳过'],
            'error' => [AutoDownLogAction::ERROR, '执行出错'],
            'canceled' => [AutoDownLogAction::CANCELED, '已取消'],
        ];
    }

    public function testGetItemWithScheduledActionShouldReturnArrayFormat(): void
    {
        $item = AutoDownLogAction::SCHEDULED->getItem();
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('label', $item);
        $this->assertSame('scheduled', $item['value']);
        $this->assertSame('已安排', $item['label']);
    }

    public function testGetItemsShouldReturnAllActionsInArrayFormat(): void
    {
        $items = AutoDownLogAction::getItems();
        $this->assertArrayHasKey('scheduled', $items);
        $this->assertArrayHasKey('executed', $items);
        $this->assertArrayHasKey('skipped', $items);
        $this->assertArrayHasKey('error', $items);
        $this->assertArrayHasKey('canceled', $items);
    }

    /**
     * @param array{value: string, label: string} $expectedArray
     */
    #[DataProvider('toArrayDataProvider')]
    public function testToArrayWithDifferentActionsShouldReturnCorrectFormat(AutoDownLogAction $action, array $expectedArray): void
    {
        $result = $action->toArray();
        $this->assertSame($expectedArray, $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
    }

    /**
     * @return array<string, array{AutoDownLogAction, array{value: string, label: string}}>
     */
    public static function toArrayDataProvider(): array
    {
        return [
            'scheduled' => [AutoDownLogAction::SCHEDULED, ['value' => 'scheduled', 'label' => '已安排']],
            'executed' => [AutoDownLogAction::EXECUTED, ['value' => 'executed', 'label' => '已执行']],
            'skipped' => [AutoDownLogAction::SKIPPED, ['value' => 'skipped', 'label' => '已跳过']],
            'error' => [AutoDownLogAction::ERROR, ['value' => 'error', 'label' => '执行出错']],
            'canceled' => [AutoDownLogAction::CANCELED, ['value' => 'canceled', 'label' => '已取消']],
        ];
    }
}
