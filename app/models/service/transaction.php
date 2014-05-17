<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

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

    public function retrieveUncaptured($from_time = 86400)
    {
        $txn = new DAL\Transaction();

        $txn_array = $txn->fetchUncaptured($from_time);

        return $txn_array;
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
        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        $status = $gateway->refund($data);

        $txn_data->setRefunded($status);
    }

    /**
     * Captures a transaction
     * Pass \DAL\Transaction object as argument
     */

    public function capture($txn_data = NULL)
    {
        $data = array('txn' => $txn_data->toArrayEx(DAL\Transaction::WITH_CARD));

        if($txn_data->captured) return;

        $gateway = new GatewayManager();

        $status = $gateway->capture($data);

        $txn_data->setCaptured($status);
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
            $txn_data->setProcessed($processed);
        }
        else
        {
            $txn_data->setError($error);
        }

        if(! $txn_data->checkIfHold())
        {
            $this->capture($txn_data);
        }

        return $txn_data;
    }
}