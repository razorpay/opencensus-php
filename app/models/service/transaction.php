<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Trace\Trace;
use Trace\TraceEvent;

class Transaction extends Service
{
    protected $txn;
    protected $trace;

    public function __construct()
    {
        parent::__construct();
        $this->txn = new Core\Transaction();
        $this->trace = Trace::getInstance();
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        $this->trace->debug(
            TraceEvent::TRANSACTION_NEW_REQUEST,
            $input);

        list($txn, $cardData) = $this->txn->createEntitites($input);

        $this->trace->debug(
            TraceEvent::TRANSACTION_CREATED,
            $txn->toArray());

        $txn = $this->txn->process($txn, $cardData);

        if(! is_array($txn))
            $txn = $txn->toArray();

        return $txn;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new DAL\Transaction;

        $txnDataArr = $txn->fetch($input);

        $count = count($txnDataArr);

        return array('count' => $count, 'data' => $txnDataArr->toArray());
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
            throw new BadRequestException('This transaction has already been refunded');
        }

        if($txn->isCaptured() === false)
        {
            throw new BadRequestException('This transaction has already been captured');
        }

        $txn = $this->txn->refund($txn);

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
            throw new BadRequestException('This transaction has already been captured');
        }

        $txn = $this->txn->capture($txn);

        return $txn->toArray();
    }

    public function bankAcsCallback(array $input)
    {
        unset($input['csrf']);

        $gateway = new GatewayManager();

        list($processed, $id, $error) = $gateway->bankAcsCallback($input);

        $txn = DAL\Transaction::findOrFail2($id);

        $txnArray = $txn->toArray();

        if ($processed === true)
        {
            $txn->setStatus(Manager\TransactionStatus::AUTH);

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_AUTHED,
                $txnArray);

            // if(! $txnData->getHold())
            // {
            //     $this->capture($txnData);
            // }
        }
        else
        {
            $txn->setStatus(Manager\TransactionStatus::FAILED);
            $txn->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_AUTH_FAILED,
                $txnArray);
        }

        return $txn;
    }
}
