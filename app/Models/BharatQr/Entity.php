<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\VirtualAccount;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const PAYMENT_ID            = 'payment_id';
    const EXPECTED              = 'expected';
    const VIRTUAL_ACCOUNT_ID    = 'virtual_account_id';
    const GATEWAY_MERCHANT_ID   = 'gateway_merchant_id';
    //card or upi
    const METHOD                = 'method';
    const AMOUNT                = 'amount';
    const VPA                   = 'vpa';
    const CARD_NUMBER           = 'card_number';
    const CARD_NETWORK          = 'card_network';
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

    const STATUS_CODE           = 'status_code';

    const BANK_REFERENCE        = 'bank_reference';

    protected static $sign      = 'bqr';

    protected $primaryKey = self::ID;

    protected $entity = 'bharat_qr';

    protected $fillable = [
        self::AMOUNT,
        self::METHOD,
        self::VPA,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
    ];

    protected $visible = [
        self::ID,
        self::EXPECTED,
        self::AMOUNT,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::METHOD,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::METHOD,
        self::BANK_REFERENCE,
        self::VIRTUAL_ACCOUNT_ID,
    ];

    protected $casts = [
        self::EXPECTED => 'bool',
        self::AMOUNT   => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
        self::BANK_REFERENCE,
    ];

    protected $pii = [
        self::VPA,
        self::CARD_NUMBER,
        self::CUSTOMER_NAME,
    ];

    protected $generateIdOnCreate = true;

    // ----------------------- Relations -----------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\virtualAccount\Entity', self::VIRTUAL_ACCOUNT_ID, self::ID);
    }

    // ----------------------- Public Setters -----------------------

    public function setPublicVirtualAccountIdAttribute(array & $array)
    {
        if (isset($array[self::VIRTUAL_ACCOUNT_ID]) === true)
        {
            $virtualAccountId = $array[self::VIRTUAL_ACCOUNT_ID];

            $array[self::VIRTUAL_ACCOUNT_ID] = VirtualAccount\Entity::getSignedId($virtualAccountId);
        }
    }

    public function setPublicPaymentIdAttribute(array & $array)
    {
        if (isset($array[self::PAYMENT_ID]) === true)
        {
            $paymentId = $array[self::PAYMENT_ID];

            $array[self::PAYMENT_ID] = Payment\Entity::getSignedId($paymentId);
        }
    }

    public function setPublicBankReferenceAttribute(array & $array)
    {
        $array[self::BANK_REFERENCE] = $this->getProviderReferenceId();
    }

    // ----------------------- Setters -----------------------

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    // ----------------------- Getters -----------------------

    public function getProviderReferenceId()
    {
        return $this->getAttribute(self::PROVIDER_REFERENCE_ID);
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

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function toArrayTrace(): array
    {
        $data = $this->toArray();

        foreach ($this->pii as $piiField)
        {
            if (isset($data[$piiField]) === false)
            {
                continue;
            }

            unset($data[$piiField]);
        }

        return $data;
    }
}
