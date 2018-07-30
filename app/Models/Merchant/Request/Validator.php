<?php

namespace RZP\Models\Merchant\Request;

use App;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant;
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
        Entity::NAME        => 'required|string|max:40',
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
        if (defined(Type::class . '::' . strtoupper($value)) === false)
        {
            $traceData = [
                'input_type' => $value
            ];

            throw new Exception\BadRequestValidationFailureException(self::INVALID_TYPE, Entity::TYPE, $traceData);
        }
    }

    /**
     * Handle the validations before creating a new merchant request
     *
     * @param array $input
     */
    public function validateCreateMerchantRequest(array $input)
    {
        $submissions = $input[Constants::SUBMISSIONS] ?? [];

        unset($input[Constants::SUBMISSIONS]);

        $this->validateInput('create', $input);

        $type = $input[Entity::TYPE];

        $name = $input[Entity::NAME];

        $this->validateName($type, $name);

        $this->validateAdminAccessIfPartnerRequest($type, $name);

        $this->validateSubmissions($input, $submissions);
    }

    /**
     * Validate the name based on the type attribute
     *
     * @param $type
     * @param $name
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateName(string $type, string $name)
    {
        if (in_array($name, Constants::$typeNamesMap[$type], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_MERCHANT_REQUEST_INVALID_NAME,
                Entity::NAME,
                [
                    Entity::NAME                  => $name,
                    Merchant\Entity::PARTNER_TYPE => $type,
                ]);
        }
    }

    /**
     * Do not allow the merchants to raise requests for marking and unmarking themselves as partners.
     * The same route can be used to submit the product activation requests.
     *
     * @param string $type
     * @param string $name
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateAdminAccessIfPartnerRequest(string $type, string $name)
    {
        $isAdminAuth = app('basicauth')->isAdminAuth();

        if (($type === Type::PARTNER) and ($isAdminAuth === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                null,
                [
                    Entity::NAME                  => $name,
                    Merchant\Entity::PARTNER_TYPE => $type,
                ]);
        }
    }

    /**
     * Validates submissions based on the merchant request type
     *
     * @param array  $input
     * @param array  $submissions
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateSubmissions(array $input, array $submissions)
    {
        $type = $input[Entity::TYPE];

        $name = $input[Entity::NAME];

        switch (true)
        {
            case ($type === Type::PRODUCT):
                if (empty($submissions) === true)
                {
                    throw new Exception\BadRequestValidationFailureException(self::MISSING_SUBMISSIONS);
                }

                break;

            // Submissions are not required for partner deactivation requests
            case (($type === Type::PARTNER) and ($name === Constants::ACTIVATION)):
                if (empty($submissions) === true)
                {
                    throw new Exception\BadRequestValidationFailureException(self::MISSING_SUBMISSIONS);
                }

                if (empty($submissions[Merchant\Entity::PARTNER_TYPE]) === true)
                {
                    throw new Exception\BadRequestValidationFailureException(
                        PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_REQUIRED,
                        Merchant\Entity::PARTNER_TYPE,
                        [
                            Constants::SUBMISSIONS => $submissions,
                        ]);
                }

                $partnerType = $submissions[Merchant\Entity::PARTNER_TYPE];

                (new Merchant\Validator)->validatePartnerType($partnerType);

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
