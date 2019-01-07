<?php

namespace RZP\Gateway\P2p\Upi\Sharp;

use RZP\Gateway\P2p\Upi;

class Gateway extends Upi\Gateway
{
    const RAZORSHARP    = 'razorsharp';
    const RZPSHARP      = 'rzpsharp';
    const NORZPSHARP    = 'norzpsharp';

    protected function shouldMockResponse(): bool
    {
        //All request for Sharp Gateway will have mocked response
        return true;
    }

    protected function shouldMockSuccessResponse(): bool
    {
        // All handles except of self::NORZPSHARP will result in success response
        return in_array($this->context->handleCode(), [self::RAZORSHARP, self::RZPSHARP], true);
    }
}
