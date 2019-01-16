<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\P2p\Base\Entity;
use RZP\Exception\BadRequestException;

trait HasContext
{

    public function verifyMerchantContext()
    {
        self::verifyContextCondition(
            ($this->merchant->getId() === app('p2p.ctx')->getMerchant()->getid()),
            'Entity does not belong merchant in context');
    }

    protected static function verifyContextCondition(bool $result, string $message)
    {
        if ($result === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED, null, null, [$message]);
        }
    }
}
