<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;

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
        return $this->hasOne('RZP\Models\VirtualAccount\Entity');
    }

    // ----------------------- Generators --------------------------------------

    // Kotak is sending us transaction_id instead of UTR
    // We unset this and set UTR early in the flow
    public function generateUtr($input)
    {
        $this->setAttribute(self::UTR, $input[self::REQ_UTR]);
    }

    // ----------------------- Getters -----------------------------------------

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
