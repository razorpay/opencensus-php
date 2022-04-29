<?php

namespace RZP\Models\BankingAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
            Entity::STATUS                => 'sometimes|string|custom',
            Entity::SUB_STATUS            => 'sometimes|string|custom',
            Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL               => 'sometimes|string|custom',
            Entity::BANK_INTERNAL_STATUS  => 'sometimes|string',
            Entity::BALANCE_ID            => 'sometimes|unsigned_id',
            Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|unsigned_id',
            Entity::ACCOUNT_TYPE          => 'sometimes|string',
            Entity::REVIEWER_ID           => 'sometimes|string',
        ],
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:merchant,merchant.merchantDetail,merchant.promotions.promotion,banking_account_details,reviewers,spocs,banking_account_activation_details,activationCallLog,activationComments',
            Entity::MERCHANT_EMAIL                      => 'sometimes|string',
            Entity::MERCHANT_BUSINESS_NAME              => 'sometimes|string',
            Entity::MERCHANT_POC_CITY                   => 'sometimes|string',
            Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'sometimes|boolean',
            Entity::SALES_TEAM                          => 'sometimes|string',
            Entity::BUSINESS_PAN_VALIDATION             => 'sometimes|string',
            Entity::DECLARATION_STEP                    => 'sometimes|boolean',
            Entity::BUSINESS_CATEGORY                   => 'sometimes|string',
            Entity::BANK_ACCOUNT_TYPE                   => 'sometimes|string',
            Entity::SALES_POC_ID                        => 'sometimes|string',
            Entity::ASSIGNEE_TEAM                       => 'sometimes|string',
            Entity::SOURCE                              => 'sometimes|string',
            Entity::FILTER_SLOT_BOOKED                  => 'sometimes|boolean',
            Entity::SORT_SLOT_BOOKED                    => 'sometimes|in:asc,desc',
            Entity::FROM_SLOT_BOOKED                    => 'sometimes|epoch',
            Entity::TO_SLOT_BOOKED                      => 'sometimes|epoch',
            Entity::FROM_FOLLOW_UP_DATE                 => 'sometimes|epoch',
            Entity::TO_FOLLOW_UP_DATE                   => 'sometimes|epoch',
            Entity::SORT_FOLLOW_UP_DATE                 => 'sometimes|in:asc,desc',
            Entity::APPLICATION_TYPE                    => 'sometimes|string'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::STATUS,
            Entity::SUB_STATUS,
            Entity::ACCOUNT_NUMBER,
            Entity::CHANNEL,
            Entity::BANK_INTERNAL_STATUS,
            Entity::BALANCE_ID,
            Entity::BANK_REFERENCE_NUMBER,
            Entity::FTS_FUND_ACCOUNT_ID,
            Entity::ACCOUNT_TYPE ,
            Entity::REVIEWER_ID,
            Entity::MERCHANT_EMAIL,
            Entity::MERCHANT_BUSINESS_NAME,
            Entity::MERCHANT_POC_CITY,
            Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
            Entity::SALES_TEAM,
            Entity::BUSINESS_PAN_VALIDATION,
            Entity::DECLARATION_STEP,
            Entity::BOOKING_DATE_AND_TIME,
            Entity::BUSINESS_CATEGORY,
            Entity::BANK_ACCOUNT_TYPE,
            Entity::SALES_POC_ID,
            Entity::ASSIGNEE_TEAM,
            Entity::SOURCE,
            Entity::FILTER_SLOT_BOOKED,
            Entity::SORT_SLOT_BOOKED,
            Entity::FROM_SLOT_BOOKED,
            Entity::TO_SLOT_BOOKED,
            Entity::FROM_FOLLOW_UP_DATE,
            Entity::TO_FOLLOW_UP_DATE,
            Entity::SORT_FOLLOW_UP_DATE,
            Entity::APPLICATION_TYPE,
            self::EXPAND_EACH,
        ],
    ];

    public function validateSubstatus(string $attribute, string $subStatus)
    {
        Status::validateSubStatus($subStatus);
    }

    public function validateStatus(string $attribute, string $status)
    {
        Status::validate($status);
    }

    public function validateChannel(string $attribute, string $channel)
    {
        Channel::validateChannel($channel);
    }
}
