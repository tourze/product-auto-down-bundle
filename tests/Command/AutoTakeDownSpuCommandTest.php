<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\ProductAutoDownBundle\Command\AutoTakeDownSpuCommand;

/**
 * @internal
 */
#[CoversClass(AutoTakeDownSpuCommand::class)]
#[RunTestsInSeparateProcesses]
final class AutoTakeDownSpuCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getContainer()->get(AutoTakeDownSpuCommand::class);
        self::assertInstanceOf(AutoTakeDownSpuCommand::class, $command);

        return new CommandTester($command);
    }

    public function testCommandInstantiationShouldCreateValidObject(): void
    {
        $command = self::getContainer()->get(AutoTakeDownSpuCommand::class);
        $this->assertInstanceOf(AutoTakeDownSpuCommand::class, $command);
    }

    public function testExecuteCommandWithDefaultOptionsShouldReturnSuccessStatus(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertSame(0, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('本次执行下架了', $output);
        $this->assertStringContainsString('个SPU', $output);
    }
}
