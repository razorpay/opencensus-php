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

        return $gateway->refund($data);
    }

    public function bankAcsCallback(array $input)
    {
        unset($input['csrf']);
        
        $gateway = new GatewayManager();

        list($processed, $id) = $gateway->bankAcsCallback($input);

        if ($processed)
        {
            $txn = DAL\Transaction::updateProcessed($id);

            return "Transaction successful";
        }
        else
        {
            return "Transaction unsuccessful";
        }
    }
}