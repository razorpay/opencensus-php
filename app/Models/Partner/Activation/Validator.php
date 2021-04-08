<?php

namespace RZP\Models\Partner\Activation;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant\Detail;

class Validator extends Detail\Validator
{
    protected static $editRules = [
        Entity::ACTIVATED_AT              => 'sometimes|int',
        Entity::ACTIVATION_STATUS         => 'sometimes|max:30',
        Entity::HOLD_FUNDS                => 'sometimes|boolean',
        Entity::SUBMITTED                 => 'sometimes|boolean',
        Entity::SUBMITTED_AT              => 'sometimes|int',
        Entity::LOCKED                    => 'sometimes|boolean',
        Entity::KYC_CLARIFICATION_REASONS => 'sometimes|array|custom',
    ];

    protected static $createRules = [
        Entity::ACTIVATED_AT      => 'sometimes|int|nullable',
        Entity::ACTIVATION_STATUS => 'sometimes|max:30',
        Entity::HOLD_FUNDS        => 'sometimes|boolean',
        Entity::SUBMITTED         => 'sometimes|boolean',
        Entity::SUBMITTED_AT      => 'sometimes|int|nullable',
        Entity::LOCKED            => 'sometimes|boolean',
    ];

    protected static $savePartnerActivationRules = [
        Detail\Entity::COMPANY_PAN         => 'filled|companyPan',
        Detail\Entity::PROMOTER_PAN        => 'sometimes|personalPan',
        Detail\Entity::PROMOTER_PAN_NAME   => 'sometimes|max:255',
        Detail\Entity::BANK_ACCOUNT_NUMBER => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,20|custom',
        Detail\Entity::BANK_ACCOUNT_NAME   => 'sometimes|regex:/^[a-zA-Z0-9\s]+$/|min:4|max:120',
        Detail\Entity::BANK_BRANCH_IFSC    => 'sometimes|alpha_num|max:11|custom',
        Detail\Entity::GSTIN               => 'sometimes|string|size:15|nullable',
        Detail\Entity::SUBMIT              => 'sometimes|boolean',

    ];

    protected static $actionRules = [
        Constants::ACTION   => 'required|custom'
    ];

    protected static $activationStatusRules = [
        Entity::ACTIVATION_STATUS               => 'required|string|max:30',
        Entity::REJECTION_REASONS               => 'filled|array',
    ];

    public function validateActivationStatusChange($currentStatus, string $newStatus)
    {
        if (empty($currentStatus) === true)
        {
            return;
        }

        if (in_array($newStatus, Constants::NEXT_ACTIVATION_STATUSES_MAPPING[$currentStatus], true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(self::INVALID_STATUS_CHANGE_MESSAGE);
        }
    }

    public function validateBankAccountNumber($attribute, $bankAccountNumber)
    {
        if(\RZP\Models\BankAccount\Validator::isBlacklistedAccountNumber($bankAccountNumber))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT);
        }
    }

    public function validateAction($attribute, $action)
    {
        if (Action::exists($action) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_ACTION_NOT_SUPPORTED);
        }
    }

    public function validateHoldCommissions(Entity $partnerActivation)
    {
        if ($partnerActivation->isFundsOnHold() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_ON_HOLD);
        }
    }

    public function validateReleaseCommissions(Entity $partnerActivation)
    {
        if ($partnerActivation->isFundsOnHold() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_RELEASED);
        }
    }
}
