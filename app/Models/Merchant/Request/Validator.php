<?php

namespace RZP\Models\Merchant\Request;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Feature;
use RZP\Models\Merchant\Partner;
use RZP\Error\PublicErrorDescription;

class Validator extends Base\Validator
{
    const INVALID_NAME                  = 'Invalid request name';
    const INVALID_TYPE                  = 'Invalid request type';
    const INVALID_INPUT                 = 'Invalid input';
    const MISSING_SUBMISSIONS           = 'Missing submissions in request';
    const INVALID_STATUS_MESSAGE        = 'Invalid status';
    const INVALID_STATUS_CHANGE_MESSAGE = 'Invalid status change';

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
        if (in_array($value, Constants::$names, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                self::INVALID_NAME,
                Entity::NAME,
                [
                    Entity::NAME => $value
                ]);
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

        if (($type === Type::PARTNER) and
            in_array($name, Partner\Constants::$partnerTypes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_TYPE,
                Partner\Entity::PARTNER_TYPE,
                [
                    $type
                ]);
        }
    }

    /**
     * Validates submissions
     *
     * @param array  $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSubmissions(array $input)
    {
        switch ($input[Entity::TYPE])
        {
            case Type::PRODUCT:
                if (isset($input[Constants::SUBMISSIONS]) === false)
                {
                    throw new Exception\BadRequestValidationFailureException(self::MISSING_SUBMISSIONS);
                }
                break;

            default:
                break;
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
