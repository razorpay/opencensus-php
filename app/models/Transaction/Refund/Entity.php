<?php

namespace Models\Transaction\Refund;

use Models\Base;
use Models\Transaction;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const TRANSACTION_ID    = 'transaction_id';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const LEDGER_ID         = 'ledger_id';

    protected $table = \Constants\Table::REFUND;

    protected static $sign = 'rfnd';

    protected $entity = 'refund';

    protected $genereateIdOnCreate = true;

    protected static $generators = array(self::AMOUNT, self::CURRENCY);

    protected $fillable = array(
        self::MERCHANT_ID,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::AMOUNT,
        self::CURRENCY,
        self::TRANSACTION_ID,
        self::CREATED_AT);

    protected $publicSetters = array(
        self::ID, self::ENTITY, self::TRANSACTION_ID);

    public function transaction()
    {
        return $this->belongsTo('Models\Transaction\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('Models\Merchant\Entity');
    }

    public function build(array $input = array())
    {
        $transaction = func_get_arg(1);

        $this->transaction()->associate($transaction);

        $this->getValidator()->setTransaction($transaction);

        return parent::build($input);
    }

    protected function generateAmount($input)
    {
        if (isset($input['amount']) === false)
        {
            $this->setAttribute(self::AMOUNT, $this->transaction->getAmountUnrefunded());
        }
    }

    protected function generateCurrency($input)
    {
        $this->setAttribute(self::CURRENCY, $this->transaction->getCurrency());
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function setPublicTransactionIdAttribute(array & $array)
    {
        $array[self::TRANSACTION_ID] =
            Transaction\Entity::getSign() . static::$delimiter . $this->getAttribute(self::TRANSACTION_ID);
    }
}
