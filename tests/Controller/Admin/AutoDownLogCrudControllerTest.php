<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;
use Tourze\ProductAutoDownBundle\Controller\Admin\AutoDownLogCrudController;

/**
 * @internal
 */
#[CoversClass(AutoDownLogCrudController::class)]
#[RunTestsInSeparateProcesses]
final class AutoDownLogCrudControllerTest extends AbstractEasyAdminControllerTestCase
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

    public function testLogListPageRenders(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsAdmin($client, 'admin@test.com', 'password');

        // 测试日志列表页面
        $crawler = $client->request('GET', '/admin/product/auto-down-log');
        $response = $client->getResponse();

        // 验证页面加载成功（可能没有数据，但页面应该正常显示）
        $this->assertTrue($response->isSuccessful(), '管理员应该能够成功访问日志列表页面');

        // 验证页面包含管理相关内容
        $pageContent = $crawler->text();
        $this->assertTrue(
            str_contains($pageContent, '自动下架日志') || str_contains($pageContent, '管理') || str_contains($pageContent, 'Admin'),
            '页面应该包含管理相关内容'
        );
    }

    protected function getControllerService(): AutoDownLogCrudController
    {
        $controller = self::getContainer()->get(AutoDownLogCrudController::class);
        self::assertInstanceOf(AutoDownLogCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID列标题' => ['ID'];
        yield 'SPU ID列标题' => ['SPU ID'];
        yield '配置列标题' => ['配置'];
        yield '操作动作列标题' => ['操作动作'];
        yield '创建时间列标题' => ['创建时间'];
    }

    /**
     * 日志记录为只读，无NEW页面字段，但需要提供至少一个数据避免空数据集错误
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        // 此控制器禁用了NEW操作，但需要提供一个虚拟字段避免空数据集
        yield '虚拟字段(禁用NEW操作)' => ['id'];
    }

    /**
     * 基类会自动检测NEW动作是否启用，如果禁用则跳过测试
     * 此控制器禁用了NEW操作，测试将被自动跳过
     */

    /**
     * 日志记录为只读，无EDIT页面字段，但需要提供至少一个数据避免空数据集错误
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 此控制器禁用了EDIT操作，但需要提供一个虚拟字段避免空数据集
        yield '虚拟字段(禁用EDIT操作)' => ['id'];
    }
}
