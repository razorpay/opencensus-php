<?php

namespace RZP\Models\QrPayment;

use RZP\Base\Fetch as BaseFetch;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Models\Payment\Entity as PaymentEntity;

class Fetch extends BaseFetch
{
    const RULES = [
        self::DEFAULTS => [
            PaymentEntity::STATUS         => 'sometimes',
            PaymentEntity::NOTES          => 'sometimes|notes_fetch',
            EsRepository::CUSTOMER_EMAIL  => 'sometimes|string',
            Entity::PAYMENT_ID            => 'sometimes|string',
            Entity::QR_CODE_ID            => 'sometimes|string',
            Entity::PROVIDER_REFERENCE_ID => 'sometimes',
            Entity::MERCHANT_ID           => 'sometimes|alpha_num|size:14',
            Entity::GATEWAY               => 'sometimes|string',
        ],
    ];

    const ACCESSES = [
        AuthType::PRIVILEGE_AUTH => [
            Entity::MERCHANT_ID
        ],
        AuthType::PRIVATE_AUTH   => [
            PaymentEntity::NOTES,
            PaymentEntity::STATUS,
            Entity::PAYMENT_ID,
            Entity::QR_CODE_ID,
            Entity::PROVIDER_REFERENCE_ID,
            EsRepository::CUSTOMER_EMAIL,
            Entity::GATEWAY,
        ],
    ];

    const ES_FIELDS = [
        PaymentEntity::NOTES,
        PaymentEntity::STATUS,
        Entity::PAYMENT_ID,
        Entity::QR_CODE_ID,
        Entity::PROVIDER_REFERENCE_ID,
        EsRepository::CUSTOMER_EMAIL,
        Entity::MERCHANT_ID,
        Entity::GATEWAY,
    ];

    const SIGNED_IDS = [
        Entity::QR_CODE_ID,
    ];

    const COMMON_FIELDS = [
    ];
}
