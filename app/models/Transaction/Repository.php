<?php

namespace Models\Transaction;

use EE\Exception;
use Models\Base;

class Repository extends Base\Repository
{
    private static $fetch_param_rules = array(
        'created'       => 'numeric',
        'from'          => 'numeric',
        'to'            => 'numeric',
        'count'         => 'numeric|max:100',
        'skip'          => 'numeric',
        'merchant_id'   => 'required',
        'status'        => 'in:failed,captured,capture_failed,auth,open,refunded,settlement_sent,settled');

        /**
     * Retrieves the transactions from database for a particular merchant.
     * @param  array        $param
     * @return Collection   A collection of transactions
     */
    public static function fetch($param)
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
        $query = $repo::where(self::MERCHANT_ID, '=', $param['merchant_id'])
                     ->orderBy(self::UPDATED_AT, 'desc');

        if (isset($param['from']))
        {
            $query = $query->where(self::UPDATED_AT, '>=', $param['from']);
        }

        if (isset($param['to']))
        {
            $query = $query->where(self::UPDATED_AT, '<=', $param['to']);
        }

        if (isset($param['status']))
        {
            $query = $query->where(self::STATUS, '=', $param['status']);
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

        return $query->with('token.card')->get();
    }

    public static function validateFetchParams(array $param)
    {
        validate(self::$fetch_param_rules, $param);
    }

    public static function loadWithTokenAndCard($id, $merchantId)
    {
        $repo = $this->repo;

        $txn = $repo::with('token')
                   ->merchantId($merchantId)
                   ->where(self::ID, '=', $id)
                   ->first();

        if (($txn !== null) and
            ($txn->token !== null))
        {
            $card = $txn->token->card()->first();
        }

        return $txn;
    }
}