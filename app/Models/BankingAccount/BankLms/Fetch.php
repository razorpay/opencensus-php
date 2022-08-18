<?php

namespace RZP\Models\BankingAccount\BankLms;

use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BankingAccount\Fetch
{

    const RULES = [
        self::DEFAULTS           => [
            PublicEntity::MERCHANT_ID                    => 'sometimes|unsigned_id',
            BankingAccount\Entity::STATUS                => 'sometimes|string|custom',
            BankingAccount\Entity::SUB_STATUS            => 'sometimes|string|custom',
            BankingAccount\Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            BankingAccount\Entity::CHANNEL               => 'sometimes|string|custom',
            BankingAccount\Entity::BANK_INTERNAL_STATUS  => 'sometimes|string',
            BankingAccount\Entity::BALANCE_ID            => 'sometimes|unsigned_id',
            BankingAccount\Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            BankingAccount\Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|unsigned_id',
            BankingAccount\Entity::ACCOUNT_TYPE          => 'sometimes|string',
            BankingAccount\Entity::REVIEWER_ID           => 'sometimes|string',
        ],
        AuthType::PROXY_AUTH     => [
            self::EXPAND_EACH                   => 'filled|string|in:banking_account_details,banking_account_activation_details,activationCallLog',
            BankingAccount\Entity::ACCOUNT_TYPE => 'sometimes|string',
            BankingAccount\Entity::CHANNEL      => 'sometimes|string|custom',
            Entity::FILTER_MERCHANTS            => 'sometimes|array',
            Entity::BANK_POC_USER_ID            => 'sometimes|string|size:14',
            Entity::BUSINESS_CATEGORY           => 'sometimes|string',
            Constants::LEAD_RECEIVED_FROM_DATE       => 'required_with:lead_received_to_date|integer',
            Constants::LEAD_RECEIVED_TO_DATE         => 'required_with:lead_received_from_date|integer',
            Constants::ACTIVATION_ACCOUNT_TYPE       => 'sometimes|string',
            BankingAccount\Entity::BANK_ACCOUNT_TYPE => 'sometimes|string',
        ],
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH                                        => 'filled|string|in:merchant,merchant.merchantDetail,merchant.promotions.promotion,banking_account_details,reviewers,spocs,banking_account_activation_details,activationCallLog,activationComments',
            BankingAccount\Entity::MERCHANT_EMAIL                    => 'sometimes|string',
            BankingAccount\Entity::MERCHANT_BUSINESS_NAME            => 'sometimes|string',
            BankingAccount\Entity::MERCHANT_POC_CITY                 => 'sometimes|string',
            BankingAccount\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => 'sometimes|boolean',
            BankingAccount\Entity::SALES_TEAM                        => 'sometimes|string',
            BankingAccount\Entity::BUSINESS_PAN_VALIDATION           => 'sometimes|string',
            BankingAccount\Entity::DECLARATION_STEP                  => 'sometimes|boolean',
            BankingAccount\Entity::BUSINESS_CATEGORY                 => 'sometimes|string',
            BankingAccount\Entity::BANK_ACCOUNT_TYPE                 => 'sometimes|string',
            BankingAccount\Entity::SALES_POC_ID                      => 'sometimes|string',
            BankingAccount\Entity::ASSIGNEE_TEAM                     => 'sometimes|string',
            BankingAccount\Entity::SOURCE                            => 'sometimes|string',
            BankingAccount\Entity::FILTER_SLOT_BOOKED                => 'sometimes|boolean',
            BankingAccount\Entity::SORT_SLOT_BOOKED                  => 'sometimes|in:asc,desc',
            BankingAccount\Entity::FROM_SLOT_BOOKED                  => 'sometimes|epoch',
            BankingAccount\Entity::TO_SLOT_BOOKED                    => 'sometimes|epoch',
            BankingAccount\Entity::FROM_FOLLOW_UP_DATE               => 'sometimes|epoch',
            BankingAccount\Entity::TO_FOLLOW_UP_DATE                 => 'sometimes|epoch',
            BankingAccount\Entity::SORT_FOLLOW_UP_DATE               => 'sometimes|in:asc,desc',
            BankingAccount\Entity::APPLICATION_TYPE                  => 'sometimes|string'
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            PublicEntity::MERCHANT_ID,
            BankingAccount\Entity::STATUS,
            BankingAccount\Entity::SUB_STATUS,
            BankingAccount\Entity::ACCOUNT_NUMBER,
            BankingAccount\Entity::CHANNEL,
            BankingAccount\Entity::BANK_INTERNAL_STATUS,
            BankingAccount\Entity::BALANCE_ID,
            BankingAccount\Entity::BANK_REFERENCE_NUMBER,
            BankingAccount\Entity::FTS_FUND_ACCOUNT_ID,
            BankingAccount\Entity::ACCOUNT_TYPE,
            BankingAccount\Entity::REVIEWER_ID,
            BankingAccount\Entity::MERCHANT_EMAIL,
            BankingAccount\Entity::MERCHANT_BUSINESS_NAME,
            BankingAccount\Entity::MERCHANT_POC_CITY,
            BankingAccount\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
            BankingAccount\Entity::SALES_TEAM,
            BankingAccount\Entity::BUSINESS_PAN_VALIDATION,
            BankingAccount\Entity::DECLARATION_STEP,
            BankingAccount\Entity::BOOKING_DATE_AND_TIME,
            BankingAccount\Entity::BUSINESS_CATEGORY,
            BankingAccount\Entity::BANK_ACCOUNT_TYPE,
            BankingAccount\Entity::SALES_POC_ID,
            BankingAccount\Entity::ASSIGNEE_TEAM,
            BankingAccount\Entity::SOURCE,
            BankingAccount\Entity::FILTER_SLOT_BOOKED,
            BankingAccount\Entity::SORT_SLOT_BOOKED,
            BankingAccount\Entity::FROM_SLOT_BOOKED,
            BankingAccount\Entity::TO_SLOT_BOOKED,
            BankingAccount\Entity::FROM_FOLLOW_UP_DATE,
            BankingAccount\Entity::TO_FOLLOW_UP_DATE,
            BankingAccount\Entity::SORT_FOLLOW_UP_DATE,
            BankingAccount\Entity::APPLICATION_TYPE,
            self::EXPAND_EACH,
        ],
        AuthType::PROXY_AUTH     => [
            BankingAccount\Entity::ACCOUNT_TYPE,
            BankingAccount\Entity::CHANNEL,
            Entity::FILTER_MERCHANTS,
            Entity::BANK_POC_USER_ID,
            Entity::BUSINESS_CATEGORY,
            Constants::LEAD_RECEIVED_FROM_DATE,
            Constants::LEAD_RECEIVED_TO_DATE,
            Constants::ACTIVATION_ACCOUNT_TYPE,
            BankingAccount\Entity::BANK_ACCOUNT_TYPE,
            self::EXPAND_EACH,
        ],
    ];

}
