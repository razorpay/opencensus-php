<?php

namespace Models\Transaction;

use EE\Exception;
use Models\Base;
use Models\Transaction;

class Repository extends Base\Repository
{
    protected $entity = 'Transaction';

    private static $fetchParamRules = array(
        'created'       => 'numeric',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required',
        'status'        => 'in:failed,captured,auth,open,refunded,settlement_sent,settled');

    /**
     * Retrieves the transactions from database for a particular merchant.
     * @param  array        $param
     * @return Collection   A collection of transactions
     */
    public function fetch($param)
    {
        if ($param === null)
        {
            throw new Exception\InvalidArgumentException('$param not provided');
        }
        self::validateFetchParams($param);

        $cols = array();

        /*
         * Create the query.
         */
        $repo = $this->repo;
        $query = $repo::where(Transaction\Entity::MERCHANT_ID, '=', $param['merchant_id'])
                     ->orderBy(Transaction\Entity::UPDATED_AT, 'desc');

        if (isset($param['from']))
        {
            $query = $query->where(Transaction\Entity::UPDATED_AT, '>=', $param['from']);
        }

        if (isset($param['to']))
        {
            $query = $query->where(Transaction\Entity::UPDATED_AT, '<=', $param['to']);
        }

        if (isset($param['status']))
        {
            $query = $query->where(Transaction\Entity::STATUS, '=', $param['status']);
        }

        if (isset($param['count']))
        {
            $query->take($param['count']);
        }
        else
        {
            $query->take(10);
        }

        if (isset($param['skip']))
        {
            $query->skip($param['skip']);
        }

        return $query->with('card')->get();
    }

    public static function validateFetchParams(array $param)
    {
        validate(self::$fetchParamRules, $param);
    }

    public function findByIdAndMerchantId($id, $merchantId, $failPublic = true)
    {
        $repo = $this->repo;

        $query = $repo::where(Transaction\Entity::MERCHANT_ID, $merchantId);

        if ($failPublic)
            return $query->findOrFailPublic($id);
        else
            return $query->findOrFail($id);
    }

    public function reloadAndLockForUpdate($txn)
    {
        $repo = $this->repo;

        $reloadedEntity = $repo::lockForUpdate()->findOrFail($txn->getKey());

        $attributes = $reloadedEntity->getAttributes();

        $txn->setRawAttributes($attributes, true);
    }

    public function lockForUpdate($id)
    {
        $repo = $this->repo;

        $repo::lockForUpdate()->findOrFail($id);
    }
}