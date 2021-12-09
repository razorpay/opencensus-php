<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use App;
use libphonenumber\PhoneNumberType;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const NOTIFICATION_EMAILS_RULE         = 'notification_emails';
    const NOTIFICATION_MOBILE_NUMBERS_RULE = 'notification_mobile_numbers';

    protected static $createRules                 = [
        Entity::UPPER_THRESHOLD             => 'sometimes|required|integer|between:5,100000',
        Entity::LOWER_THRESHOLD             => 'sometimes|required|integer|min:5',
        Entity::MODE                        => 'sometimes|string|alpha_num|custom',
        Entity::NOTIFY_AFTER                => 'sometimes|integer|between:120,43200',
        Entity::NOTIFICATION_EMAILS         => 'sometimes|string|nullable|custom',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'sometimes|string|nullable|custom',
        Entity::NOTIFICATION_TYPE           => 'required|string|in:bene_bank_downtime,fund_loading_downtime',
    ];

    protected static $notificationEmailsRules     = [
        Entity::NOTIFICATION_EMAILS . '.*' => 'filled|email',
    ];

    protected static $editRules = [
        Entity::UPPER_THRESHOLD             => 'sometimes|integer|between:5,100000',
        Entity::LOWER_THRESHOLD             => 'sometimes|integer|min:5',
        Entity::NOTIFICATION_EMAILS         => 'sometimes|string|custom',
        Entity::NOTIFICATION_MOBILE_NUMBERS => 'sometimes|string|custom',
        Entity::NOTIFY_AFTER                => 'sometimes|integer|between:120,43200',
        Entity::MODE                        => 'sometimes|string|alpha_num|custom'
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
            $num = $lib->parse($number, 'IN');

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

    public static function validateMode($attribute, $value)
    {
        Mode::validateMode($value);
    }

    public static function checkThreshold(array $input, Entity $entity = null)
    {
        $errorCode = null;
        $data = null;

        if ((empty($input[Entity::LOWER_THRESHOLD]) === false) and
            (empty($input[Entity::UPPER_THRESHOLD]) === true) and
            (is_null($entity) === false) and
            ($entity->getUpperThreshold() < $input[Entity::LOWER_THRESHOLD]))
        {
            $data = [
                'existing_lower_threshold'     => $entity->getLowerThreshold(),
                'existing_upper_threshold'     => $entity->getUpperThreshold(),
                'new_lower_threshold_received' => $input[Entity::LOWER_THRESHOLD],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD;

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD,
                null,
                $data
            );
        }

        if ((empty($input[Entity::UPPER_THRESHOLD]) === false) and
            (empty($input[Entity::LOWER_THRESHOLD]) === true) and
            (is_null($entity) === false) and
            ($entity->getLowerThreshold() > $input[Entity::UPPER_THRESHOLD]))
        {
            $data = [
                'existing_upper_threshold'     => $entity->getUpperThreshold(),
                'existing_lower_threshold'     => $entity->getLowerThreshold(),
                'new_upper_threshold_received' => $input[Entity::UPPER_THRESHOLD],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_NEW_UPPER_THRESHOLD_LOWER_THAN_EXISTING_LOWER_THRESHOLD;
        }

        if ((empty($input[Entity::UPPER_THRESHOLD]) === false) and
            (empty($input[Entity::LOWER_THRESHOLD]) === false) and
            ($input[Entity::LOWER_THRESHOLD] > $input[Entity::UPPER_THRESHOLD]))
        {
            $data = [
                'new_upper_threshold_received' => $input[Entity::UPPER_THRESHOLD],
                'new_lower_threshold_received' => $input[Entity::LOWER_THRESHOLD],
            ];

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_LOWER_THRESHOLD_GREATER_THAN_UPPER_THRESHOLD;
        }

        if ((is_null($errorCode) === false) and
            (is_null($data) === false))
        {
            throw new BadRequestException(
                $errorCode,
                null,
                $data
            );
        }
    }
}
