<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Base\Fetch as BaseFetch;
use RZP\Models\Settlement\Channel;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Reversal\Entity as ReversalEntity;
use RZP\Models\PayoutSource\Entity as PayoutSource;
use RZP\Models\PayoutsDetails\Entity as PayoutDetails;
use RZP\Models\PayoutsStatusDetails\Entity as PayoutsStatusDetails;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            Entity::ID                   => 'sometimes|public_id|size:19',
            Entity::MERCHANT_ID          => 'sometimes|unsigned_id',
            Entity::CUSTOMER_ID          => 'sometimes|public_id|size:19',
            Entity::BALANCE_ID           => 'sometimes|unsigned_id',
            Entity::PRODUCT              => 'required_without:balance_id|string|in:banking',
            Entity::DESTINATION          => 'sometimes|public_id|max:20',
            Entity::METHOD               => 'sometimes|string|custom',
            Entity::MODE                 => 'sometimes|string|custom',
            Entity::TRANSACTION_ID       => 'sometimes|public_id',
            Entity::UTR                  => 'sometimes|string|max:255',
            Entity::CONTACT_NAME         => 'sometimes|string|max:50',
            Entity::CONTACT_PHONE        => 'sometimes|contact_syntax',
            Entity::CONTACT_EMAIL        => 'sometimes|email|max:50',
            Entity::CONTACT_TYPE         => 'sometimes|string',
            Entity::CONTACT_ID           => 'sometimes|public_id|size:19',
            Entity::FUND_ACCOUNT_ID      => 'sometimes|public_id|size:17',
            Entity::NOTES                => 'sometimes|notes_fetch',
            Entity::FUND_ACCOUNT_NUMBER  => 'sometimes|string',
            Entity::CONTACT_PHONE_PS     => 'sometimes|string',
            Entity::CONTACT_EMAIL_PS     => 'sometimes|string',
            Entity::BATCH_ID             => 'sometimes|string|public_id|size:20',
            Entity::STATUS               => 'sometimes|string|custom',
            Entity::REFERENCE_ID         => 'sometimes|string|max:40',
            Entity::PURPOSE              => 'sometimes|string|max:255',
            EsRepository::QUERY          => 'sometimes|string|min:2|max:50',
            Entity::REVERSED_FROM        => 'sometimes|epoch',
            Entity::REVERSED_TO          => 'sometimes|epoch',
            Entity::CHANNEL              => 'sometimes|string|custom',
            // EsRepository::SEARCH_HITS => 'sometimes|boolean',
            Entity::SCHEDULED_FROM       => 'sometimes|required_with:scheduled_to|epoch',
            Entity::SCHEDULED_TO         => 'sometimes|epoch',
            Entity::SORTED_ON            => 'sometimes|string|custom',
            Entity::QUEUED_REASON        => 'sometimes|string|max:255',
            PayoutsStatusDetails::REASON => 'sometimes|string',
            Entity::SOURCE_TYPE_EXCLUDE => 'sometimes|string|max:255',
        ],
        AuthType::PROXY_AUTH => [
            self::EXPAND_EACH                       => 'filled|string|in:user,reversal,fund_account,fund_account.contact,transaction',
            // Because, dashboard thinks there can be just one mode (live/test).
            Entity::PAYOUT_MODE                     => 'sometimes|string|custom',
            Entity::PENDING_ON_ME                   => 'sometimes|boolean',
            Entity::PENDING_ON_ROLES                => 'sometimes|array',
            Entity::PENDING_ON_ROLES . '.*'         => 'filled|string',
            // These are not expected from the dashboard, but are set internally via code.
            Entity::PENDING_ON_ME_VIA_WFS           => 'sometimes|boolean',
            Entity::PENDING_ON_ROLES_VIA_WFS        => 'sometimes|array',
            Entity::PENDING_ON_ROLES_VIA_WFS . '.*' => 'filled|string',
            PayoutSource::SOURCE_ID                 => 'sometimes|string',
            PayoutSource::SOURCE_TYPE               => 'sometimes|string',
            Entity::REVERSAL_ID                     => 'sometimes|public_id|size:20',
            PayoutDetails::TDS_CATEGORY_ID          => 'sometimes|integer',
            PayoutDetails::TAX_PAYMENT_ID           => 'sometimes|string',
            Entity::PAYOUT_IDS                      => 'sometimes|array',
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::PRODUCT                 => 'sometimes|string',
            Entity::PAYOUT_LINK_ID          => 'sometimes|string',
            PayoutSource::SOURCE_ID         => 'sometimes|string',
            PayoutSource::SOURCE_TYPE       => 'sometimes|string',
        ],
        AuthType::PRIVATE_AUTH => [
            self::EXPAND_EACH                       => 'filled|string|in:user,reversal,fund_account,fund_account.contact,transaction',
            // Because, dashboard thinks there can be just one mode (live/test).
            Entity::PRODUCT                         => 'sometimes:balance_id|string|in:banking',
            Entity::PENDING_ON_ME                   => 'sometimes|boolean',
            Entity::PENDING_ON_ROLES                => 'sometimes|array',
            Entity::PENDING_ON_ROLES . '.*'         => 'filled|string',
            // These are not expected from the dashboard, but are set internally via code.
            Entity::PENDING_ON_ME_VIA_WFS           => 'sometimes|boolean',
            Entity::PENDING_ON_ROLES_VIA_WFS        => 'sometimes|array',
            Entity::PENDING_ON_ROLES_VIA_WFS . '.*' => 'filled|string',
            Entity::SOURCE_TYPE_EXCLUDE             => 'sometimes|string|max:255',
        ]
    ];

    const ACCESSES = [
        AuthType::PRIVATE_AUTH => [
            self::EXPAND_EACH,
            Entity::PENDING_ON_ME,
            Entity::PENDING_ON_ROLES,
            Entity::PENDING_ON_ROLES . '.*',
            Entity::PENDING_ON_ME_VIA_WFS,
            Entity::PENDING_ON_ROLES_VIA_WFS,
            Entity::PENDING_ON_ROLES_VIA_WFS . '.*',
            Entity::PRODUCT,
            Entity::ID,
            Entity::TRANSACTION_ID,
            Entity::UTR,
            Entity::CONTACT_ID,
            Entity::CONTACT_NAME,
            Entity::CONTACT_PHONE,
            Entity::CONTACT_TYPE,
            Entity::CONTACT_EMAIL,
            Entity::FUND_ACCOUNT_ID,
            Entity::BALANCE_ID,
            Entity::STATUS,
            Entity::REFERENCE_ID,
            Entity::PURPOSE,
            EsRepository::QUERY,
            // EsRepository::SEARCH_HITS,
            Entity::MODE,
            Entity::NOTES,
            Entity::FUND_ACCOUNT_NUMBER,
            Entity::CONTACT_PHONE_PS,
            Entity::CONTACT_EMAIL_PS,
            Entity::SOURCE_TYPE_EXCLUDE,
            PayoutsStatusDetails::REASON,
        ],
        AuthType::PROXY_AUTH     => [
            self::EXPAND_EACH,
            Entity::BATCH_ID,
            Entity::PAYOUT_MODE,
            Entity::PENDING_ON_ME,
            Entity::PENDING_ON_ROLES,
            Entity::PENDING_ON_ROLES . '.*',
            Entity::PENDING_ON_ME_VIA_WFS,
            Entity::PENDING_ON_ROLES_VIA_WFS,
            Entity::PENDING_ON_ROLES_VIA_WFS . '.*',
            Entity::REVERSED_FROM,
            Entity::REVERSED_TO,
            Entity::PRODUCT,
            Entity::SCHEDULED_FROM,
            Entity::SCHEDULED_TO,
            Entity::SORTED_ON,
            PayoutSource::SOURCE_ID,
            PayoutSource::SOURCE_TYPE,
            Entity::QUEUED_REASON,
            Entity::REVERSAL_ID,
            PayoutsStatusDetails::REASON,
            PayoutDetails::TDS_CATEGORY_ID,
            PayoutDetails::TAX_PAYMENT_ID,
            Entity::PAYOUT_IDS,
        ],
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID,
            Entity::CUSTOMER_ID,
            Entity::DESTINATION,
            Entity::METHOD,
            Entity::CHANNEL,
            Entity::PAYOUT_LINK_ID,
            PayoutSource::SOURCE_ID,
            PayoutSource::SOURCE_TYPE,
            PayoutDetails::TDS_CATEGORY_ID,
            PayoutDetails::TAX_PAYMENT_ID,
        ],
    ];

    const SIGNED_IDS = [
        Entity::CUSTOMER_ID,
        Entity::TRANSACTION_ID,
        Entity::CONTACT_ID,
        Entity::FUND_ACCOUNT_ID,
        Entity::BATCH_ID,
        Entity::PAYOUT_LINK_ID,
        Entity::REVERSAL_ID
    ];

    const ES_FIELDS = [
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        EsRepository::QUERY,
        Entity::CONTACT_PHONE_PS,
        Entity::CONTACT_EMAIL_PS,
        Entity::NOTES,
        Entity::FUND_ACCOUNT_NUMBER,
        // EsRepository::SEARCH_HITS,
    ];

    const COMMON_FIELDS = [
        Entity::MERCHANT_ID,
        Entity::TYPE,
        Entity::METHOD,
        Entity::MODE,
        Entity::BALANCE_ID,
        Entity::STATUS,
        Entity::PURPOSE,
        Entity::REVERSED_FROM,
        Entity::REVERSED_TO,
        Entity::PRODUCT,
        Entity::CONTACT_TYPE,
        Entity::SCHEDULED_TO,
        Entity::SCHEDULED_FROM,
        Entity::SOURCE_TYPE_EXCLUDE,
    ];

    const PAYOUT_SERVICE_FETCH_ALLOWED_FIELDS = [
        Entity::ID,
        Entity::STATUS,
        Entity::PURPOSE,
        Entity::REVERSED_FROM,
        Entity::REVERSED_TO,
        Entity::BATCH_ID,
        Entity::PAYOUT_MODE,
        Entity::QUEUED_REASON,
        Entity::REVERSAL_ID,
        Entity::FUND_ACCOUNT_ID,
        Entity::REFERENCE_ID,
        Entity::BALANCE_ID,
        Entity::PRODUCT,
        Entity::MODE,
        Entity::TRANSACTION_ID,
        Entity::UTR,
        Entity::CONTACT_NAME,
        Entity::CONTACT_EMAIL,
        Entity::CONTACT_TYPE,
        Entity::CHANNEL,
        Entity::PENDING_ON_ROLES,
        Fetch::COUNT,
        Fetch::SKIP,
        Fetch::FROM,
        Fetch::TO,
        self::EXPAND,
    ];

    protected function validateMethod(string $attribute, string $value)
    {
        Method::validateMethod($value);
    }

    protected function validateStatus(string $attribute, string $value)
    {
        Status::validate($value);
    }

    protected function validateMode(string $attribute, string $value)
    {
        Mode::validateMode($value);
    }

    protected function validatePayoutMode(string $attribute, string $value)
    {
        Mode::validateMode($value);
    }

    protected function validateChannel(string $attribute, string $value)
    {
        Channel::validate($value);
    }

    // Currently we only allow sorting on these two timestamps
    protected function validateSortedOn(string $attribute, string $value)
    {
        if (in_array($value, [Entity::CREATED_AT, Entity::SCHEDULED_AT], true) === true)
        {
            return;
        }

        throw new Exception\BadRequestValidationFailureException('Cannot sort payouts on: ' . $value);
    }

    public function canFetchRequestBeRoutedToMicroservice(array $input) : bool
    {
        $filteredInputAfterExcludingPayoutServiceFetchAllowedFields =
            array_diff(array_keys($input), self::PAYOUT_SERVICE_FETCH_ALLOWED_FIELDS);

        // If this condition is true, it means there were no elements in $input which were not present in
        // PAYOUT_SERVICE_FETCH_ALLOWED_FIELDS and hence we can route the service via Microservice.
        if (empty($filteredInputAfterExcludingPayoutServiceFetchAllowedFields) === true)
        {
            return true;
        }

        return false;
    }
}
