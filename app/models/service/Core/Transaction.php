<?php 

namespace Models\Service\Core;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use Exceptions;

class Transaction
{
    protected $txn;

    protected $card;

    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
        $card_token = null;

        $txn_input = $input;
        
        if (! array_key_exists('card', $input))
        {
            throw new \Exceptions\InvalidArgumentException('Card not provided');
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

        //@todo: this needs to be handled better
        $data['id'] = \Models\DAL\UuidDAL::generateUuid();

        $txn = DAL\Transaction::createOrFail($data);

        return array($txn, $card_data);
    }

    /**
     * Processes a transaction.
     */
    public function process($txn, $card)
    {
        if (! ($txn instanceof DAL\Transaction))
        {
            throw new Exceptions\InvalidArgumentException('Invalid transaction id');
        }

        $this->txn = $txn;

        //
        // Call gateway with required info
        //
        $txnInfo = array(
                    'txn' => $txn->toArray(),
                    'card' => $card);

        $gateway = new GatewayManager();

        list($status, $data) = $gateway->process($txnInfo);

        switch ($status)
        {
            case TransactionStatus::ENROLLED:
            return $data;

            //@todo: Update data on hold
            case TransactionStatus::HOLD:
            $this->updateTransactionHold();
            break;

            //@todo: Update data on captured
            case TransactionStatus::CAPTURED:
            $this->updateTransactionCaptured();
            break;

            //@todo: Fill errors on failure
            case TransactionStatus::FAILED:
            $error = $this->fillErrorDetails($data, $txn);
            $this->updateTransactionFailed();
            break;

            default:
            throw new \LogicException($status . ' is an invalid status');
        }

        return $txn;
    }

    /**
     * Capture a preivous auth transaction
     * 
     * @param  [type] $txn [description]
     * @return [type]      [description]
     */
    public function capture($txn)
    {
        ;
    }

    protected function updateTransactionHold()
    {
        $this->txn->updateStatus(TransactionStatus::HOLD);
    }

    protected function updateTransactionCaptured()
    {
        $this->txn->updateStatus(TransactionStatus::CAPTURED);
    }

    protected function updateTransactionFailed($error)
    {
        $this->txn->failed();
    }

    protected function fillErrorDetails($error, $txn)
    {
        
    }
}
