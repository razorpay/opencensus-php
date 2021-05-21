<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Balance\Type as BalanceType;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Validator extends Base\Validator
{
    protected static $adminCreateRules = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'required|alpha_num|size:14',
        Entity::STATUS                     => 'required|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER       => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'required|alpha_num|size:11',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|string|between:14,18',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
    ];

    protected static $createRules      = [
        Entity::MERCHANT_ID                => 'required|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'required|alpha_num|size:14',
        Entity::STATUS                     => 'required|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER       => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'required|alpha_num|size:11',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|alpha_num|size:14',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
    ];

    protected static $adminEditRules   = [
        Entity::MERCHANT_ID                => 'filled|alpha_num|size:14',
        Entity::BALANCE_ID                 => 'filled|alpha_num|size:14',
        Entity::PAYER_ACCOUNT_NUMBER       => 'filled|alpha_num|between:5,40',
        Entity::PAYER_IFSC                 => 'filled|alpha_num|size:11',
        Entity::STATUS                     => 'sometimes|string|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::REJECTED,
        Entity::PAYER_NAME                 => 'sometimes|string|max:255',
        Entity::CREATED_BY                 => 'sometimes|string|max:255',
        Entity::TYPE                       => 'sometimes|in:'.Type::BANK_ACCOUNT,
        Entity::REMARKS                    => 'sometimes|string|max:255',
        Entity::NOTES                      => 'sometimes|string|max:255',
        Entity::FUND_ACCOUNT_VALIDATION_ID => 'sometimes|string|between:14,18',
    ];

    protected static $merchantDashboardCreateRules = [
        Entity::BALANCE_ID           => 'required|alpha_num|size:14',
        Entity::PAYER_NAME           => 'required|string|max:255',
        Entity::PAYER_ACCOUNT_NUMBER => 'required|alpha_num|between:5,40',
        Entity::PAYER_IFSC           => 'required|alpha_num|size:11',
        Entity::NOTES                => 'sometimes|string|max:255',
        Entity::TYPE                 => 'sometimes|in:' . Type::BANK_ACCOUNT,
    ];

    public function validateMerchantBalanceId(string $merchantId, string $balanceId)
    {
        try
        {
            /** @var BalanceEntity $balance */
            $balance = app('repo')->balance->findOrFailById($balanceId);

            /** @var MerchantEntity $merchant */
            $merchant = app('repo')->merchant->findOrFail($merchantId);
        }
        catch (\Throwable $ex)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TPV_ERROR);
        }

        $accountTypeDirectBalanceIds = $merchant->directBankingBalances()->pluck('id')->toArray();

        if($balance->getMerchantId() !== $merchantId)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID);
        }
        else if($balance->getType() === BalanceType::PRIMARY)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TPV_PRIMARY_BALANCE_NOT_SUPPORTED);
        }
        else if(in_array($balanceId, $accountTypeDirectBalanceIds) === true)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_TPV_BALANCE_TYPE_DIRECT_NOT_SUPPORTED);
        }
    }
}
