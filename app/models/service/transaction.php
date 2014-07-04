<?php

namespace Models\Service;

use EE\Exception\BadRequestException;
use Models\Manager;
use Models\DAL;
use Trace\Trace;
use Trace\TraceCode;

class Transaction extends Service
{
    protected $txn;
    protected $trace;

    public function __construct()
    {
        parent::__construct();
        $this->core = new Core\Transaction();
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
        if($data instanceof DAL\Transaction)
            $data = $data->toArrayPublic();

        return $data;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new DAL\Transaction;

        $txns = $txn->fetch($input);

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
        Manager\Transaction::verifyIdAndStripSign($id);

        $txn = DAL\Transaction::findByIdAndMerchantIdOrFailPublic($id, $merchantId);

        //
        // Don't continue if already refunded
        //
        if($txn->isRefunded())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_REFUNDED);
        }

        if($txn->isCaptured() === false)
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
    public function bankAcsCallback($id, array $input)
    {
        Manager\Transaction::verifyIdAndStripSign($id);

        unset($input['csrf']);

        $txn = DAL\Transaction::findOrFail($id);

        $input['txn'] = $txn->toArray();

        $txn = $this->core->callback($txn, $input);

        return $txn->toArrayPublic();
    }
}
