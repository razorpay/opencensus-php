<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID         = 'payment_id';
    const MERCHANT_ID        = 'merchant_id';
    const PAYER_ACCOUNT      = 'payer_account';
    const PAYER_IFSC         = 'payer_ifsc';
    const PAYEE_ACCOUNT      = 'payee_account';
    const PAYEE_IFSC         = 'payee_ifsc';
    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';
    const AMOUNT             = 'amount';
    const MODE               = 'mode';
    const UTR                = 'utr';
    const TIME               = 'time';
    const DESCRIPTION        = 'description';
    const EXPECTED           = 'expected';
    const NOTIFIED           = 'notified';

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

    protected static $modifiers = [
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

    // ----------------------- Modifers ----------------------------------------

    // Kotak is sending us transaction_id instead of UTR
    // We unset this and set UTR early in the flow
    public function modifyUtr(& $input)
    {
        $input[self::UTR] = $input[self::REQ_UTR];

        unset($input[self::REQ_UTR]);
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
