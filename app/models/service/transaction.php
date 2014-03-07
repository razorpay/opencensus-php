<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;

class Transaction extends Service
{
    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
    	$card_token = null;

        $txn_input = $input;
        
        if (array_key_exists('token', $input))
        {
        	if (array_key_exists('merchant_id', $input))
        	{
            	$card_token = DAL\CardToken::findByTokenAndMerchantId($input['token'], $input['merchant_id']);
            }
            else
            {
            	throw new \InvalidArgumentException('merchant_id not found');
            }

            if ($card_token->expired())
            {
            	throw new \LogicException('token already used');
            }
        }
        else
        {
			list($token_input, $txn_input) = Manager\Transaction::separateTokenTxnInput($input);

            $card_token = Token::getNewInstance()->generate($token_input);

	        $txn_input['token'] = $card_token->getToken();
        }

        $data = Manager\Transaction::createValidate($txn_input)->getData();

        $txn = DAL\Transaction::create($data);

        if ($input['process'] == '1')
        {
            $txn = $this->process($txn);
        }

        $txn_data = $txn->toArray();

        return $txn_data;
    }

    /**
     * Processes a transaction.
     * This function will be re-written.
     */
    public function process($txn)
    {
        if ($txn instanceof DAL\Transaction)
        {
            $gateway = new Gateway;
            $gateway->process($txn);
            $txn->setProcessed(1);

            return $txn;
        }
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
}