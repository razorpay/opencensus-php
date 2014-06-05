<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use Exceptions;
use Trace\TransactionTrace;
use Trace\TraceEvent;

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
        $cardToken = null;

        $txnInput = $input;

        if (! array_key_exists('card', $input))
        {
            throw new \Exceptions\InvalidArgumentException(' Transaction Exception: Card not provided');
        }

        if(strlen($input['card']['expiry_year']) == 2)
            $input['card']['expiry_year'] = '20'.$input['card']['expiry_year'];

        $input['card']['number'] = str_replace(' ', '', $input['card']['number']);

        list($cardInput, $tokenInput) = Manager\CardToken::separateTokenAndCardCreateInput($input['card']);

        $cardData = (new Card)->createAndReturnWithSensitiveData($cardInput);

        $token = (new Token)->create(
                    $tokenInput, 
                    $input['merchant_id'],
                    $cardData['id']);

        $txnInput['token'] = $token->token;

        unset($txnInput['card']);

        $data = Manager\Transaction::createValidate($txnInput)->getData();

        $txn = DAL\Transaction::createOrFail($data);

        return array($txn, $cardData);
    }

    /**
     * Processes a transaction.
     */
    public function process(
        DAL\Transaction $txn,
        array $cardData)
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
                    'card' => $cardData);

        $gateway = new GatewayManager();
        
        $status = null;
        $data = null;

        try
        {
            list($status, $data) = $gateway->process($txnInfo);
        }
        catch(\Requests_Exception $e)
        {
            //check if timeout has occured
            if(strpos($e->getMessage(), 'Operation timed out') || strpos($e->getMessage(), 'Network is unreachable'))
            {
                $status = TransactionStatus::FAILED;
                $data['code'] = "TIMEOUT";
                $data['message'] = 'Request timed out';
            }
            else throw $e;
        }

        return $this->updateTransactionStatus($status, $data, $txn);
    }

    function updateTransactionStatus($status, $data, $txn)
    {

        switch ($status)
        {
            case 'enrolled':
            return $data;

            case 'not enrolled':
            $txn->setStatus(TransactionStatus::AUTH);
            // if (! $txn->getHold())
            //     $txn->setCapturable(true);
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

            case 'timeout':
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
        $this->trace->info(
            TraceEvent::TRANSACTION_AUTHED, 
            $this->txn->toArray());
    }

    protected function updateTransactionCaptured()
    {
        $this->txn->setStatus(TransactionStatus::CAPTURED);

        //Logging
        $this->trace->info(
            TraceEvent::TRANSACTION_CAPTURED, 
            $this->txn->toArray());
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
        $this->trace->error(
            TraceEvent::TRANSACTION_FAILED, 
            $txn->toArray());

        return $txn;
    }
}
