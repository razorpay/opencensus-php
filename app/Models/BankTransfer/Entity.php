<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\VirtualAccount;

class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const MERCHANT_ID        = 'merchant_id';

    // Details of the sender bank account
    const PAYER_ACCOUNT      = 'payer_account';
    const PAYER_IFSC         = 'payer_ifsc';

    // Details of the receiver bank account
    const PAYEE_ACCOUNT      = 'payee_account';
    const PAYEE_IFSC         = 'payee_ifsc';

    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';
    const AMOUNT             = 'amount';

    // Modes: NEFT, RTGS, IMPS, IFT
    const MODE               = 'mode';

    // Bank reference number
    const UTR                = 'utr';

    // Time of transaction
    const TIME               = 'time';

    // Remarks field
    const DESCRIPTION        = 'description';

    // Indicates whether the bank transfer corresponds
    // to an active virtual account on our side. If
    // false, this transfer will need to be refunded
    const EXPECTED           = 'expected';

    // All entities are created and process in the bank transfer process flow.
    // In the notify flow, we simply mark the bank transfer as a confirmed one.
    const NOTIFIED           = 'notified';

    // Original request contains this key as input, it is actually the UTR.
    // This is used to generate the value for the UTR field.
    const REQ_UTR            = 'transaction_id';

    // The IFSC we receive in the process bank_transfer API is often a mocked one.
    // This is the key we show the merchant, as the bank can be derived from the IFSC.
    const PAYER_BANK         = 'payer_bank';

    protected $fillable = [
        self::PAYMENT_ID,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::MODE,
        self::UTR,
        self::TIME,
        self::AMOUNT,
        self::DESCRIPTION,
    ];

    protected $public = [
        self::ID,
        self::UTR,
        self::MODE,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
        self::PAYER_ACCOUNT,
        self::PAYER_BANK,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
        self::EXPECTED => 'bool',
        self::NOTIFIED => 'bool',
    ];

    protected static $generators = [
        self::UTR,
    ];

    protected $defaults = [
        self::EXPECTED => false,
        self::NOTIFIED => false,
    ];

    protected $publicSetters = [
        self::ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
        self::PAYER_ACCOUNT,
        self::PAYER_BANK,
    ];

    protected static $sign = 'bt';

    protected $entity = Constants\Entity::BANK_TRANSFER;

    protected $generateIdOnCreate = true;


    // ----------------------- Associations ------------------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\VirtualAccount\Entity');
    }

    // ----------------------- Generators --------------------------------------

    // Kotak is sending us transaction_id instead of UTR
    // We unset this and set UTR early in the flow
    public function generateUtr($input)
    {
        $this->setAttribute(self::UTR, $input[self::REQ_UTR]);
    }

    // ----------------------- Getters -----------------------------------------

    public function setPublicPayerAccountAttribute(array & $array)
    {
        $ac = $array[self::PAYER_ACCOUNT];

        // Get account number in redacted form\
        $repeat = ceil((strlen($ac) - 4) / 4);

        $array[self::PAYER_ACCOUNT] = str_repeat('XXXX-', $repeat) . substr($ac, -4);
    }

    public function setPublicPayerBankAttribute(array & $array)
    {
        $ifsc = $array[self::PAYER_IFSC];

        $array[self::PAYER_BANK] = substr($ifsc, 0, 4);
    }

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

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getPayeeAccount()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT);
    }

    public function getPayeeIfsc()
    {
        return $this->getAttribute(self::PAYEE_IFSC);
    }

    public function getPayerAccount()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT);
    }

    public function getPayerIfsc()
    {
        return $this->getAttribute(self::PAYER_IFSC);
    }

    public function isNotified()
    {
        return $this->getAttribute(self::NOTIFIED);
    }

    // ----------------------- Setters -----------------------------------------

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    public function setNotified(bool $notified)
    {
        $this->setAttribute(self::NOTIFIED, $notified);
    }
}
