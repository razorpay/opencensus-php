<?php 

namespace Models\Service\Core;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use Exceptions;
use Trace\TransactionTrace;

class Transaction
{
    protected $txn;

    protected $card;

    protected $trace;

    public function __construct()
    {
        $this->trace = new TransactionTrace();
    }
    /**
     * Creates an entry for a new transaction.
     */
    public function create($input = null)
    {
        $card_token = null;

        $txn_input = $input;
        
        if (! array_key_exists('card', $input))
        {
            throw new \Exceptions\InvalidArgumentException(' Transcation Exception: Card not provided');
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
            throw new Exceptions\InvalidArgumentException('Transaction Exception: Invalid transaction id');
        }

        $this->txn = $txn;

        //
        // Call gateway with required info
        //
        $txnInfo = array(
                    'txn' => $txn->toArray(),
                    'card' => $card);

        $gateway = new GatewayManager();
        $status;
        $data;
        
        try{
            list($status, $data) = $gateway->process($txnInfo);
        }
        catch(\Requests_Exception $e)
        {   
            //check if timeout has occured
            if(strpos($e->getMessage(), 'Operation timed out') || strpos($e->getMessage(), 'Network is unreachable'))
            {   
                $status= TransactionStatus::TIMEOUT;
                $data['code'] = "TIMEOUT";
                $data['message'] = 'Request timed out';
            }
            else throw $e;         
        }

        switch ($status)
        {
            case TransactionStatus::ENROLLED:
            return $data;

            case TransactionStatus::NOT_ENROLLED:
            $txn->setStatus('auth');
            if (! $txn->getHold())
                $txn->setCapturable(true);
            return $txn;

            //@todo: Update data on hold
            case TransactionStatus::AUTH:
            $this->updateTransactionAuth();
            break;

            //@todo: Update data on captured
            case TransactionStatus::CAPTURED:
            $this->updateTransactionCaptured();
            break;

            //@todo: Fill errors on failure
            case TransactionStatus::FAILED:
            $this->updateTransactionFailed();
            $txn = $this->fillErrorDetails($data, $txn);
            break;

            case TransactionStatus::TIMEOUT:
            $this->updateTransactionFailed();
            $txn = $this->fillErrorDetails($data, $txn);
            break;

            default:
            throw new \LogicException('Transaction Exception: '.$status . ' is an invalid status');
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

    protected function updateTransactionAuth()
    {
        $this->txn->setStatus(TransactionStatus::AUTH);

        //Logging
        $this->trace->info(TransactionTrace::TRANSACTION_AUTHED, $this->txn->toArray() + array('message' => 'Transaction Auth Successfull'));   
    }

    protected function updateTransactionCaptured()
    {
        $this->txn->setStatus(TransactionStatus::CAPTURED);

        //Logging
        $this->trace->info(TransactionTrace::TRANSACTION_CAPTURED, $this->txn->toArray() + array('message' => 'Transaction Capture Successfull'));
    }

    protected function updateTransactionFailed()
    {
        $this->txn->setStatus(TransactionStatus::FAILED);
    }

    protected function updateTransactionCaptureFailed()
    {
        $this->txn->setStatus(TransactionStatus::CAPTURE_FAILED);
    }

    protected function fillErrorDetails($error, $txn)
    {   
        $txn->setError($error);

        //Logging
        $this->trace->error(TransactionTrace::TRANSACTION_FAILED, $txn->toArray() + array('message' => 'Transaction Failed'));
        
        return $txn;
    }
}
