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

    protected static $sign      = 'bhqr';

    protected $primaryKey = self::ID;

    protected $entity = 'bharat_qr';

    protected $fillable = [
        self::AMOUNT,
        self::GATEWAY_MERCHANT_ID,
        self::METHOD,
        self::VPA,
        self::CARD_NUMBER,
        self::CARD_NETWORK,
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
        self::EXPECTED,
        self::AMOUNT,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::GATEWAY_MERCHANT_ID,
        self::METHOD,
        self::VPA,
        self::RRN,
        self::CARD_NUMBER,
        self::CARD_NETWORK,
        self::TRANSACTION_TIME,
        self::TRANSACTION_DATE,
        self::PROVIDER_REFERENCE_ID,
        self::MERCHANT_REFERENCE,
        self::RRN,
        self::STATUS_CODE,
    ];

    protected $casts = [
        self::EXPECTED => 'bool',
        self::AMOUNT   => 'int',
    ];

    protected $publicSetters = [
        self::ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
    ];

    protected static $modifiers = [
        self::AMOUNT,
    ];

    protected $generateIdOnCreate = true;

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\virtualAccount\Entity', self::VIRTUAL_ACCOUNT_ID, self::ID);
    }

    // ----------------------- Public Setters ----------------------------------

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

    // -------------------------- Modifiers ------------------------------------

    public function modifyAmount(array & $input)
    {
        //
        // If you're wondering why this is here, run "(int) (579.3 * 100)" in tinker
        //
        // The value of (579.3 * 100) is actually stored as 57929.999... and casting
        // that to an integer just dumps the decimal part and ruins everything.
        //

        $input[self::AMOUNT] = (int) number_format(($input[self::AMOUNT] * 100), 0, '.', '');
    }

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

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

    public function getCardNumber()
    {
        return $this->getAttribute(self::CARD_NUMBER);
    }
}
