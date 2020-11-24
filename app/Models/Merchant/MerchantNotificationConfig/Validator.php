<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

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
