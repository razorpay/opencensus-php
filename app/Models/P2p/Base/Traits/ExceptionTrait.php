<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Exception;
use RZP\Exception\P2p;

trait ExceptionTrait
{
    protected function badRequestException(string $subCode, array $data = [])
    {
        $exception = new P2p\BadRequestException($subCode, $data);

        return $exception;
    }

    protected function logicException(string $message, array $data = [])
    {
        $exception = new Exception\LogicException($message, null, $data);

        return $exception;
    }
}
