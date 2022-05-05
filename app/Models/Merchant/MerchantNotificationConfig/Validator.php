<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use App;
use libphonenumber\PhoneNumberType;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const NOTIFICATION_EMAILS_RULE         = 'notification_emails';
    const NOTIFICATION_MOBILE_NUMBERS_RULE = 'notification_mobile_numbers';

    protected static $createRules                 = [
        Entity::NOTIFICATION_EMAILS         => 'sometimes|string|nullable|custom',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'sometimes|string|nullable|custom',
        Entity::NOTIFICATION_TYPE           => 'required|string|custom',
    ];

    protected static $notificationEmailsRules     = [
        Entity::NOTIFICATION_EMAILS . '.*' => 'filled|email',
    ];

    protected static $editRules = [
        Entity::NOTIFICATION_EMAILS         => 'sometimes|string|custom',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'sometimes|string|custom',
    ];

    public static function validateNotificationEmails($attribute, $value)
    {
        $value = [Entity::NOTIFICATION_EMAILS => explode(',', $value)];
        (new Validator())->setStrictFalse()->validateInput(self::NOTIFICATION_EMAILS_RULE, $value);
    }

    // This logic only supports Indian numbers
    public static function validateNotificationMobileNumbers($attribute, $value)
    {
        $value = explode(',', $value);

        $lib = App::getFacadeRoot()['libphonenumber'];

        $invalidMobileNumbers = [];

        foreach($value as $number)
        {
            try
            {
                $num = $lib->parse($number, 'IN');
            }
            catch (\Throwable $ex)
            {
                $invalidMobileNumbers[] = $number;
                continue;
            }

            // - Check if number is valid in India, and if the number is a mobile number
            // - Since libphonenumber returns type FIXED_OR_MOBILE for 6xx, 7xx or 8xx mobile numbers
            //   we need to check for both the aforementioned types.
            if (($lib->isValidNumberForRegion($num, 'IN') === false) or
                (($lib->getNumberType($num) !== PhoneNumberType::MOBILE) and
                    ($lib->getNumberType($num) !== PhoneNumberType::FIXED_LINE_OR_MOBILE)
                )
            )
            {
                $invalidMobileNumbers[] = $number;
            }
        }

        if(empty($invalidMobileNumbers) === false)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MOBILE_NUMBER,
                null,
                [
                    'invalid_mobile_numbers' => $invalidMobileNumbers,
                ]);
        }
    }

    public static function validateNotificationType($attribute, $value)
    {
        if (in_array($value, NotificationType::getNotificationTypes()) === false)
        {
            throw new BadRequestValidationFailureException(
                'INVALID_NOTIFICATION_TYPE',
                null,
                [
                    'notification_type' => $value
                ]
            );
        }
    }
}
