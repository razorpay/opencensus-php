<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID     = 'payment_id';
    const PAYER_ACCOUNT  = 'payer_account';
    const PAYER_IFSC     = 'payer_ifsc';
    const PAYEE_ACCOUNT  = 'payee_account';
    const PAYEE_IFSC     = 'payee_ifsc';
    const AMOUNT         = 'amount';
    const MODE           = 'mode';
    const TRANSACTION_ID = 'transaction_id';
    const TIME           = 'time';
    const DESCRIPTION    = 'description';

    protected $fillable = array(
        self::PAYMENT_ID,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::MODE,
        self::TRANSACTION_ID,
        self::TIME,
        self::AMOUNT,
        self::DESCRIPTION,
    );

    protected $public = array(
        self::ID,
        self::PAYMENT_ID,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::AMOUNT,
        self::MODE,
        self::TRANSACTION_ID,
        self::TIME,
        self::DESCRIPTION,
    );

    protected $casts = [
        self::AMOUNT => 'int',
    ];

    protected $entity = Constants\Entity::BANK_TRANSFER;


    // ----------------------- Associations ----------------------------------------

    public function payment()
    {
        return $this->belongsTo(
            'RZP\Models\Payment\Entity', self::PAYMENT_ID);
    }

    // ----------------------- Getters ---------------------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getPayeeAccount()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT);
    }

    public function getPayeeIfsc()
    {
        return $this->getAttribute(self::PAYEE_IFSC);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setAmount($amount)
    {
        return $this->setAttribute(self::AMOUNT, $amount);
    }
}
