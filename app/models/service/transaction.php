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
        	$input + array('message' => 'New Transaction Requested'));

        list($txn, $cardData) = $this->txn->create($input);

        $this->trace->debug(
        	TraceEvent::TRANSACTION_CREATED, 
        	$txn->toArray() + array('message' => 'New Transaction Created'));

        $txn = $this->txn->process($txn, $cardData);

        if(! is_array($txn))
            $txn = $txn->toArray();

        return $txn;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new DAL\Transaction;

        $txn_data_arr = $txn->fetch($input);

        $count = count($txn_data_arr);

        return array('count' => $count, 'data' => $txn_data_arr->toArray());
    }

    public function retrieve($id = null)
    {
        Manager\Transaction::validateTransactionId($id);

        $txn = new DAL\Transaction();

        $txn_data = $txn->fetchById($id);

        return $txn_data;
    }

    /**
     * Refunds a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function refund($txn_data = NULL)
    {
        //Don't continue if already refunded

        if($txn_data->getRefunded())
        {
            $txn_data->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);
            return;
        }
        if(! $txn_data->getCaptured())
        {
            $txn_data->setError([
                'code' => 'RP00002',
                'message' => 'Uncaptured Transaction'
            ]);
            return;
        }
        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->refund($data);

        if($status){
            $txn_data->setStatus('refunded');

            //Logging
            $this->trace->info(
            	TraceEvent::TRANSACTION_REFUNDED, 
            	$txn_data->toArray() + array('message' => 'Transaction Refunded'));
        }
        else
        {
            $txn_data->setError($error);

            //Logging
            $this->trace->error(
            	TraceEvent::TRANSACTION_FAILED, 
            	$txn_data->toArray() + array('message' => 'Transaction Refund Request Failed'));
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
            	$txnData->toArray() + array('message' => 'Transaction Captured'));
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

        $txn_data = $txn->fetchById($id);

        if ($processed === true)
        {
            $txn_data->setStatus(Manager\TransactionStatus::AUTH);

            //Logging
            $this->trace->info(
            	TraceEvent::TRANSACTION_AUTHED, 
            	$txn_data->toArray() + array('message' => 'Transaction Auth Successfull'));

            // if(! $txn_data->getHold())
            // {
            //     $this->capture($txn_data);
            // }
        }
        else
        {
            $txn_data->setStatus(Manager\TransactionStatus::FAILED);
            $txn_data->setError($error);

            //Logging
            $this->trace->error(
            	TraceEvent::TRANSACTION_FAILED, 
            	$txn_data->toArray() + array('message' => 'Transaction Auth Failed'));
        }


        return $txn_data;
    }
}
