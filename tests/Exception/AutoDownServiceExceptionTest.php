<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\ProductAutoDownBundle\Exception\AutoDownServiceException;

/**
 * @internal
 */
#[CoversClass(AutoDownServiceException::class)]
final class AutoDownServiceExceptionTest extends AbstractExceptionTestCase
{
    public function testCanBeInstantiated(): void
    {
        $exception = new AutoDownServiceException('Test message');

        $this->assertInstanceOf(AutoDownServiceException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testSpuNotFoundFactoryMethod(): void
    {
        $exception = AutoDownServiceException::spuNotFound();

        $this->assertInstanceOf(AutoDownServiceException::class, $exception);
        $this->assertSame('配置关联的SPU不存在', $exception->getMessage());
    }

    public function testInheritsFromRuntimeException(): void
    {
        $exception = new AutoDownServiceException('Test');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testCustomMessage(): void
    {
        $customMessage = '自定义错误消息';
        $exception = new AutoDownServiceException($customMessage);

        $this->assertSame($customMessage, $exception->getMessage());
    }

    public function testExceptionCode(): void
    {
        $code = 500;
        $exception = new AutoDownServiceException('Test', $code);

        $this->assertSame($code, $exception->getCode());
    }

    public function testExceptionPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new AutoDownServiceException('Test', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testFactoryMethodAlwaysReturnsSameMessage(): void
    {
        $exception1 = AutoDownServiceException::spuNotFound();
        $exception2 = AutoDownServiceException::spuNotFound();

        $this->assertSame($exception1->getMessage(), $exception2->getMessage());
        // 但应该是不同的对象实例
        $this->assertNotSame($exception1, $exception2);
    }
}
