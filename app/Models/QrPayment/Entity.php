<?php

namespace RZP\Models\QrPayment;

use App;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const GATEWAY               = 'gateway';
    const PAYMENT_ID            = 'payment_id';
    const QR_CODE_ID            = 'qr_code_id';
    const EXPECTED              = 'expected';
    const UNEXPECTED_REASON     = 'unexpected_reason';
    const METHOD                = 'method';
    const AMOUNT                = 'amount';
    const PAYER_VPA             = 'payer_vpa';
    const PROVIDER_REFERENCE_ID = 'provider_reference_id';
    const MERCHANT_REFERENCE    = 'merchant_reference';
    const TRANSACTION_TIME      = 'transaction_time';

    protected static $sign = 'qp';

    protected $primaryKey = self::ID;

    protected $entity = 'qr_payment';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::PAYMENT_ID,
        self::AMOUNT,
        self::QR_CODE_ID,
        self::EXPECTED,
        self::METHOD,
        self::PAYER_VPA,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::TRANSACTION_TIME,
        self::GATEWAY,
        self::UNEXPECTED_REASON,
    ];

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::QR_CODE_ID,
        self::EXPECTED,
        self::UNEXPECTED_REASON,
        self::METHOD,
        self::PAYER_VPA,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::TRANSACTION_TIME,
        self::GATEWAY,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::QR_CODE_ID,
        self::EXPECTED,
        self::UNEXPECTED_REASON,
        self::METHOD,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::TRANSACTION_TIME,
        self::CREATED_AT,
        self::GATEWAY,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    public function setUnexpectedReason($unexpectedReason)
    {
        $this->setAttribute(self::UNEXPECTED_REASON, $unexpectedReason);
    }
}
