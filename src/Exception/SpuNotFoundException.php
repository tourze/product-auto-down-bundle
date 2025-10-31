<?php

namespace Tourze\ProductAutoDownBundle\Exception;

class SpuNotFoundException extends \InvalidArgumentException
{
    public function __construct(int $spuId)
    {
        parent::__construct("SPU {$spuId} 不存在");
    }
}
