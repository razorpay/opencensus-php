<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;

class Action
{
    const ENABLE  = 'enable';
    const DISABLE = 'disable';

    public static function exists($action): bool
    {
        return defined(get_class() . '::' . strtoupper($action));
    }

    public static function validateInputAndGetAccountAction(string $action): string
    {
        $mapping = [
            self::ENABLE  => Merchant\Action::UNSUSPEND,
            self::DISABLE => Merchant\Action::SUSPEND,
        ];

        if (self::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCOUNT_ACTION_NOT_SUPPORTED);
        }

        return $mapping[$action];
    }
}
