<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Upi;

class Gateway extends Upi\Gateway
{
    const RAZORSHARP    = 'razorsharp';
    const RZPSHARP      = 'rzpsharp';
    const NORZPSHARP    = 'norzpsharp';

    protected function makeMockedResponse()
    {
        $response = $this->makeResponse();

        $response->setMock(true);

        if ($this->context->handleId() === self::NORZPSHARP)
        {
            $response->setError(...$this->getMockedError());
        }

        return $response;
    }

    protected function getMockedError()
    {
        return [
            'BAD_REQUEST_ERROR',
            'Bad request error',
        ];
    }
}
