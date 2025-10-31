<?php

namespace Tourze\ProductAutoDownBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum AutoDownLogAction: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case SCHEDULED = 'scheduled';
    case EXECUTED = 'executed';
    case SKIPPED = 'skipped';
    case ERROR = 'error';
    case CANCELED = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::SCHEDULED => '已安排',
            self::EXECUTED => '已执行',
            self::SKIPPED => '已跳过',
            self::ERROR => '执行出错',
            self::CANCELED => '已取消',
        };
    }

    /**
     * @return array{value: string, label: string}
     */
    public function getItem(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->getLabel(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getItems(): array
    {
        $items = [];
        foreach (self::cases() as $case) {
            $items[$case->value] = $case->getLabel();
        }

        return $items;
    }

    /**
     * @return array{value: string, label: string}
     */
    public function toSelectItem(): array
    {
        return $this->getItem();
    }
}
