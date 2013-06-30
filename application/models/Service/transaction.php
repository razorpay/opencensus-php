<?php 

namespace Service;

use ERR;
use BasicAuth;
use DomainObject\Transaction as TransactionDO;
use DataMapper\Transaction as TransactionDB;
use DomainObject\CardToken as CardTokenDO;

class Transaction
{

    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null, $merchant_id)
    {
        $txn_do = new TransactionDO();

        $txn_do->verify_build_input($input);

        if (isset($input['card']))
        {
            $tok = $input['card'];
            $card_token_do = new CardTokenDO;
            $card_token_db = new CardTokenDB;

            $card_token_do->set_token($tok);
            $err = $card_token_db->fetch_with_card($card_token_do);

            if ($err !== ERR::SUCCESS)
                return $err;
        }
        else 
        {
            $new_token_input_keys = CardTokenDO::input_keys_for_new_token();

            $token_input = array();

            foreach ($input as $key => $value)
            {
                if (in_array($key, $new_token_input_keys))
                    $token_input[$key] = $value;
            }

            $token_service = new Token;

            list ($card_token_do, $err) = $token_service->generate($token_input);
            if ($err !== ERR::SUCCESS)
                return $err;
        }

        $txn_do->set_token_do($card_token_do);

        $data['merchant_id'] = BasicAuth::MerchantId();

        $err = $txn_do->build($data);

        if ($err !== ERR::SUCCESS)
        {
            return $err;
        }

        $txn_db = new TransactionDB;
        $err = $txn_db->insert($txn_do);

        $process_now = $txn_do->process_now();

        if ($process_now)
        {
            $err = $this->process(null, $txn_do);

            if ($err !== ERR::SUCCESS)
                return array(false, $err);
        }
        else
        {
            return array($txn_do, ERR::SUCCESS);
        }

    }

    /**
     * Processes a transaction.
     * This function will be re-written.
     */
    public function process(array $input, TransactionDO $txn_do = null)
    {
        if (($input === null) and ($txn_do === null) or
            ($input !== null) and ($txn_do !== null))
            return ERR::INVALID_PARAMETERS;

        if ($txn_do !== null)
        {
            $gateway = new Gateway;
            $gateway->process($txn_do);
            $txn_do->set_processed(1);
        }

    }
}