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
        $txn_do = new DO\Transaction();

        $card_token_do = null;

        $txn_input = $input;

        if (array_key_exists('token', $input))
        {
            $card_token_do = $this->loadToken($input['token']);
        }
        else 
        {
            list($token_input, $txn_input) = $this->separateTokenTxnInput($input);

            $card_token_do = $this->createToken($token_input);
        }

        $txn_do->setTokenDO($card_token_do);

        $txn_do->build($txn_input);

        $txn_db = new DAL\Transaction();
        
        $txn_db->insert($txn_do);

        $process_now = $txn_do->processNow();

        if ($process_now)
        {
            $txn_do = $this->process(null, $txn_do);

        }
        
        $flag = DO\Transaction::WITH_CARD |
                DO\Transaction::WITH_OBJECT_FIELD |
                DO\Transaction::ONLY_PUBLIC_FIELDS;
        
        $txn_data = $txn_do->getTransactionData($flag);

        return $txn_data;
    }

    /**
     * Processes a transaction.
     * This function will be re-written.
     */
    public function process($input, DO\Transaction $txn_do = null)
    {
        if (($input === null) and ($txn_do === null) or
            ($input !== null) and ($txn_do !== null))
            throw new \InvalidArgumentException('message');

        if (($input !== null) and
            (!is_array($input)))
            throw new \InvalidArgumentException('message');

        if ($txn_do !== null)
        {
            $gateway = new Gateway;
            $gateway->process($txn_do);
            $txn_do->setProcessed(1);

            return $txn_do;
        }
    }

    public function retrieve(array $input)
    {
        $txn_db = new DAL\Transaction;

        $txn_db->validate_fetch_params($input);

        $flag = DAL\Transaction::FETCH_WITH_CARD;
        
        $txn_do_arr = $txn_db->fetch(null, $flag);

        $txn_data_arr = array();
        $txn_data_arr['count'] = count($txn_do_arr);
        $txn_data_arr['data'] = array();

        foreach ($txn_do_arr as $txn_do)
        {
            array_push($txn_data_arr['data'], $txn_do->get_transaction_data($flag));
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

    private function separate_token_txn_input($input)
    {
        $token_input_keys = DO\CardToken::input_keys();
        
        $token_input = array();
        $txn_input = array();

        foreach ($input as $key => $value)
        {
            if (in_array($key, $token_input_keys))
            {
                $token_input[$key] = $value;
            }
            else
            {
                $txn_input[$key] = $value;
            }
        }

        $txn_input['merchant_id'] = BasicAuth::MerchantId();

        return array($token_input, $txn_input);
    }

    private function createToken($token_input)
    {
        $card_token_db = new DAL\CardToken;

        $token_service = new Token;

        $card_token_do = $token_service->buildToken($token_input);
     
        $card_token_db->insert($card_token_do);
        
        return $card_token_do;
    }
}