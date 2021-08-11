<?php

namespace RZP\Models\UpiTransfer;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Feature;
use RZP\Models\VirtualAccount;

/**
 * @property VirtualAccount\Entity  $virtualAccount
 */
class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';
    const AMOUNT             = 'amount';

    // Details of the sender bank account
    const PAYER_BANK        = 'payer_bank';
    const PAYER_ACCOUNT     = 'payer_account';
    const PAYER_IFSC        = 'payer_ifsc';
    const PAYER_VPA         = 'payer_vpa';
    const PAYER_BANK_DETAIL = 'payer_bank_detail';

    const PAYEE_VPA           = 'payee_vpa';
    const GATEWAY             = 'gateway';
    const GATEWAY_MERCHANT_ID = 'gateway_merchant_id';

    // Bank reference number
    const PROVIDER_REFERENCE_ID = 'provider_reference_id';

    // NPCI reference number
    const NPCI_REFERENCE_ID = 'npci_reference_id';
    const RRN               = 'rrn';

    // Transaction reference number
    const TRANSACTION_REFERENCE = 'transaction_reference';
    const TR                    = 'tr';

    // Indicates whether the upi transfer corresponds
    // to an active virtual account on our side. If
    // false, this transfer will need to be refunded
    const EXPECTED          = 'expected';
    const UNEXPECTED_REASON = 'unexpected_reason';

    // Public alias for PROVIDER_REFERENCE_ID
    const BANK_REFERENCE   = 'bank_reference';
    const TRANSACTION_TIME = 'transaction_time';

    const VIRTUAL_ACCOUNT = 'virtual_account';

    protected static $sign = 'ut';

    protected $primaryKey = self::ID;

    protected $entity = Constants\Entity::UPI_TRANSFER;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::EXPECTED,
        self::AMOUNT,
        self::PAYER_VPA,
        self::PAYEE_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::GATEWAY,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::BANK_REFERENCE,
        self::TRANSACTION_TIME,
        self::GATEWAY_MERCHANT_ID,
        self::NPCI_REFERENCE_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
    ];

    protected $visible = [
        self::ID,
        self::EXPECTED,
        self::UNEXPECTED_REASON,
        self::AMOUNT,
        self::PAYER_VPA,
        self::PAYEE_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::GATEWAY,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::GATEWAY_MERCHANT_ID,
        self::TRANSACTION_TIME,
        self::BANK_REFERENCE,
        self::NPCI_REFERENCE_ID,
        self::PROVIDER_REFERENCE_ID,
        self::TRANSACTION_REFERENCE,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::PAYER_VPA,
        self::PAYER_BANK,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYMENT_ID,
        self::RRN,
        self::TR,
        self::VIRTUAL_ACCOUNT_ID,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::BANK_REFERENCE,
        self::RRN,
        self::TR,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
        self::EXPECTED => 'bool',
    ];

    protected $pii = [
        self::PAYEE_VPA,
        self::PAYER_ACCOUNT,
        self::PAYER_VPA,
    ];

    // ----------------------- Relations -----------------------

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    public function virtualAccount()
    {
        return $this->belongsTo(VirtualAccount\Entity::class, self::VIRTUAL_ACCOUNT_ID, self::ID);
    }

    // ----------------------- Setters -----------------------

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    public function setUnexpectedReason(string $unexpectedReason)
    {
        $this->setAttribute(self::UNEXPECTED_REASON, $unexpectedReason);
    }

    public function setPublicBankReferenceAttribute(array & $array)
    {
        $array[self::BANK_REFERENCE] = $array[self::PROVIDER_REFERENCE_ID];
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

    public function setPublicRrnAttribute(array & $array)
    {
        $array[self::RRN] = $array[self::NPCI_REFERENCE_ID];
    }

    public function setPublicTrAttribute(array & $array)
    {
        if(empty($array[self::TRANSACTION_REFERENCE]) === false)
        {
            $array[self::TR] = $array[self::TRANSACTION_REFERENCE];
        }
    }

// -------------------------- Getters --------------------------------------

    public function getMethod()
    {
        return Payment\Method::UPI;
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getVirtualAccountId()
    {
        return $this->getAttribute(self::VIRTUAL_ACCOUNT_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPayeeVpa()
    {
        return $this->getAttribute(self::PAYEE_VPA);
    }

    public function getPayerVpa()
    {
        return $this->getAttribute(self::PAYER_VPA);
    }

    public function getPayerIfsc()
    {
        return $this->getAttribute(self::PAYER_IFSC);
    }

    public function getPayerAccount()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    public function getBankReference()
    {
        return $this->getAttribute(self::PROVIDER_REFERENCE_ID);
    }

    public function getRrn()
    {
        return $this->getAttribute(self::NPCI_REFERENCE_ID);
    }

    public function getTr()
    {
        return $this->getAttribute(self::TRANSACTION_REFERENCE);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getUnexpectedReason()
    {
        return $this->getAttribute(self::UNEXPECTED_REASON);
    }

    public function getPii()
    {
        return $this->pii;
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

            switch ($piiField)
            {
                case self::PAYEE_VPA:
                    $payee_vpa = $data[self::PAYEE_VPA];

                    $data[self::PAYEE_VPA . '_root']    = explode('.', $payee_vpa)[0];
                    $data[self::PAYEE_VPA . '_dynamic'] = explode('@', explode('.', $payee_vpa)[1])[0];
                    $data[self::PAYEE_VPA . '_handle']  = explode('@', $payee_vpa)[1];

                    break;

                default:
                    break;
            }

            unset($data[$piiField]);
        }

        return $data;
    }
}
