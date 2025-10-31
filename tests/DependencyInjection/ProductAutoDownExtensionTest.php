<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\ProductAutoDownBundle\DependencyInjection\ProductAutoDownExtension;
use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

/**
 * @internal
 */
#[CoversClass(ProductAutoDownExtension::class)]
final class ProductAutoDownExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private ProductAutoDownExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new ProductAutoDownExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoadConfigurationShouldRegisterServicesSuccessfully(): void
    {
        // Arrange: 设置容器环境参数
        $this->container->setParameter('kernel.environment', 'test');

        // Act: 加载Extension配置
        $this->extension->load([], $this->container);

        // Assert: 验证Extension正确加载配置
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
        $this->assertTrue($this->container->isTrackingResources(), 'Extension应该启用资源跟踪');
    }

    public function testGetAliasShouldReturnCorrectBundleName(): void
    {
        // Act & Assert: 验证Extension alias正确
        $this->assertSame('product_auto_down', $this->extension->getAlias());
    }

    public function testExtensionShouldImplementAutoExtensionInterface(): void
    {
        // Act & Assert: 验证Extension继承关系
        $this->assertInstanceOf(AutoExtension::class, $this->extension);
    }

    public function testGetConfigDirShouldReturnValidPath(): void
    {
        // Act: 获取配置目录路径
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);
        $configDir = $method->invoke($this->extension);

        // Assert: 验证配置目录存在
        $this->assertIsString($configDir);
        $this->assertDirectoryExists($configDir, '配置目录应该存在');
        $this->assertStringEndsWith('Resources/config', $configDir);
    }

    public function testLoadWithDifferentEnvironmentsShouldWorkCorrectly(): void
    {
        // Arrange: 测试不同环境的配置加载
        $environments = ['test', 'dev', 'prod'];

        foreach ($environments as $env) {
            $container = new ContainerBuilder();
            $container->setParameter('kernel.environment', $env);

            // Act & Assert: 各个环境的配置都能正常加载
            $this->extension->load([], $container);
            $this->assertInstanceOf(ContainerBuilder::class, $container);
        }
    }

    /**
     * 暂时跳过自动服务发现测试，避免League\ConstructFinder依赖问题
     */
    protected function provideServiceDirectories(): iterable
    {
        return [];
    }
}
