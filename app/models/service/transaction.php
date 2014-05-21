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