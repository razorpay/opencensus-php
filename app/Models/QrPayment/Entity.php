<?php

namespace RZP\Models\QrPayment;

use App;
use RZP\Models\Base;
use RZP\Models\BankAccount;

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
    const PAYER_BANK_ACCOUNT_ID = 'payer_bank_account_id';

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
        self::PAYER_BANK_ACCOUNT_ID,
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
        self::PAYER_BANK_ACCOUNT_ID,
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
        self::PAYER_BANK_ACCOUNT_ID,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $pii = [
        self::PAYER_VPA
    ];

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getMerchantReference()
    {
        return $this->getAttribute(self::MERCHANT_REFERENCE);
    }

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function qrCode()
    {
        return $this->belongsTo('RZP\Models\QrCode\NonVirtualAccountQrCode\Entity', self::QR_CODE_ID, self::ID);
    }

    public function payerBankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', self::PAYER_BANK_ACCOUNT_ID, BankAccount\Entity::ID);
    }

    public function setExpected(bool $paymentExpected)
    {
        $this->setAttribute(self::EXPECTED, $paymentExpected);
    }

    public function setUnexpectedReason($unexpectedReason)
    {
        $this->setAttribute(self::UNEXPECTED_REASON, $unexpectedReason);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getProviderReferenceId()
    {
        return $this->getAttribute(self::PROVIDER_REFERENCE_ID);
    }

    public function toArrayTrace(): array
    {
        $data = $this->toArray();

        foreach ($this->pii as $piiField)
        {
            unset($data[$piiField]);
        }

        return $data;
    }
}
