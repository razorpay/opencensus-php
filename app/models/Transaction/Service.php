<?php

namespace Models\Transaction;

use EE\Exception\BadRequestException;

use Models\Base;
use Models\Transaction;

use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    protected $txn;
    protected $trace;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Transaction\Core();
        $this->trace = Trace::getInstance();
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        $this->trace->debug(
            TraceCode::TRANSACTION_NEW_REQUEST,
            $input);

        list($txn, $cardData) = $this->core->createEntitites($input);

        $this->trace->debug(
            TraceCode::TRANSACTION_CREATED,
            $txn->toArray());

        $data = $this->core->process($txn, $cardData);

        //
        // The returned value could be either Transaction
        // model or an array containing callback data.
        // We convert txn model to array
        // if it's a txn model
        //
        if ($data instanceof Transaction\Entity)
            $data = $data->toArrayPublic();

        return $data;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new Transaction\Entity;

        $txns = (new Transaction\Repository)->fetch($input);

        $count = count($txns);

        $collection = $txns->transform(function($txn)
        {
            return $txn->toArrayPublic();
        });
        $txns = $collection->all();

        return array('count' => $count, 'data' => $txns);
    }

    public function retrieveById($id, $merchantId)
    {
        $txn = $this->core->retrieveTransaction($id, $merchantId);

        return $txn->toArrayPublic();
    }

    /**
     * Refunds a transaction
     *
     * @param  string   $id
     * @param  integer  $merchantId
     * @return DAL\Transaction
     */
    public function refund($id, $merchantId)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        $txn = (new Transaction\Repository)->findByIdAndMerchantIdOrFailPublic($id, $merchantId);

        //
        // Don't continue if already refunded
        //
        if ($txn->isRefunded())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_REFUNDED);
        }

        if ($txn->isCaptured() === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED);
        }

        $txn = $this->core->refund($txn);

        return $txn->toArrayPublic();
    }

    /**
     * Captures a transaction
     *
     * @param  string   $id
     * @param  integer  $merchantId
     * @return DAL\Transaction
     */
    public function capture($id, $merchantId, $input)
    {
        $txn = $this->core->retrieveTransaction($id, $merchantId);

        $txn = $this->core->capture($txn, $input);

        return $txn->toArrayPublic();
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return DAL\Transaciton
     */
    public function bankAcsCallback($id, $merchantId, array $input)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($input['csrf']);

        $txn = (new Transaction\Repository)->findOrFail($id);

        $input['txn'] = $txn->toArray();

        $txn = $this->core->callback($txn, $input);

        return $txn->toArrayPublic();
    }
}
