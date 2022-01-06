<?php

namespace RZP\Models\Transaction\Statement\Ledger\Journal;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Exception\LogicException;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\Ledger\Journal
 *
 *
 */
class Entity extends Base\PublicEntity
{
    protected static $sign = 'txn';

    protected $entity = 'journal';

    const MERCHANT_ID               = 'merchant_id';
    const AMOUNT                    = 'amount';
    const BASE_AMOUNT               = 'base_amount';
    const CURRENCY                  = 'currency';
    const TENANT                    = 'tenant';
    const TRANSACTOR_ID             = 'transactor_id';
    const TRANSACTOR_EVENT          = 'transactor_event';
    const TRANSACTION_DATE          = 'transaction_date';
    const TRANSACTOR_INTERNAL_ID    = 'transactor_internal_id';
    const TRANSACTOR_TYPE           = 'transactor_type';

    // Relation names/attributes
    const LEDGER_ENTRY      = 'ledger_entry';
    const SOURCE            = 'source';

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::BASE_AMOUNT,
        self::TENANT,
        self::TRANSACTOR_ID,
        self::TRANSACTOR_EVENT,
        self::TRANSACTION_DATE,
    ];

    protected $public = [
        self::ID,
        self::TENANT,
        self::TRANSACTOR_INTERNAL_ID,
        self::TRANSACTOR_TYPE,
        self::TRANSACTION_DATE,
        self::BASE_AMOUNT,
        self::AMOUNT,
        self::CURRENCY,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::TRANSACTION_DATE,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::BASE_AMOUNT,
    ];

    protected $casts = [
        self::AMOUNT              => 'int',
        self::BASE_AMOUNT         => 'int',
    ];

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getBaseAmount()
    {
        return $this->getAttribute(self::BASE_AMOUNT);
    }

    public function getTransactorId()
    {
        return $this->getAttribute(self::TRANSACTOR_ID);
    }

    public function getTransactorEvent()
    {
        return $this->getAttribute(self::TRANSACTOR_EVENT);
    }

    public function getTransactorType()
    {
        return $this->getAttribute(self::TRANSACTOR_TYPE);
    }

    public function getTransactorInternalId()
    {
        return $this->getAttribute(self::TRANSACTOR_INTERNAL_ID);
    }

    public function getSignedEntityId(): string
    {
        if (($this->getTransactorType() === null) or ($this->getTransactorInternalId() === null))
        {
            throw new LogicException('Unexpected method call, source entity has not been associated yet.');
        }

        $entityClass = Constants\Entity::getEntityClass($this->getTransactorType());

        return $entityClass::getSignedId($this->getEntityId());
    }
}
