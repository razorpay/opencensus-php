<?php

namespace RZP\Exception\P2p;

use RZP\Error\P2p;

trait ErrorTrait
{
    protected function newError(string $code, array $data = [])
    {
        return new P2p\Error($code, null, null, $data);
    }
}
