<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Exception;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Exception\BadRequestException;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * Class Validator
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                   => 'required|regex:/^[a-zA-Z0-9][\w\-&\'’,.:()\s\/]+$/|between:4,120|string',
        Entity::MASTER_ACCOUNT_NUMBER  => 'required|string|between:5,35',
        Entity::SUB_ACCOUNT_NUMBER     => 'required|string|between:5,35',
        Entity::SUB_ACCOUNT_TYPE       => 'sometimes|nullable|string|custom',
    ];

    // We want to make sure we are not receving any extra keys that
    // are required.
    protected static $subVirtualAccountTransferWithOtpRules = [
        Entity::MASTER_ACCOUNT_NUMBER  => 'required',
        Entity::SUB_ACCOUNT_NUMBER     => 'required',
        Entity::AMOUNT                 => 'required',
        Entity::CURRENCY               => 'sometimes',
        UserEntity::OTP                => 'required',
        UserEntity::TOKEN              => 'required',
    ];

    // Maximum transfer allowed is 50cr
    protected static $subVirtualAccountTransferRules = [
        Entity::MASTER_ACCOUNT_NUMBER  => 'required|string|between:5,35',
        Entity::SUB_ACCOUNT_NUMBER     => 'required|string|between:5,35',
        Entity::AMOUNT                 => 'required|integer|min:100|max:50000000000',
        Entity::CURRENCY               => 'sometimes|size:3|in:INR',
    ];

    protected static $subDirectAccountTransferRules = [
        Entity::MASTER_ACCOUNT_NUMBER  => 'required|string|between:5,35',
        Entity::SUB_ACCOUNT_NUMBER     => 'required|string|between:5,35',
        Entity::AMOUNT                 => 'required|integer|min:1',
        Entity::CURRENCY               => 'sometimes|size:3|in:INR',
    ];

    protected static $enableOrDisableRules = [
        Entity::ACTIVE  =>  'required|boolean',
    ];

    public function validateMasterMerchant(MerchantEntity $masterMerchant, Entity $subVirtualAccount)
    {
        if ($this->isMasterMerchantFeatureEnabled($subVirtualAccount, $masterMerchant) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_FEATURE_NOT_ENABLED);
        }

        if ($masterMerchant->isBusinessBankingEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_MASTER_MERCHANT,
                null,
                [
                    Entity::MASTER_MERCHANT_ID       => $masterMerchant->getId(),
                    MerchantEntity::BUSINESS_BANKING => $masterMerchant->isBusinessBankingEnabled(),
                ]
            );
        }

        if (($masterMerchant->isFundsOnHold() === true) and
            ($masterMerchant->isFeatureEnabled(Features::SKIP_HOLD_FUNDS_ON_PAYOUT) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                null,
                [
                    Entity::MASTER_MERCHANT_ID  =>  $masterMerchant->getId(),
                ]
            );
        }

        $merchantCoreClass = new \RZP\Models\Merchant\Core();

        if (($masterMerchant->isLive() === false) and
            ($merchantCoreClass->isXVaActivated($masterMerchant) === false))
        {
            if (($subVirtualAccount->getSubAccountType() === Type::SUB_DIRECT_ACCOUNT) and
                ($merchantCoreClass->isCurrentAccountActivated($masterMerchant) === true))
            {
                return;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MASTER_MERCHANT_NOT_LIVE_ACTION_DENIED,
                null,
                [
                    Entity::MASTER_MERCHANT_ID => $masterMerchant->getId(),
                ]);
        }
    }

    public function validateSubMerchant(MerchantEntity $subMerchantEntity)
    {
        if ($subMerchantEntity->isBusinessBankingEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_BUSINESS_BANKING_NOT_ENABLED_FOR_SUB_MERCHANT,
                null,
                [
                    Entity::SUB_MERCHANT_ID => $subMerchantEntity->getId()
                ]
            );
        }

        if (($subMerchantEntity->isLive() === false) and
            ((new \RZP\Models\Merchant\Attribute\Core())->isXVaActivated($subMerchantEntity)) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_MERCHANT_NOT_LIVE_ACTION_DENIED,
                null,
                [
                    Entity::SUB_MERCHANT_ID => $subMerchantEntity->getId()
                ]
            );
        }
    }

    public function validateSubVirtualAccount($subVirtualAccount, array $input)
    {
        if ($subVirtualAccount === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_DOES_NOT_EXIST,
                null,
                [
                    Entity::INPUT => $input
                ]
            );
        }

        if ($subVirtualAccount->getActive() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_TRANSFER_DISABLED,
                null,
                [
                    Entity::INPUT => $input
                ]
            );
        }
    }

    protected function isMasterMerchantFeatureEnabled(Entity $subVirtualAccount, MerchantEntity $masterMerchant)
    {
        $isFeatureEnabled = true;

        switch ($subVirtualAccount->getSubAccountType())
        {
            case Type::SUB_DIRECT_ACCOUNT:
                if ($masterMerchant->isFeatureEnabled(Features::ASSUME_MASTER_ACCOUNT) === false)
                {
                    $isFeatureEnabled = false;
                }
                break;

            case Type::DEFAULT:
                if ($masterMerchant->isFeatureEnabled(Features::SUB_VIRTUAL_ACCOUNT) === false)
                {
                    $isFeatureEnabled = false;
                }
                break;

            default:
                $isFeatureEnabled = false;
        }

        return $isFeatureEnabled;
    }

    public function validateSubAccountType($attribute, $value)
    {
        if (Type::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                TraceCode::BAD_REQUEST_SUB_VIRTUAL_ACCOUNT_INVALID_TYPE,
                null,
                [
                    $attribute => $value
                ]
            );
        }
    }
}
