<?php

namespace RZP\Models\Qr;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                    = 'id';
    const BHARAT_QR_ID          = 'bharat_qr_id';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    //card or upi
    const METHOD                = 'method';
    const AMOUNT                = 'amount';
    const VPA                   = 'vpa';
    const CARD_NUMBER           = 'card_number';
    const CARD_NETWORK          = 'card_network';
    const PROVIDER              = 'provider';
    const PROVIDER_REFERENCE_ID = 'provider_reference_id';
    //URN
    const MERCHANT_REFERENCE    = 'merchant_reference';
    const TRACE_NUMBER          = 'trace_number';
    const RRN                   = 'rrn';
    const TRANSACTION_TIME      = 'transaction_time';
    const TRANSACTION_DATE      = 'transaction_date';
    const GATEWAY_TERMINAL_ID   = 'gateway_terminal_id';
    const GATEWAY_TERMINAL_DESC = 'gateway_terminal_desc';
    const CUSTOMER_NAME         = 'customer_name';

    const RECEIVED              = 'received';
    const STATUS_CODE           = 'status_code';

    protected static $sign      = 'qr';

    protected $primaryKey = self::ID;

    protected $entity = 'qr';

    protected $fillable = [
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::METHOD,
        self::VPA,
        self::CARD_NUMBER,
        self::CARD_NETWORK,
        self::PROVIDER,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::TRACE_NUMBER,
        self::RRN,
        self::TRANSACTION_TIME,
        self::TRANSACTION_DATE,
        self::GATEWAY_TERMINAL_ID,
        self::GATEWAY_TERMINAL_DESC,
        self::CUSTOMER_NAME,
        self::STATUS_CODE,
    ];

    protected $visible = [
        self::ID,
        self::AMOUNT,
        self::RRN,
        self::CARD_NUMBER,
        self::CARD_NETWORK,
        self::PROVIDER,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::RRN,
        self::RECEIVED,
        self::STATUS_CODE,
    ];

    protected $generateIdOnCreate = true;

    public function setReceived($received)
    {
        $this->setAttribute($received);
    }

    public function getMerchantReference()
    {
        return $this->getAttribute(self::MERCHANT_REFERENCE);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }
}
