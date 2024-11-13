<?php

namespace RZP\Models\Admin\AdminsMeta;

use Carbon\Carbon;
use RZP\Models\Admin\Base;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Admin\Entity as AdminsEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{

    protected static array $orgAdminCreateRules = [
        Entity::AUTH_MODE                         => 'required|string|in:adfs',
        Entity::UNIQUE_IDENTIFIER                 => 'required_if:auth_mode,adfs',
        Constant::FULL_NAME                       => 'required|regex:/^(?=.*[a-zA-Z])(?=.*\s)[a-zA-Z0-9\s]+$/|between:3,100',
        AdminsEntity::EMAIL                       => 'required|email|max:255',
        AdminsEntity::USERNAME                    => 'sometimes|string|between:3,50',
        Constant::USER_ROLES                      => 'required|array|filled',
        Constant::EXPIRE_AT                       => 'required|epoch|custom'
    ];

    protected static array $createAdminsMetaRules = [
        Entity::AUTH_MODE                         => 'required|string|in:adfs',
        Entity::UNIQUE_IDENTIFIER                 => 'required_if:auth_mode,adfs|max:14',
        Entity::ADMIN_ID                          => 'required|string'
    ];

    protected static array $getMultipleAdminsRules = [
        Constant::START_DATE => 'required|epoch',
        Constant::END_DATE   => 'required|epoch'
    ];

    protected static array $getUpdateAdminsRules = [
        Constant::FULL_NAME  => 'sometimes|regex:/^(?=.*[a-zA-Z])(?=.*\s)[a-zA-Z0-9\s]+$/|between:3,100',
        Constant::EXPIRE_AT  => 'sometimes|epoch|custom',
        Constant::USER_ROLES => 'sometimes|array',
        Constant::ACCOUNT_STATUS => 'sometimes'
    ];

    public $isOrgSpecificValidationSupported = true;

    /**
     * Validate the expire_at attribute
     *
     * @throws BadRequestValidationFailureException
     */
    public function validateExpireAt(string $attribute, int $expireAt): bool
    {
        $now = Carbon::now(Timezone::IST);

        if ($expireAt < $now->getTimestamp()) {
            $message = 'expire_at should be in future';
            throw new BadRequestValidationFailureException($message, $attribute);
        }

        return true;
    }

}
