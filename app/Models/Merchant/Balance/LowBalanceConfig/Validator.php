<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Validator as MerchantValidator;

class Validator extends Base\Validator
{
    const NOTIFICATION_EMAILS_RULE = 'notification_emails';

    protected static $createRules = [
        // in input account_number is sent which is converted into balance_id in the code
        // validation for account number (currently only merchants with business banking enabled are
        // allowed)
        Entity::BALANCE_ID          => 'required|size:14',
        Entity::THRESHOLD_AMOUNT    => 'required|integer|min:0',
        Entity::NOTIFY_AFTER        => 'sometimes|integer|min:0',
        Entity::NOTIFICATION_EMAILS => 'sometimes|string',
        Entity::TYPE                => 'sometimes|string|custom',
        Entity::AUTOLOAD_AMOUNT     => 'sometimes|integer|min:100|custom',
    ];

    protected static $notificationEmailsRules = [
        Entity::NOTIFICATION_EMAILS        => 'sometimes|array',
        Entity::NOTIFICATION_EMAILS . '.*' => 'filled|email',
    ];

    protected static $editRules = [
        Entity::THRESHOLD_AMOUNT    => 'sometimes|integer|min:0',
        Entity::NOTIFY_AFTER        => 'sometimes|integer|min:0',
        Entity::NOTIFICATION_EMAILS => 'sometimes|string',
        Entity::AUTOLOAD_AMOUNT     => 'sometimes|integer|min:100|custom',
    ];

    public static function validateNotificationEmailRules(array & $input)
    {
        (new Validator())->setStrictFalse()->validateInput(self::NOTIFICATION_EMAILS_RULE, $input);

        if (empty($input[Entity::NOTIFICATION_EMAILS]) === false)
        {
            //converting to string for validation that is in build/edit (create/edit Rules)
            $input[Entity::NOTIFICATION_EMAILS] = implode(',', $input[Entity::NOTIFICATION_EMAILS]);
        }
    }

    public static function validateAndTranslateAccountNumberForBanking(array & $input, MerchantEntity $merchant)
    {
        /** @var MerchantValidator $merchantValidator */
        $merchantValidator = $merchant->getValidator();

        $merchantValidator->validateAndTranslateAccountNumberForBanking($input);
    }

    public function validateType($attribute, $value)
    {
        if ((new Service)->isAdminAuth() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_AUTH_NOT_SUPPORTED,
                null,
                [
                    'type'  => $value
                ]);
        }

        if (in_array($value, Entity::ALLOWED_CONFIG_TYPES, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_INVALID_TYPE,
                null,
                [
                    'type'  => $value
                ]);
        }
    }

    public function validateAutoloadAmount($attribute, $value)
    {
        if ((new Service)->isAdminAuth() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_LOW_BALANCE_CONFIG_AUTH_NOT_SUPPORTED,
                null,
                [
                    'autoload_amount'   => $value
                ]);
        }
    }
}
