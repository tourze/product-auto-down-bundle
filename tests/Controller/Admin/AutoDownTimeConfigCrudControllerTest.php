<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\ProductAutoDownBundle\Controller\Admin\AutoDownTimeConfigCrudController;

/**
 * @internal
 */
#[CoversClass(AutoDownTimeConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownTimeConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testControllerAccessRequiresAuthentication(): void
    {
        $client = self::createClientWithDatabase();

        // 测试未认证用户访问管理页面应该抛出访问拒绝异常
        $this->expectException(AccessDeniedException::class);
        $client->request('GET', '/admin');
    }

    public function testAuthorizedUserCanAccessController(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client, 'admin@test.com', 'password');

        $client->request('GET', '/admin');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful(), '管理员应该能够成功访问后台管理页面');
    }

    public function testConfigListPageRenders(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client, 'admin@test.com', 'password');

        // 测试配置列表页面
        $crawler = $client->request('GET', '/admin/product/auto-down-config');
        $response = $client->getResponse();

        // 验证页面加载成功（可能没有数据，但页面应该正常显示）
        $this->assertTrue($response->isSuccessful(), '管理员应该能够成功访问配置列表页面');

        // 验证页面包含管理相关内容
        $pageContent = $crawler->text();
        $this->assertTrue(
            str_contains($pageContent, '自动下架配置') || str_contains($pageContent, '管理') || str_contains($pageContent, 'Admin'),
            '页面应该包含管理相关内容'
        );
    }

    public function testNewConfigFormRenders(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client, 'admin@test.com', 'password');

        // 测试新建配置表单页面
        $crawler = $client->request('GET', '/admin/product/auto-down-config/new');
        $response = $client->getResponse();

        // 验证页面加载成功
        $this->assertTrue($response->isSuccessful(), '管理员应该能够成功访问新建配置表单页面');

        // 验证页面包含表单相关内容
        $pageContent = $crawler->text();
        $this->assertTrue(
            $crawler->filter('form')->count() > 0 || str_contains($pageContent, '创建') || str_contains($pageContent, '新建'),
            '页面应该包含表单或创建相关内容'
        );
    }

    protected function getControllerService(): AutoDownTimeConfigCrudController
    {
        $controller = self::getContainer()->get(AutoDownTimeConfigCrudController::class);
        self::assertInstanceOf(AutoDownTimeConfigCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列标题' => ['ID'];
        yield 'SPU商品列标题' => ['SPU商品'];
        yield '自动下架时间列标题' => ['自动下架时间'];
        yield '状态列标题' => ['状态'];
        yield '创建时间列标题' => ['创建时间'];
        yield '更新时间列标题' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'SPU商品字段' => ['spu'];
        yield '自动下架时间字段' => ['autoTakeDownTime'];
        yield '状态字段' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'SPU商品字段' => ['spu'];
        yield '自动下架时间字段' => ['autoTakeDownTime'];
        yield '状态字段' => ['isActive'];
    }
}
