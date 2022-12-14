<?php


namespace RZP\Models\BankingAccount\Activation\Comment;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Permission;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BANKING_ACCOUNT_ID  => 'required|string|size:14',
        Entity::ADMIN_ID            => 'required_without:user_id|string|size:14',
        Entity::USER_ID             => 'required_without:admin_id|string|size:14',
        Entity::COMMENT             => 'required|filled|string',
        Entity::NOTES               => 'sometimes|array|max:3',
        Entity::SOURCE_TEAM_TYPE    => 'required|max:255|in:internal,external',
        Entity::SOURCE_TEAM         => 'required|max:255|in:product,sales,ops,bank,ops_mx_poc',
        Entity::TYPE                => 'required|max:64|in:internal,external',
        Entity::ADDED_AT            => 'required|epoch'
    ];

    protected static $editRules = [
        Entity::COMMENT             => 'sometimes|string',
        Entity::NOTES               => 'sometimes|array|max:3',
        Entity::SOURCE_TEAM_TYPE    => 'sometimes|max:255|in:internal,external',
        Entity::SOURCE_TEAM         => 'sometimes|max:255|in:product,sales,ops,bank,ops_mx_poc',
        Entity::TYPE                => 'sometimes|max:64|in:internal,external,external_resolved',
        Entity::ADDED_AT            => 'sometimes|epoch'
    ];
}
