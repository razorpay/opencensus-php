<?php

namespace RZP\Models\Merchant\Request;

use RZP\Base;
use RZP\Exception;
use RZP\Models\State;
use RZP\Models\Feature;

class Validator extends Base\Validator
{
    const INVALID_TYPE                  = 'Invalid request type';
    const INVALID_INPUT                 = 'Invalid input';
    const INVALID_FEATURE               = 'Invalid feature';
    const MISSING_SUBMISSIONS           = 'Missing submissions in request';
    const INVALID_STATUS_MESSAGE        = 'Invalid status';
    const INVALID_STATUS_CHANGE_MESSAGE = 'Invalid status change';

    protected static $createRules = [
        Entity::NAME        => 'required|string|max:40|custom',
        Entity::TYPE        => 'required|string|max:25|custom',
        Entity::STATUS      => 'required|max:30',
        Entity::MERCHANT_ID => 'required|string|size:14',
    ];

    // Required for the API which not only creates the entity but also form submissions if any.
    protected static $createMerchantRequestRules = [
        Entity::NAME           => 'required|string|max:40|custom',
        Entity::TYPE           => 'required|string|max:25|custom',
        Constants::SUBMISSIONS => 'sometimes|array',
    ];

    protected static $editRules = [
        Entity::STATUS           => 'sometimes|max:30',
        Entity::PUBLIC_MESSAGE   => 'sometimes|max:255',
        Entity::INTERNAL_COMMENT => 'sometimes|max:255',
    ];

    protected static $changeStatusRules = [
        Entity::STATUS                      => 'required|max:30',
        Constants::REJECTION_REASON         => 'sometimes|array',
        Constants::NEEDS_CLARIFICATION_TEXT => 'sometimes|string',
    ];

    protected static $updateRules = [
        Entity::STATUS                      => 'sometimes|max:30',
        Constants::SUBMISSIONS              => 'sometimes|array',
        Entity::PUBLIC_MESSAGE              => 'sometimes|max:255',
        Entity::INTERNAL_COMMENT            => 'sometimes|max:255',
        Constants::REJECTION_REASON         => 'filled|array',
        Constants::NEEDS_CLARIFICATION_TEXT => 'filled|string',
    ];

    public function validateStatus(array $input)
    {
        $validActivationStatuses = array_keys(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING);

        if (in_array($input[Entity::STATUS], $validActivationStatuses, true) === false)
        {
            $traceData = [
                'input_status' => $input[Entity::STATUS]
            ];

            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_STATUS_MESSAGE,
                Entity::STATUS,
                $traceData);
        }
    }

    public function validateName($attribute, $value)
    {
        if (in_array($value, array_keys(Feature\Constants::$featureValueMap)) === false)
        {
            $traceData = [
                'input_name' => $value
            ];

            throw new Exception\BadRequestValidationFailureException(self::INVALID_FEATURE, Entity::NAME, $traceData);
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

        $traceData = [
            'current_state' => $currentStatus,
            'new_state'     => $newStatus,
        ];

        if (isset(Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$newStatus]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_STATUS_MESSAGE,
                Entity::STATUS,
                $traceData);
        }

        if (in_array($newStatus, Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$currentStatus], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_STATUS_CHANGE_MESSAGE,
                Entity::STATUS,
                $traceData);
        }
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateType($attribute, $value)
    {
        if (empty($value) === true)
        {
            return;
        }

        if (defined(Type::class . '::' . strtoupper($value)) === false)
        {
            $traceData = [
                'input_type' => $value
            ];

            throw new Exception\BadRequestValidationFailureException(self::INVALID_TYPE, Entity::TYPE, $traceData);
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

        if (($type === Type::PRODUCT) and
            (in_array($name, $productFeatures, true) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                "Invalid product: $name for request type : $type");
        }
    }

    /**
     * Validate existence of submissions in case it is a Product type request
     *
     * @param array  $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSubmissionsForProductType(array $input)
    {
        if (($input[Entity::TYPE] === Type::PRODUCT) and
            (isset($input[Constants::SUBMISSIONS]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(self::MISSING_SUBMISSIONS);
        }
    }

    /**
     * @param array $merchantMap
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateBulkUpdateMerchantRequests(array $merchantMap)
    {
        if (is_array($merchantMap) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_INPUT, null, $merchantMap);
        }

        foreach ($merchantMap as $merchantId => $requests)
        {
            if (is_array($requests) === false)
            {
                throw new Exception\BadRequestValidationFailureException(self::INVALID_INPUT, null, $merchantMap);
            }

            foreach ($requests as $request)
            {
                if ((isset($request[Entity::TYPE]) === false) or
                    (isset($request[Entity::STATUS]) === false) or
                    (isset($request[Entity::NAME]) === false))
                {
                    throw new Exception\BadRequestValidationFailureException(self::INVALID_INPUT, null, $merchantMap);
                }
            }
        }
    }
}
