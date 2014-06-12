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

        if($txnData->isRefunded())
        {
            $txnData->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);
            return;
        }

        if(! $txnData->isCaptured())
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

            $txn_arr = $txnData->toArray();

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_REFUNDED, 
                $txn_arr);

            //Analytics
            $txn_arr['merchant_id'] = $txnData->merchant_id;
            \Dashboard\Transaction::getInstance()->queueRecord($txn_arr);
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
    public function capture($id)
    {
        $txn = DAL\Transaction::findOrFail($id);

        //
        // Don't continue if already captured
        // 
        if ($txn->isCaptured())
        {
            $txn->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);

            return;
        }

        $txn = $this->txn->capture($txn);

        $merchant_id = $txn->merchant_id;

        return $txn->toArray();
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
