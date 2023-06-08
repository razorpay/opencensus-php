<?php

namespace RZP\Models\BankingAccount;

use RZP\Base\Fetch as BaseFetch;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Http\BasicAuth\Type as AuthType;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::MERCHANT_ID           => 'sometimes|unsigned_id',
            Entity::STATUS                => 'sometimes|custom',
            Entity::SUB_STATUS            => 'sometimes|string|custom',
            Entity::ACCOUNT_NUMBER        => 'sometimes|alpha_num|max:40',
            Entity::CHANNEL               => 'sometimes|string|custom',
            Entity::BANK_INTERNAL_STATUS  => 'sometimes|string',
            Entity::BALANCE_ID            => 'sometimes|unsigned_id',
            Entity::BANK_REFERENCE_NUMBER => 'sometimes|string',
            Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|unsigned_id',
            Entity::ACCOUNT_TYPE          => 'sometimes|string',
            Entity::REVIEWER_ID           => 'sometimes|string',
            Entity::FILTER_MERCHANTS      => 'sometimes|array',
            Entity::EXCLUDE_STATUS        => 'sometimes|custom',
            self::COUNT                   => 'filled|integer|min:1|max:1000',
            self::EXPAND_EACH             => 'filled|string|in:merchant,merchant.merchantDetail,reviewers,spocs,banking_account_activation_details',
        ],
        AuthType::PRIVILEGE_AUTH => [
            self::EXPAND_EACH             => 'filled|string|in:merchant,merchant.merchantDetail,merchant.promotions.promotion,banking_account_details,reviewers,spocs,banking_account_activation_details,activationCallLog,activationComments,opsMxPocs',
            Entity::MERCHANT_EMAIL                      => 'sometimes|string',
            Entity::MERCHANT_BUSINESS_NAME              => 'sometimes|string',
            Entity::MERCHANT_POC_CITY                   => 'sometimes|string',
            Entity::FOS_CITY                            => 'sometimes|string',
            Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE   => 'sometimes|boolean',
            Entity::SALES_TEAM                          => 'sometimes|string',
            Entity::BUSINESS_PAN_VALIDATION             => 'sometimes|string',
            Entity::DECLARATION_STEP                    => 'sometimes|boolean',
            Entity::BUSINESS_CATEGORY                   => 'sometimes|string',
            Entity::CLARITY_CONTEXT                     => 'sometimes|string',
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
            Entity::APPLICATION_TYPE                    => 'sometimes|string',
            Entity::PENDING_ON                          => 'sometimes|string',
            Entity::FROM_OPS_FOLLOW_UP_DATE             => 'sometimes|epoch',
            Entity::TO_OPS_FOLLOW_UP_DATE               => 'sometimes|epoch',
            Entity::SKIP_DWT                            => 'sometimes|int',
            Entity::OPS_MX_POC_ID                       => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        self::DEFAULTS => [
            self::EXPAND_EACH,
            Entity::FILTER_MERCHANTS,
            Entity::EXCLUDE_STATUS,
            Entity::STATUS,
            Entity::CHANNEL,
            Entity::ACCOUNT_TYPE,
            self::COUNT,
        ],
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
            Entity::FOS_CITY,
            Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE,
            Entity::SALES_TEAM,
            Entity::BUSINESS_PAN_VALIDATION,
            Entity::DECLARATION_STEP,
            Entity::CLARITY_CONTEXT,
            Entity::BOOKING_DATE_AND_TIME,
            Entity::BUSINESS_CATEGORY,
            Entity::BANK_ACCOUNT_TYPE,
            Entity::SALES_POC_ID,
            Entity::PENDING_ON,
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
            Entity::FROM_OPS_FOLLOW_UP_DATE,
            Entity::TO_OPS_FOLLOW_UP_DATE,
            Entity::SKIP_DWT,
            Entity::OPS_MX_POC_ID,
            Entity::FROM_DOCKET_ESTIMATED_DELIVERY_DATE,
            Entity::TO_DOCKET_ESTIMATED_DELIVERY_DATE,
            Entity::EXCLUDE_STATUS,
            self::EXPAND_EACH,
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH,
            Entity::FILTER_MERCHANTS,
            Entity::EXCLUDE_STATUS,
            Entity::STATUS,
            Entity::CHANNEL,
            Entity::ACCOUNT_TYPE,
            self::COUNT,
        ]
    ];

    public function validateSubstatus(string $attribute, string $subStatus)
    {
        Status::validateSubStatus($subStatus);
    }

    public function validateStatus(string $attribute, $status)
    {
        if (is_array($status))
        {
            foreach ($status as $s)
            {
                Status::validate($s);
            }
        }
        else if (is_string($status))
        {
            Status::validate($status);
        }
        else
        {
            throw new BadRequestValidationFailureException(
                'Not a valid Razorpay Banking status ' . $status,
                Entity::STATUS,
                [
                    Entity::STATUS => $status
                ]);
        }
    }

    public function validateChannel(string $attribute, string $channel)
    {
        Channel::validateChannel($channel);
    }

    public function validateExcludeStatus(string $attribute, $excludeStatus)
    {
        $this->validateStatus($attribute, $excludeStatus);
    }
}
