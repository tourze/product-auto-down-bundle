<?php

namespace Tourze\ProductAutoDownBundle\Exception;

class AutoDownServiceException extends \RuntimeException
{
    public static function spuNotFound(): self
    {
        return new self('配置关联的SPU不存在');
    }
}
