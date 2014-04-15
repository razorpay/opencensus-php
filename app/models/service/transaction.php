<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;

class TransactionService extends Service
{
	protected $txn;

	public function __construct()
	{
		parent::construct();
		$this->txn = new Transaction\Base();
	}

    /**
     * Processes a transaction.
     */
    public function transact(array $input)
    {
    	list($txn, $card) = $this->txn->create($input);

    	$this->txn->process($txn, $card);

        return $txn;
    }

    public function capture($input)
    {
    	;
    }

    public function retrieve(array $input)
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

    public function bankAcsCallback(array $input)
    {
    	unset($input['csrf']);
    	
    	$gateway = new GatewayManager();

    	list($processed, $id) = $gateway->bankAcsCallback($input);

    	if ($processed)
    	{
    		$txn = DAL\Transaction::updateProcessed($id);
			echo "Transaction successful";
    	}
    	else
    	{
    		echo "Transaction unsuccessful";
    	}
    }
}