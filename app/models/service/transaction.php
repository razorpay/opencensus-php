<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Trace\Trace;
use Trace\TraceCode;
use EE\Exception\BadRequestException;
use Models\Manager\TransactionAction;
use Models\Manager\TransactionStatus;

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

        if($data instanceof DAL\Transaction)
            $data = $data->toArray();

        return $data;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new DAL\Transaction;

        $txns = $txn->fetch($input);

        $count = count($txns);

        return array('count' => $count, 'data' => $txns->toArray());
    }

    public function retrieve($id, $merchantId)
    {
        Manager\UniqueId::verifyUid($id, true);

        $txn = DAL\Transaction::findByIdAndMerchantId($id, $merchantId);

        if ($txn !== null)
            $txn = $txn->toArray();

        return $txn;
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
        $txn = DAL\Transaction::findByIdAndMerchantId($id, $merchantId);

        if ($txn === null)
            return null;

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

        return $txn->toArray();
    }

    /**
     * Captures a transaction
     *
     * @param  string   $id
     * @param  integer  $merchantId
     * @return DAL\Transaction
     */
    public function capture($id, $merchantId)
    {
        $txn = DAL\Transaction::findByIdAndMerchantId($id, $merchantId);

        if ($txn === null)
            return;

        //
        // Don't continue if already captured
        //
        if ($txn->isCaptured())
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_TRANSACTION_ALREADY_CAPTURED);
        }

        $txn = $this->core->capture($txn);

        return $txn->toArray();
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing. Next step is auth.
     *
     * @param  array  $input Contains fields provided
     *                       by bank
     *
     * @return DAL\Transaciton
     */
    public function bankAcsCallback($id, array $input)
    {
        unset($input['csrf']);

        $txn = DAL\Transaction::findOrFail2($id);

        $input['txn'] = $txn->toArray();

        $txn = $this->core->callback($txn, $input);

        return $txn;
    }
}
