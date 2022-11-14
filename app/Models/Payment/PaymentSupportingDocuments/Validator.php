<?php

namespace RZP\Models\Payment\PaymentSupportingDocuments;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::PAYMENT_ID             => 'required|string|size:14',
        Entity::DOCUMENT_TYPE          => 'required|string|between:1,45',
        Entity::DOCUMENT_NUMBER        => 'sometimes|string|between:1,45',
        Entity::DOCUMENT_OWNER         => 'required|string|between:1,45',
        Entity::FILE_ID                => 'sometimes|size:14',
        Entity::UPDATED_AT             => 'sometimes',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::PAYMENT_ID             => 'required|string|size:14',
        Entity::DOCUMENT_TYPE          => 'required|string|between:1,45',
        Entity::DOCUMENT_NUMBER        => 'sometimes|string|between:1,45',
        Entity::DOCUMENT_OWNER         => 'required|string|between:1,45',
        Entity::FILE_ID                => 'sometimes|size:14',
        Entity::UPDATED_AT             => 'sometimes',
    ];

}
