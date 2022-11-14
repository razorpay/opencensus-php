<?php

namespace RZP\Models\Payment\PaymentSupportingDocuments;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{

    const ID                                = "id";
    const MERCHANT_ID                       = "merchant_id";
    const PAYMENT_ID                        = "payment_id";
    const DOCUMENT_TYPE                     = "document_type";
    const DOCUMENT_NUMBER                   = "document_number";
    const DOCUMENT_OWNER                    = "document_owner";
    const FILE_ID                           = "file_id";
    const CREATED_AT                        = "created_at";
    const UPDATED_AT                        = "updated_at";
    const DELETED_AT                        = "deleted_at";

    const ACCOUNT_ID                        = 'account_id';


    protected $entity      = 'payment_supporting_documents';

    protected $primaryKey  = self::ID;

    protected $fillable    = [
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::DOCUMENT_TYPE,
        self::DOCUMENT_NUMBER,
        self::DOCUMENT_OWNER,
        self::FILE_ID,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $public      = [
        self::ID,
        self::MERCHANT_ID,
        self::PAYMENT_ID,
        self::DOCUMENT_TYPE,
        self::DOCUMENT_NUMBER,
        self::DOCUMENT_OWNER,
        self::FILE_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $dates        = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $defaults     = [
        self::UPDATED_AT                => null,
        self::DELETED_AT                => null,
        self::FILE_ID                   => null,
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
}
