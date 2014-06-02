<?php

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Trace\TransactionTrace;

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
        //Logging
        $this->trace->debug(TransactionTrace::NEW_TRANSACTION_REQUEST, $input + array('message' => 'New Transaction Requested'));

        list($txn, $card) = $this->txn->create($input);

        //Logging
        $this->trace->debug(TransactionTrace::TRANSACTION_CREATED, $txn->toArray() + array('message' => 'New Transaction Created'));

        $txn = $this->txn->process($txn, $card);

        // Auto-capture if hold set to false for credit cards
        // if ($txn instanceof \Models\DAL\Transaction && $txn->unsetAndGetCapturable())
        //     $this->capture($txn);

        if(!is_array($txn))
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

    public function retrieve($id = NULL)
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
            $this->trace->info(TransactionTrace::TRANSACTION_REFUNDED, $txn_data->toArray() + array('message' => 'Transaction Refunded'));
        }
        else
        {
            $txn_data->setError($error);

            //Logging
            $this->trace->error(TransactionTrace::TRANSACTION_REFUND_FAILED, $txn_data->toArray() + array('message' => 'Transaction Refund Request Failed'));
        }
    }

    /**
     * Captures a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function capture($txn_data = NULL)
    {
        //Don't continue if already captured
        if($txn_data->getCaptured())
        {
            $txn_data->setError([
                'code' => 'FSS00002',
                'message' => 'Duplicate Transaction Request'
            ]);
            return;
        }

        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->capture($data);

        if($status)
        {
            $txn_data->setStatus('captured');

            //Logging
            $this->trace->info(TransactionTrace::TRANSACTION_CAPTURED, $txn_data->toArray() + array('message' => 'Transaction Captured'));
        }
        else
        {
            $txn_data->setStatus('capture_failed');
            $txn_data->setError($error);

            //Logging
            $this->trace->error(TransactionTrace::TRANSACTION_CAPTURE_FAILED, $txn_data->toArray() + array('message' => 'Transaction Capture Request Failed'));
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
            $txn_data->setStatus('auth');

            //Logging
            $this->trace->info(TransactionTrace::TRANSACTION_AUTHED, $txn_data->toArray() + array('message' => 'Transaction Auth Successfull'));

            // if(! $txn_data->getHold())
            // {
            //     $this->capture($txn_data);
            // }
        }
        else
        {
            $txn_data->setStatus('failed');
            $txn_data->setError($error);

            //Logging
            $this->trace->error(TransactionTrace::TRANSACTION_FAILED, $txn_data->toArray() + array('message' => 'Transaction Auth Failed'));
        }


        return $txn_data;
    }
}
