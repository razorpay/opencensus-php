<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;
use Models\Service\TransactionTrace;

class Transaction extends Service
{
    protected $txn;

    public function __construct()
    {
        parent::__construct();
        $this->txn = new Core\Transaction();
    }

    /**
     * Processes a transaction.
     */
    public function process(array $input)
    {
        list($txn, $card) = $this->txn->create($input);

        $txn = $this->txn->process($txn, $card);

        if(!is_array($txn))
            $txn = $txn->toArray();

        return $txn;
    }

    public function retrieveMultiple(array $input)
    {
        $txn = new DAL\Transaction;

        $txn->validateFetchParams($input);

        $flag = DAL\Transaction::FETCH_WITH_CARD;
        
        $txn_do_arr = $txn_db->fetch(null, $flag);

        $txn_data_arr = array();
        $txn_data_arr['count'] = count($txn_do_arr);
        $txn_data_arr['data'] = array();

        foreach ($txn_do_arr as $txn_do)
        {
            array_push($txn_data_arr['data'], $txn_do->toArray($flag));
        }

        return $txn_data_arr;
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
        if($txn_data->getRefunded()) return;

        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        $status = $gateway->refund($data);

        if($status) $txn_data->setStatus('refunded');
    }

    /**
     * Captures a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function capture($txn_data = NULL)
    {
        //Don't continue if already captured
        if($txn_data->getCaptured()) return;

        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->capture($data);

        if($status) $txn_data->setStatus('captured');
        else
        {
            $txn_data->setStatus('capture_failed');
            $txn_data->setError($error);
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
            if(! $txn_data->checkIfHold())
            {
                $this->capture($txn_data);
            }
        }
        else
        {   
            $txn_data->setStatus('failed');
            $txn_data->setError($error);
        }


        return $txn_data;
    }
}