<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\ProductAutoDownBundle\Entity\AutoDownTimeConfig;
use Tourze\ProductCoreBundle\Entity\Spu;

/**
 * @internal
 */
#[CoversClass(AutoDownTimeConfig::class)]
final class AutoDownTimeConfigTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new AutoDownTimeConfig();
    }

    /**
     * @return array<array{string, mixed}>
     */
    public static function propertiesProvider(): array
    {
        $spu = new Spu();
        $time = new \DateTimeImmutable('2024-12-31 23:59:59');

        return [
            ['spu', $spu],
            ['autoTakeDownTime', $time],
            ['isActive', true],
        ];
    }

    public function testEntityInstantiationShouldCreateValidObject(): void
    {
        $config = new AutoDownTimeConfig();
        $this->assertInstanceOf(AutoDownTimeConfig::class, $config);
    }

    public function testSettersAndGettersWithValidDataShouldWorkCorrectly(): void
    {
        $config = new AutoDownTimeConfig();
        $spu = new Spu();
        $time = new \DateTimeImmutable('2024-12-31 23:59:59');

        $config->setSpu($spu);
        $config->setAutoTakeDownTime($time);
        $config->setIsActive(true);

        $this->assertSame($spu, $config->getSpu());
        $this->assertSame($time, $config->getAutoTakeDownTime());
        $this->assertTrue($config->getIsActive());
    }

    public function testDefaultStatusShouldBeActive(): void
    {
        $config = new AutoDownTimeConfig();
        $this->assertTrue($config->getIsActive());
    }

    public function testStatusCheckersWithDifferentStatesShouldReturnCorrectValues(): void
    {
        $config = new AutoDownTimeConfig();

        $this->assertTrue($config->isActive());
        $this->assertFalse($config->isCanceled());

        $config->markAsCanceled();
        $this->assertFalse($config->isActive());
        $this->assertTrue($config->isCanceled());
    }

    public function testToStringWithCompleteDataShouldReturnFormattedString(): void
    {
        $config = new AutoDownTimeConfig();
        $spu = new Spu();
        $spu->setTitle('Test SPU');
        $time = new \DateTimeImmutable('2024-12-31 23:59:59');

        $config->setSpu($spu);
        $config->setAutoTakeDownTime($time);

        $result = (string) $config;
        $this->assertStringContainsString('2024-12-31 23:59:59', $result);
    }

    public function testToStringWithoutDataShouldReturnDefaultString(): void
    {
        $config = new AutoDownTimeConfig();
        $result = (string) $config;
        $this->assertStringContainsString('SPU-0', $result);
    }
}
