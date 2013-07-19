<?php 

namespace Service;

use ERR;
use DomainObject\Transaction as TransactionDO;
use DataMapper\Transaction as TransactionDB;
use DomainObject\CardToken as CardTokenDO;
use DataMapper\CardToken as CardTokenDB;

class Transaction
{

    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null, $merchant_id)
    {
        $txn_do = new TransactionDO();

        $card_token_do = null;
        $txn_input = $input;

        if (isset($input['token']))
        {
            list($card_token_do, $err) = $this->load_token($input['token']);

            if ($err !== ERR::SUCCESS)
                return array(false, $err);
        }
        else 
        {
            list($token_input, $txn_input) = $this->separate_token_txn_input($input);
            list($card_token_do, $err) = $this->create_token($token_input);
        }

        $txn_do->set_token_do($card_token_do);

        $data['merchant_id'] = BasicAuth::MerchantId();

        $err = $txn_do->build($data, $txn_input);

        if ($err !== ERR::SUCCESS)
        {
            return array(false, $err);
        }

        $txn_db = new TransactionDB;
        $err = $txn_db->insert($txn_do);

        $process_now = $txn_do->process_now();

        if ($process_now)
        {
            list($txn_do, $err) = $this->process(null, $txn_do);

            if ($err !== ERR::SUCCESS)
                return array(false, $err);
        }
        
        $flag = TransactionDO::WITH_CARD |
                TransactionDO::WITH_OBJECT_FIELD |
                TransactionDO::ONLY_PUBLIC_FIELDS;
        
        $txn_data = $txn_do->get_transaction_data($flag);

        return array($txn_data, ERR::SUCCESS);;
    }

    /**
     * Processes a transaction.
     * This function will be re-written.
     */
    public function process($input, TransactionDO $txn_do = null)
    {
        if (($input === null) and ($txn_do === null) or
            ($input !== null) and ($txn_do !== null))
            return ERR::INVALID_PARAMETERS;

        if (($input !== null) and
            (!is_array($input)))
            return array(false, ERR::INVALID_PARAMETERS);

        if ($txn_do !== null)
        {
            $gateway = new Gateway;
            $gateway->process($txn_do);
            $txn_do->set_processed(1);
            return array($txn_do, ERR::SUCCESS);
        }
    }

    public function retrieve($input)
    {
        
    }

    private function load_token($token_input)
    {
        $token_input = $input['token'];
        
        $card_token_do = new CardTokenDO;
        $card_token_db = new CardTokenDB;

        $card_token_do->set_token($token_input);
        $err = $card_token_db->fetch_with_card($card_token_do);

        if ($err !== ERR::SUCCESS)
            return array(false, $err);
        else
            return array($card_token_do, $err);
    }

    private function separate_token_txn_input($input)
    {
        $new_token_input_keys = CardTokenDO::input_keys_for_new_token();
        $token_input = array();
        $txn_input = array();

        foreach ($input as $key => $value)
        {
            if (in_array($key, $new_token_input_keys))
                $token_input[$key] = $value;
            else
                $txn_input[$key] = $value;
        }

        return array($token_input, $txn_input);
    }

    private function create_token($token_input)
    {
        $card_token_db = new CardTokenDB;

        $token_service = new Token;

        list($card_token_do, $err) = $token_service->build_token($token_input);
        if ($err !== ERR::SUCCESS)
            return array(false, $err);

        $err = $card_token_db->insert($card_token_do);
        if ($err !== ERR::SUCCESS)
        {
            return array(false, $err);
        }

        return array($card_token_do, $err);
    }
}