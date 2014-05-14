<?php 

namespace Models\Service;

use Models\Manager;
use Models\DAL;
use Gateway\GatewayManager;
use Rhumsaa\Uuid\Uuid;
use Rhumsaa\Uuid\Exception\UnsatisfiedDependencyException;

class Transaction extends Service
{
    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
        $card_token = null;

        $txn_input = $input;
        
        if (! array_key_exists('card', $input))
        {
            throw new \InvalidArgumentException('Card not provided');
        }

        list($card_input, $card_token_input) = Manager\CardToken::separateTokenAndCardCreateInput($input['card']);

        $card_data = Manager\Card::createValidate($card_input)->getData();

        $card = DAL\Card::create($card_data);

        $card_token_input['merchant_id'] = $input['merchant_id'];
        
        $card_token_data = Manager\CardToken::createValidate($card_token_input)->getData();

        $card_token_data['card_id'] = $card->getId();

        $token = DAL\CardToken::create($card_token_data);

        $txn_input['token'] = $token->getToken();

        unset($txn_input['card']);

        $data = Manager\Transaction::createValidate($txn_input)->getData();

        //TODO
        //has to be handled better
        $data['id'] = \Models\DAL\UuidDAL::generateUuid();

        $txn = DAL\Transaction::createOrFail($data);

        $txn = $this->process($txn, $card_data);

        return $txn;
    }

    /**
     * Processes a transaction.
     * This function needs to be re-written.
     */
    public function process($txn, $card)
    {
        if (is_string($txn))
        {
            $txn = Transaction::findByTxn($txn);
        }
        else if (! ($txn instanceof DAL\Transaction))
        {
            throw new \InvalidArgumentException('Invalid transaction id');
        }

        //
        // Call gateway with required info
        //
        {
            $data = array(
                        'txn' => $txn->toArray(),
                        'card' => $card);

            $gateway = new GatewayManager();

            return $gateway->process($data);
            
            //return $txn;
        }
        
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

        $status = $gateway->refund($data);

        $txn_data->setRefunded($status);
    }

    public function bankAcsCallback(array $input)
    {
        unset($input['csrf']);
        
        $gateway = new GatewayManager();

        list($processed, $id) = $gateway->bankAcsCallback($input);

        if ($processed)
        {
            $txn = DAL\Transaction::where('id', $id)
                                  ->update(array('processed' => 1));
            return "Transaction successful";
        }
        else
        {
            return "Transaction unsuccessful";
        }
    }
}