<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\ProductAutoDownBundle\Exception\SpuNotFoundException;

/**
 * @internal
 */
#[CoversClass(SpuNotFoundException::class)]
final class SpuNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testCanBeInstantiatedWithSpuId(): void
    {
        $spuId = 123;
        $exception = new SpuNotFoundException($spuId);

        $this->assertInstanceOf(SpuNotFoundException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame("SPU {$spuId} 不存在", $exception->getMessage());
    }

    public function testInheritsFromInvalidArgumentException(): void
    {
        $exception = new SpuNotFoundException(1);

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(\LogicException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testMessageContainsSpuId(): void
    {
        $spuId = 456;
        $exception = new SpuNotFoundException($spuId);

        $this->assertStringContainsString((string) $spuId, $exception->getMessage());
        $this->assertStringContainsString('SPU', $exception->getMessage());
        $this->assertStringContainsString('不存在', $exception->getMessage());
    }

    public function testDifferentSpuIdsCreateDifferentMessages(): void
    {
        $exception1 = new SpuNotFoundException(100);
        $exception2 = new SpuNotFoundException(200);

        $this->assertNotSame($exception1->getMessage(), $exception2->getMessage());
        $this->assertStringContainsString('100', $exception1->getMessage());
        $this->assertStringContainsString('200', $exception2->getMessage());
    }

    public function testZeroSpuId(): void
    {
        $exception = new SpuNotFoundException(0);

        $this->assertSame('SPU 0 不存在', $exception->getMessage());
    }

    public function testNegativeSpuId(): void
    {
        $exception = new SpuNotFoundException(-1);

        $this->assertSame('SPU -1 不存在', $exception->getMessage());
    }

    public function testLargeSpuId(): void
    {
        $largeId = 999999999;
        $exception = new SpuNotFoundException($largeId);

        $this->assertSame("SPU {$largeId} 不存在", $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $exception = new SpuNotFoundException(123);

        // 默认代码应该是0
        $this->assertSame(0, $exception->getCode());
    }

    public function testNoPreviousException(): void
    {
        $exception = new SpuNotFoundException(123);

        $this->assertNull($exception->getPrevious());
    }
}
