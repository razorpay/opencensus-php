<?php

namespace RZP\Models\Transaction\Statement\DirectAccount\Statement;

use RZP\Models\Transaction;
use RZP\Models\FundTransfer\Mode;
use RZP\Http\BasicAuth\Type as AuthType;

/**
 * Class Fetch
 *
 * @package RZP\Models\Transaction\Statement\DirectAccount\Statement
 */
class Fetch extends Transaction\Statement\Fetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                => 'sometimes|public_id|size:18',
            Entity::BALANCE_ID        => 'sometimes|unsigned_id',
            Entity::CONTACT_ID        => 'sometimes|public_id|size:19',
            Entity::PAYOUT_ID         => 'sometimes|public_id|size:19',
            Entity::CONTACT_NAME      => 'sometimes|string|max:255',
            Entity::CONTACT_PHONE     => 'sometimes|contact_syntax',
            Entity::CONTACT_EMAIL     => 'sometimes|email',
            Entity::CONTACT_TYPE      => 'sometimes|string|max:255',
            Entity::PAYOUT_PURPOSE    => 'sometimes|string|max:255',
            Entity::FUND_ACCOUNT_ID   => 'sometimes|public_id|size:17',
            Entity::UTR               => 'sometimes|string',
            Entity::MODE              => 'sometimes|string|custom',
            Entity::TYPE              => 'sometimes|string|custom',
            Entity::ACTION            => 'sometimes|string|in:debit,credit',
            Entity::NOTES             => 'sometimes|notes_fetch',
            Entity::FUND_ACCOUNT_NUMBER => 'sometimes|string',
            Entity::CONTACT_PHONE_PS  => 'sometimes|string',
            Entity::CONTACT_EMAIL_PS  => 'sometimes|string',
            EsRepository::QUERY       => 'sometimes|string|min:2|max:100',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            Entity::ID,
            Entity::BALANCE_ID,
            Entity::CONTACT_ID,
            Entity::PAYOUT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE,
            Entity::CONTACT_EMAIL,
            Entity::CONTACT_TYPE,
            Entity::PAYOUT_PURPOSE,
            Entity::FUND_ACCOUNT_ID,
            Entity::UTR,
            EsRepository::QUERY,
            Entity::MODE,
            Entity::TYPE,
            Entity::NOTES,
            Entity::FUND_ACCOUNT_NUMBER,
            Entity::CONTACT_PHONE_PS,
            Entity::CONTACT_EMAIL_PS,
        ],
        AuthType::PROXY_AUTH => [
            Entity::ACTION,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CONTACT_ID,
        Entity::PAYOUT_ID,
        Entity::FUND_ACCOUNT_ID,
    ];

    const ES_FIELDS = [
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        EsRepository::QUERY,
        Entity::NOTES,
        Entity::FUND_ACCOUNT_NUMBER,
        Entity::CONTACT_PHONE_PS,
        Entity::CONTACT_EMAIL_PS,
    ];

    const COMMON_FIELDS = [
        Entity::ID,
        Entity::MERCHANT_ID,
        Entity::BALANCE_ID,
        Entity::UTR,
    ];

    protected function validateMode(string $attribute, string $value)
    {
        Mode::validateMode($value);
    }

    protected function validateType(string $attribute, string $value)
    {
        Transaction\Type::validateBankingType($value);
    }
}
