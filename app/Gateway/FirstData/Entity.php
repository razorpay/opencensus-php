<?php

namespace RZP\Gateway\FirstData;

use RZP\Gateway\Base;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                        = 'id';
    const AMOUNT                    = 'amount';
    const TXNDATETIME               = 'txndatetime';
    const HASH                      = 'hash';
    const OID                       = 'oid';
    const TDATE                     = 'tdate';
    const PAYMENT_METHOD            = 'paymentMethod';

    const REFNUMBER                 = 'refnumber';
    const STATUS                    = 'status';
    const TXNDATE_PROCESSED         = 'txndate_processed';
    const RESPONSE_HASH             = 'response_hash';
    const PROCESSOR_RESPONSE_CODE   = 'processor_response_code';
    const TERMINAL_ID               = 'terminal_id';

    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';

    protected $fields = array(
        self::ID,
        self::PAYMENT_ID,
        self::REFUND_ID,
        self::RECEIVED,
        self::ACTION,
        self::AMOUNT,
        self::TXNDATETIME,
        self::HASH,
        self::OID,
        self::REFNUMBER,
        self::STATUS,
        self::RESPONSE_HASH,
        self::PROCESSOR_RESPONSE_CODE,
        self::TERMINAL_ID,
        self::TDATE,
        self::PAYMENT_METHOD,
        self::CREATED_AT,
        self::UPDATED_AT,

        );

    protected $fillable = array(
        self::PAYMENT_ID,
        self::RECEIVED,
        self::REFUND_ID,
        self::ACTION,
        self::AMOUNT,
        self::TXNDATETIME,
        self::HASH,
        self::OID,
        self::REFNUMBER,
        self::STATUS,
        self::RESPONSE_HASH,
        self::PROCESSOR_RESPONSE_CODE,
        self::TERMINAL_ID,
        self::TDATE,
        self::PAYMENT_METHOD,
        );

    protected $guarded = array();

    protected $table = Constants\Table::FIRST_DATA;

    protected $primaryKey = self::ID;

    protected $entity = Constants\Table::FIRST_DATA;

    public $incrementing = true;

    protected $guarded = array();

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getAmount()
    {
        return (int) $this->getAttribute(self::AMOUNT);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

}
