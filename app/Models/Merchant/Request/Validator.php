<?php

namespace RZP\Models\Merchant\Request;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;

class Validator extends Base\Validator
{
    const INVALID_STATUS_MESSAGE        = 'Invalid status';
    const INVALID_STATUS_CHANGE_MESSAGE = 'Invalid status change';
    const INVALID_TYPE                  = 'Invalid request type';
    const MISSING_SUBMISSIONS           = 'Missing submissions in request';
    const INVALID_FEATURE               = 'Invalid feature';

    protected static $createRules = [
        Entity::NAME        => 'required|string|max:40|custom',
        Entity::TYPE        => 'required|string|max:25|custom',
        Entity::STATUS      => 'required|max:30',
        Entity::MERCHANT_ID => 'required|string|size:14',
    ];

    protected static $editRules = [
        Entity::STATUS           => 'sometimes|max:30',
        Entity::PUBLIC_MESSAGE   => 'sometimes|max:255',
        Entity::INTERNAL_COMMENT => 'sometimes|max:255',
    ];

    protected static $changeStatusRules = [
        Entity::STATUS            => 'required|max:30',
        Entity::REJECTION_REASONS => 'sometimes|array',
    ];

    protected static $updateRules = [
        Entity::STATUS            => 'required|max:30',
        Entity::SUBMISSIONS       => 'sometimes|array|custom',
        Entity::PUBLIC_MESSAGE    => 'sometimes|max:255',
        Entity::INTERNAL_COMMENT  => 'sometimes|max:255',
        Entity::REJECTION_REASONS => 'filled|array',
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

    /**
     * Validate the next possible status after current state.
     *
     * @param        $currentStatus
     * @param string $newStatus
     *
     * @throws Exception\BadRequestValidationFailureException
     */
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

    /**
     * Validate the name of feature being a product feature, if request type is Product
     *
     * @param $type
     * @param $name
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateTypeAndProduct($type, $name)
    {
        $productFeatures = Feature\Constants::PRODUCT_FEATURES;

        if (($type === Type::PRODUCT) and (in_array($name, $productFeatures, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid product: $name");
        }
    }

    /**
     * Validate existence of submissions in case it is a Product type request
     *
     * @param string $requestType
     * @param array  $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSubmissions(string $requestType, array $input)
    {
        if (($requestType === Type::PRODUCT) and (isset($input[Entity::SUBMISSIONS]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(self::MISSING_SUBMISSIONS);
        }
    }
}
