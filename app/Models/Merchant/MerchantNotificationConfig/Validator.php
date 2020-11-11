<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Base;

class Validator extends Base\Validator
{
    const NOTIFICATION_EMAILS_RULE         = 'notification_emails';
    const NOTIFICATION_MOBILE_NUMBERS_RULE = 'notification_mobile_numbers';

    protected static $createRules                 = [
        Entity::UPPER_THRESHOLD             => 'required|integer|between:5,100000',
        Entity::LOWER_THRESHOLD             => 'required|integer|min:5',
        Entity::MODE                        => 'sometimes|string',
        Entity::NOTIFY_AFTER                => 'sometimes|integer|between:120,43200',
        Entity::NOTIFICATION_EMAILS         => 'required|string',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'required|string',
    ];

    protected static $notificationEmailsRules     = [
        Entity::NOTIFICATION_EMAILS        => 'required|array',
        Entity::NOTIFICATION_EMAILS . '.*' => 'filled|email',
    ];

    protected static $notificationMobileNumbersRules = [
        Entity::NOTIFICATION_MOBILE_NUMBERS        => 'required|array',
        // TODO: Use a good validator for mobile numbers in phase 2
        Entity::NOTIFICATION_MOBILE_NUMBERS . '.*' => 'filled|numeric|digits:10',
    ];

    protected static $editRules = [
        Entity::UPPER_THRESHOLD             => 'sometimes|integer|between:5,100000',
        Entity::LOWER_THRESHOLD             => 'sometimes|integer|min:5',
        Entity::NOTIFY_AFTER                => 'sometimes|integer|between:120,43200',
        Entity::NOTIFICATION_EMAILS         => 'sometimes|string',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'sometimes|string',
    ];

    public static function validateNotificationEmailRules(array &$input)
    {
        (new Validator())->setStrictFalse()->validateInput(self::NOTIFICATION_EMAILS_RULE, $input);

        //converting to string for validation that is in build/edit (create/edit Rules)
        $input[Entity::NOTIFICATION_EMAILS] = implode(',', $input[Entity::NOTIFICATION_EMAILS]);
    }

    public static function validateNotificationMobileNumbersRules(array &$input)
    {
        (new Validator())->setStrictFalse()->validateInput(self::NOTIFICATION_MOBILE_NUMBERS_RULE, $input);

        //converting to string for validation that is in build/edit (create/edit Rules)
        $input[Entity::NOTIFICATION_MOBILE_NUMBERS] = implode(',', $input[Entity::NOTIFICATION_MOBILE_NUMBERS]);
    }

    public static function validateMode(array &$input)
    {
        Mode::validateMode($input['mode']);
    }
}
