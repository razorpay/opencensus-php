<?php


namespace RZP\Models\BankingAccount\Activation\CallLog;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Permission;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BANKING_ACCOUNT_ID      => 'required|string|size:14',
        Entity::ADMIN_ID                => 'required|string|size:14',
        Entity::COMMENT_ID              => 'sometimes|string|size:14',
        Entity::STATE_LOG_ID            => 'required|string|size:14',
        Entity::DATE_AND_TIME           => 'required|epoch',
        Entity::FOLLOW_UP_DATE_AND_TIME => 'sometimes|epoch',
    ];

    protected static $editRules = [
        Entity::DATE_AND_TIME           => 'sometimes|string|size:14',
        Entity::FOLLOW_UP_DATE_AND_TIME => 'sometimes|string|size:14',
    ];
}
