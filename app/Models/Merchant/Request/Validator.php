<?php

namespace RZP\Models\Merchant\Request;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;

class Validator extends Base\Validator
{
    const INVALID_STATUS_MESSAGE                        = 'Invalid status';
    const INVALID_STATUS_CHANGE_MESSAGE                 = 'Invalid status change';
    const INVALID_TYPE                                  = 'Invalid request type';
    const MISSING_QUESTIONS                             = 'Missing Questions in request';
    const INVALID_FEATURE                               = 'Invalid feature';

    protected static $createRules = [
        Entity::TYPE        => 'required|alpha_space|max:255|custom',
        Entity::STATUS      => 'required|max:30',
        Entity::NAME        => 'required|string|max:15|custom',
        Entity::MERCHANT_ID => 'required|string|max:15',
    ];

    protected static $createRequestRules = [
        Entity::SUBMISSIONS => 'sometimes|array',
        Entity::TYPE        => 'required|alpha_space|max:255|custom',
        Entity::STATUS      => 'sometimes|max:30',
        Entity::NAME        => 'required|string|max:15|custom',
    ];

    protected static $editRules = [
        Entity::STATUS         => 'sometimes|max:255',
        Entity::PUBLIC_MESSAGE => 'sometimes|max:255',
        Entity::COMMENT        => 'sometimes|max:255',
    ];

    protected static $changeStatusRules = [
        Entity::STATUS            => 'required|max:30',
        Entity::REJECTION_REASONS => 'filled|array',
    ];

    protected static $updateRules = [
        Entity::SUBMISSIONS    => 'sometimes|array|custom',
        Entity::STATUS         => 'required|max:30',
        Entity::PUBLIC_MESSAGE => 'sometimes|max:255',
        Entity::COMMENT        => 'sometimes|max:255',
    ];

    public function validateStatus(array $input)
    {
        $validActivationStatuses = array_keys(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING);

        if (in_array($input[Entity::STATUS], $validActivationStatuses, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_MESSAGE);
        }
    }

    public function validateName($attribute, $value)
    {
        if (in_array($value, array_keys(Feature\Constants::$featureValueMap)) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_FEATURE);
        }
    }

    public function validateActivationStatusChange($currentStatus, string $newStatus)
    {
        if (empty($currentStatus) === true)
        {
            return;
        }

        if (isset(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$newStatus]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_CHANGE_MESSAGE);
        }

        if (in_array($newStatus, Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$currentStatus], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_CHANGE_MESSAGE);
        }
    }

    public function validateType($attribute, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        if (defined(Type::class . '::' . strtoupper($value)) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_TYPE);
        }
    }

    public function validateProduct($type, $name)
    {
        $productFeatures = Feature\Constants::PRODUCT_FEATURES;

        if ($type === Type::PRODUCT and in_array($name, $productFeatures, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid product: $name");
        }
    }

    public function validateQuestions(string $requestType, array $input)
    {
        if ($requestType === Type::PRODUCT and isset($input[Entity::SUBMISSIONS]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::MISSING_QUESTIONS);
        }
    }
}
