<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownLog;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductAutoDownBundle\Enum\AutoDownLogAction;

/**
 * @internal
 */
#[CoversClass(AutoDownLog::class)]
final class AutoDownLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AutoDownLog();
    }

    /**
     * @return array<array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        $config = new AutoDownTimeConfig();
        $context = ['key' => 'value', 'type' => 'test'];

        return [
            ['spuId', 123],
            ['config', $config],
            ['action', AutoDownLogAction::EXECUTED],
            ['description', 'Test description for log entry'],
            ['context', $context],
        ];
    }

    public function testEntityInstantiationShouldCreateValidObject(): void
    {
        $log = new AutoDownLog();
        $this->assertInstanceOf(AutoDownLog::class, $log);
    }

    public function testSettersAndGettersWithValidDataShouldWorkCorrectly(): void
    {
        $log = new AutoDownLog();
        $config = new AutoDownTimeConfig();
        $context = ['key' => 'value'];

        $log->setSpuId(123);
        $log->setConfig($config);
        $log->setAction(AutoDownLogAction::EXECUTED);
        $log->setDescription('Test description');
        $log->setContext($context);

        $this->assertSame(123, $log->getSpuId());
        $this->assertSame($config, $log->getConfig());
        $this->assertSame(AutoDownLogAction::EXECUTED, $log->getAction());
        $this->assertSame('Test description', $log->getDescription());
        $this->assertSame($context, $log->getContext());
    }

    public function testToStringWithCompleteDataShouldReturnFormattedString(): void
    {
        $log = new AutoDownLog();
        $log->setSpuId(123);
        $log->setAction(AutoDownLogAction::EXECUTED);

        $result = (string) $log;
        $this->assertStringContainsString('SPU-123', $result);
        $this->assertStringContainsString('已执行', $result);
    }

    public function testToStringWithoutDataShouldReturnDefaultString(): void
    {
        $log = new AutoDownLog();
        $result = (string) $log;
        $this->assertStringContainsString('SPU-0', $result);
    }
}
