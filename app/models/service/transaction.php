<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Trace\TransactionTrace;
use Trace\TraceEvent;

class Transaction extends Service
{
    protected $txn;
    protected $trace;

    public function __construct()
    {
        parent::__construct();
        $this->txn = new Core\Transaction();
        $this->trace = new TransactionTrace();
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        $this->trace->debug(
            TraceEvent::NEW_TRANSACTION_REQUEST, 
            $input);

        list($txn, $cardData) = $this->txn->create($input);

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

    public function retrieve($id = null)
    {
        Manager\Transaction::validateTransactionId($id);

        $txn = new DAL\Transaction();

        $txnData = $txn->fetchById($id);

        return $txnData;
    }

    /**
     * Refunds a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function refund($txnData = NULL)
    {
        //Don't continue if already refunded

        if($txnData->getRefunded())
        {
            $txnData->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);
            return;
        }
        if(! $txnData->getCaptured())
        {
            $txnData->setError([
                'code' => 'RP00002',
                'message' => 'Uncaptured Transaction'
            ]);
            return;
        }
        $data = array('txn' => $txnData->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->refund($data);

        if($status){
            $txnData->setStatus('refunded');

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_REFUNDED, 
                $txnData->toArray());
        }
        else
        {
            $txnData->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_FAILED, 
                $txnData->toArray() + array('message' => 'Transaction Refund Request Failed'));
        }
    }

    /**
     * Captures a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function capture($txnData = null)
    {
        //Don't continue if already captured
        if($txnData->getCaptured())
        {
            $txnData->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);
            return;
        }

        $data = array('txn' => $txnData->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->capture($data);

        if($status)
        {
            $txnData->setStatus(Manager\TransactionStatus::CAPTURED);

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_CAPTURED, 
                $txnData->toArray());
        }
        else
        {
            $txnData->setStatus('capture_failed');
            $txnData->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_FAILED,
                $txnData->toArray() + array('message' => 'Transaction Capture Request Failed'));
        }
    }

    public function bankAcsCallback(array $input)
    {
        unset($input['csrf']);

        $gateway = new GatewayManager();

        list($processed, $id, $error) = $gateway->bankAcsCallback($input);

        $txn = new DAL\Transaction();

        $txnData = $txn->fetchById($id);

        if ($processed === true)
        {
            $txnData->setStatus(Manager\TransactionStatus::AUTH);

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_AUTHED, 
                $txnData->toArray());

            // if(! $txnData->getHold())
            // {
            //     $this->capture($txnData);
            // }
        }
        else
        {
            $txnData->setStatus(Manager\TransactionStatus::FAILED);
            $txnData->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_FAILED, 
                $txnData->toArray() + array('message' => 'Transaction Auth Failed'));
        }


        return $txnData;
    }
}
