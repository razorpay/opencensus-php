<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID                           = 'id';
    const TRANSACTION_ID               = 'transaction_id';
    const NAME                         = 'name';
    const PERCENTAGE                   = 'percentage';
    const AMOUNT                       = 'amount';
    const TYPE                         = 'type';

    const NAME_LENGTH                  = 100;
    const TYPE_LENGTH                  = 20;

    protected $table = Table::FEE_BREAKUP;

    protected static $sign = 'fees';

    protected $entity = 'fee_breakup';

    protected $generateIdOnCreate = true;

    protected $fillable = array(
        self::NAME,
        self::TYPE,
        self::AMOUNT,
        self::PERCENTAGE,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::TYPE,
        self::AMOUNT,
        self::PERCENTAGE,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    );

    protected $casts = array(
        self::AMOUNT                        => 'int',
        self::PERCENTAGE                    => 'int',
    );

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }


    // ----------------------- Getters ---------------------------------------------

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getPercentage()
    {
        return $this->getAttribute(self::PERCENTAGE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }


    // ----------------------- Setters ---------------------------------------------

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setPercentage($percentage)
    {
        $this->setAttribute(self::PERCENTAGE, $percentage);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $typ);
    }

    public function setCreatedAt($createdAt)
    {
        $this->setAttribute(self::CREATED_AT, $createdAt);
    }

}
