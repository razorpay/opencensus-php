<?php

namespace RZP\Models\Partner\Activation;

use RZP\Exception;
use RZP\Models\Partner;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Detail\Constants as DEConstants;

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
        Entity::REVIEWER_ID               => 'sometimes|alpha_num|size:14',
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
        Detail\Entity::CONTACT_NAME                => 'sometimes|alpha_space|max:255',
        Detail\Entity::CONTACT_EMAIL               => 'filled|email|max:255',
        Detail\Entity::CONTACT_MOBILE              => 'sometimes|numeric|digits_between:8,11',
        Detail\Entity::BUSINESS_TYPE               => 'filled|numeric|digits_between:1,10',
        Detail\Entity::COMPANY_PAN                 => 'filled|companyPan',
        Detail\Entity::BUSINESS_NAME               => 'sometimes|max:255',
        Detail\Entity::PROMOTER_PAN                => 'sometimes|personalPan',
        Detail\Entity::PROMOTER_PAN_NAME           => 'sometimes|max:255',
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 'sometimes|regex:/^[a-zA-Z0-9]+$/|between:5,20|custom',
        Detail\Entity::BANK_ACCOUNT_NAME           => 'sometimes|regex:/^[a-zA-Z0-9\s]+$/|min:4|max:120',
        Detail\Entity::BANK_BRANCH_IFSC            => 'sometimes|alpha_num|max:11|custom',
        Detail\Entity::BUSINESS_OPERATION_ADDRESS  => 'sometimes|max:255',
        Detail\Entity::BUSINESS_OPERATION_STATE    => 'sometimes|alpha_space|max:2|custom',
        Detail\Entity::BUSINESS_OPERATION_CITY     => 'sometimes|alpha_space_num|max:255',
        Detail\Entity::BUSINESS_OPERATION_PIN      => 'sometimes|size:6',
        Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 'sometimes|max:255',
        Detail\Entity::BUSINESS_REGISTERED_STATE   => 'sometimes|alpha_space|max:2|custom',
        Detail\Entity::BUSINESS_REGISTERED_CITY    => 'sometimes|alpha_space_num|max:255',
        Detail\Entity::BUSINESS_REGISTERED_PIN     => 'sometimes|size:6',
        Detail\Entity::GSTIN                       => 'sometimes|string|size:15|nullable',
        Detail\Entity::KYC_CLARIFICATION_REASONS   => 'sometimes|array|custom',
        Detail\Entity::SUBMIT                      => 'sometimes|boolean',
        DEConstants::CONSENT                       => 'sometimes|boolean',
        DEConstants::DOCUMENTS_DETAIL              => 'sometimes|array',
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

    /**
     * This function does some validation checks with merchant and partner activation entities before
     * saving/submitting the partner form
     *
     * @param Merchant\Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    public function validatePartnerFormSaveAndSubmit(Merchant\Entity $merchant)
    {
        (new Merchant\Validator())->validateIsPartner($merchant);

        $merchantDetails = $merchant->merchantDetail;

        // do not allow partner form to be submitted when merchant form is under needs clarification
        if ($merchantDetails->getActivationStatus() === Constants::NEEDS_CLARIFICATION)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_FORM_UNDER_NEEDS_CLARIFICATION);
        }

        $partnerActivation = (new Partner\Core())->getPartnerActivation($merchant);

        if($partnerActivation->isLocked() === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_PARTNER_ACTIVATION_ALREADY_LOCKED);
        }
    }
}
