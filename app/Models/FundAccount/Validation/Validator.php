<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;
use RZP\Models\FundTransfer\Attempt\Entity as FtaEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    // This is used for both banking and PG creation request
    protected static $createRules = [
        Entity::FUND_ACCOUNT => 'required|associative_array',
        Entity::AMOUNT       => 'sometimes|integer|min:100|max:200',
        Entity::NOTES        => 'sometimes|notes',
        Entity::CURRENCY     => 'filled|string|in:INR',
        Entity::RECEIPT      => 'sometimes|string|min:1|max:40',
        Entity::BALANCE_ID   => 'sometimes|unsigned_id',
    ];

    // This is only used for banking bank account creation request
    protected static $bankingBankAccountRules = [
        Entity::AMOUNT                          => 'required|integer|min:100|max:200',
        Entity::NOTES                           => 'sometimes|notes',
        Entity::CURRENCY                        => 'filled|string|in:INR',
        Entity::BALANCE_ID                      => 'required|custom',
        Entity::FUND_ACCOUNT                    => 'required|associative_array',
        Entity::RECEIPT                         => 'sometimes|string|min:1|max:40',
        Entity::FUND_ACCOUNT . '.' . Entity::ID => 'required|unsigned_id',
    ];

    // This is only used for banking vpa creation request
    protected static $bankingVpaRules = [
        Entity::NOTES                           => 'sometimes|notes',
        Entity::BALANCE_ID                      => 'required|custom',
        Entity::FUND_ACCOUNT                    => 'required|associative_array',
        Entity::FUND_ACCOUNT . '.' . Entity::ID => 'required|unsigned_id',
    ];

    protected static $bulkPatchFavRules = [
        Entity::FUND_ACCOUNT_VALIDATION_IDS      => 'required|array|min:1',
        Entity::FUND_ACCOUNT_VALIDATION_IDS.".*" => 'required|public_id',
    ];

    protected static $ftsStatusUpdateRules = [
        FtaEntity::UTR                => 'sometimes|string',
        FtaEntity::STATUS             => 'required|string',
        FtaEntity::REMARKS            => 'sometimes|string',
        FtaEntity::NARRATION          => 'sometimes|string',
        FtaEntity::DATE_TIME          => 'sometimes|string',
        FtaEntity::SOURCE_ID          => 'required_with:source_type|string',
        FtaEntity::SOURCE_TYPE        => 'required_with:source_id|string',
        FtaEntity::FAILURE_REASON     => 'sometimes|string',
        FtaEntity::MODE               => 'sometimes|string',
        'bank_processed_time'         => 'sometimes|string',
        'fund_transfer_id'            => 'required|int',
        'extra_info'                  => 'sometimes',
        'extra_info.*'                => 'sometimes',
        'return_utr'                  => 'sometimes|string',
        FtaEntity::BANK_STATUS_CODE   => 'sometimes|string',
        FtaEntity::GATEWAY_REF_NO     => 'sometimes|string',
        FtaEntity::GATEWAY_ERROR_CODE => 'sometimes|string',
        FtaEntity::CHANNEL            => 'sometimes|string',
        FtaEntity::SOURCE_ACCOUNT_ID  => 'sometimes|int',
        FtaEntity::BANK_ACCOUNT_TYPE  => 'sometimes',
    ];

    protected static $createValidators = [
        'amount_as_integer',
    ];

    protected static $bankingBankAccountValidators = [
        'amount_as_integer',
    ];

    /**
     * @param $attribute
     * @param $value
     *
     * @throws BadRequestException
     */
    public function validateBalanceId($attribute, $value)
    {
        /** @var Entity $validation */
        $validation = $this->entity;

        if ((empty($validation->balance) === true) or
            (($validation->balance->isTypeBanking() === true) and
             ($validation->balance->isAccountTypeShared() === false)))
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_NOT_SUPPORTED_BALANCE,
                Merchant\Balance\Entity::ACCOUNT_NUMBER,
                [
                    Merchant\Balance\Entity::ACCOUNT_NUMBER => $validation->balance->getAccountNumber(),
                ]
            );
        }
    }

    protected function validateStatus($attribute, $value)
    {
        if (in_array($value, Status::$favPossibleStatuses) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid status',
                $attribute,
                $value);
        }
    }

    public function validateStatusInFtsWebhook($value)
    {
        if (in_array($value, Status::$ftaPossibleStatuses) === false)
        {
            throw new BadRequestValidationFailureException(
                'Invalid status',
                Entity::STATUS,
                $value);
        }
    }
}
