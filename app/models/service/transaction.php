<?php 

namespace Models\Service;

use ERR;
use Models\DO;
use Models\DAL;

class Transaction
{
    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
        $card_token_do = null;

        $txn_input = $input;

        if (array_key_exists('token', $input))
        {
            $card_token_do = $this->loadToken($input['token']);
        }
        else 
        {
			list($token_input, $txn_input) = break_assoc_array(
											$input, 
											DO\CardToken::getCreateInputKeys(), 
											DO\Transaction::getCreateInputKeys());

            $token_service = new Token();

	        $card_token_do = $token_service->generate($token_input);
        }

        $txn_do = DO\Transaction::create($txn_input);

        $txn_do->setToken($card_token_do);

        $txn_db = DAL\Transaction::persist($txn_do);
        
        if ($txn_do->processNow())
        {
            $txn_do = $this->process($txn_do);
        }
        
        $txn_data = $txn_do->toArray();

        return $txn_data;
    }

    /**
     * Processes a transaction.
     * This function will be re-written.
     */
    public function process($txn)
    {
        if ($txno isntanceof DO\Transaction)
        {
            $gateway = new Gateway;
            $gateway->process($txn_do);
            $txn->setProcessed(1);

            return $txn_do;
        }
    }

    public function retrieve(array $input)
    {
        $txn_db = new DAL\Transaction;

        $txn_db->validateFetchParams($input);

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

    private function loadToken($token_input)
    {
        $card_token_do = new DO\CardToken;
        $card_token_db = new DAL\CardToken;

        $card_token_do->setToken($token_input);

        $card_token_db->fetchWithCard($card_token_do);

        array $card_token_do;
    }
}