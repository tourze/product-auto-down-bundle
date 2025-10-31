<?php

declare(strict_types=1);

namespace Tourze\ProductAutoDownBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\ProductAutoDownBundle\ProductAutoDownBundle;

/**
 * @internal
 */
#[CoversClass(ProductAutoDownBundle::class)]
#[RunTestsInSeparateProcesses]
final class ProductAutoDownBundleTest extends AbstractBundleTestCase
{
}
