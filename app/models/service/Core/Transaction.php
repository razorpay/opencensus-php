<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use Exceptions;
use Exceptions\InvalidArgumentException;
use Trace\Trace;
use Trace\TraceEvent;

class Transaction
{
    protected $txn;

    protected $card;

    protected $trace;

    public function __construct()
    {
        $this->trace = Trace::getInstance();
    }

    /**
     * Creates card, token and txn entities
     *
     * @param  array $input Input required for creating
     *                      card, token and txn entities
     *
     * @return array        Returns an array containing
     *                      DAL\Transaction object and
     *                      card data array
     */
    public function createEntitites($input = null)
    {
        $cardToken = null;

        $txnInput = $input;

        // Check that card key exists
        if (! array_key_exists('card', $input))
        {
            throw new InvalidArgumentException(
                'Transaction Exception: Card not provided');
        }

        //
        // Now separate inputs required for creating card and token
        //
        list($cardInput, $tokenInput) =
            Manager\CardToken::separateTokenAndCardCreateInput(
                $input['card']);

        //
        // Creates card entity. But since we don't store
        // number and cvv for now, we get back a card data
        // array with number and cvv inserted after storing
        // card details (DAL\Card)
        //
        $cardData = (new Card)->createAndReturnWithSensitiveData($cardInput);

        //
        // Create token. Links to card id and merchant id
        //
        $token = (new Token)->create(
                    $tokenInput,
                    $input['merchant_id'],
                    $cardData['id']);

        // Links txn to token
        $txnInput['token'] = $token->token;

        // Remove card key from input. Isn't needed
        unset($txnInput['card']);

        // Create txn entity and store.
        $txn = $this->create($txnInput);

        return array($txn, $cardData);
    }

    /**
     * Creates an entry for a new transaction
     *
     * @param  array $input Input relevant to creating
     *                      a txn row in db
     *
     * @return DAL\Transaction  A DAL\Transaction object
     */
    public function create($input)
    {
        $data = Manager\Transaction::createValidate($input)->getData();

        return DAL\Transaction::createOrFail($data);
    }

    /**
     * Processes a transaction.
     * Passes data along to the gateway
     * which does the actual processing.
     * After return, updates transaction status.
     *
     * @param  DAL\Transaction $txn      Transaction object
     * @param  array           $cardData card data array
     *
     * @return DAL\Transaction           Transaction object
     */
    public function process(
        DAL\Transaction $txn,
        array $cardData)
    {
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
            if ($this->checkTimeout($e))
            {
                $status = TransactionStatus::FAILED;
                $data['code'] = 'TIMEOUT';
                $data['message'] = 'Request timed out';
            }
            else
                throw $e;
        }

        return $this->updateTransactionStatus($status, $data);
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param  Requests_Exception $e The caught requests exception
     *
     * @return boolean               true/false
     */
    protected function checkTimeout(\Requests_Exception $e)
    {
        //check if timeout has occured
        if ((strpos($e->getMessage(), 'Operation timed out')  !== false) or
            (strpos($e->getMessage(), 'Network is unreachable') !==false) or
            (strpos($e->getMessage(), 'Name or service not known') !== false) or
            (strpos($e->getMessage(), 'Failed to connect') !== false) or
            (strpos($e->getMessage(), 'Could not resolve host') !== false))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    function updateTransactionStatus($status, $data)
    {
        $txn = $this->txn;

        switch ($status)
        {
            case 'enrolled':
            return $data;

            case 'not enrolled':
                $txn->setStatus(TransactionStatus::AUTH);
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
                throw new \LogicException(
                    'Transaction Exception: ' . $status . ' is an invalid status');
        }

        return $txn;
    }

    /**
     * Capture a preivous auth transaction
     *
     * @param  [type] $txn [description]
     * @return [type]      [description]
     */
    public function capture(DAL\Transaction $txn)
    {
        $data = array('txn' => $txn->toArrayEx(DAL\Transaction::WITH_CARD));

        $gateway = new GatewayManager();

        list($status, $error) = $gateway->capture($data);

        if($status)
        {
            $txn->setStatus(Manager\TransactionStatus::CAPTURED);

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_CAPTURED,
                $txn->toArray());
        }
        else
        {
            $txn->setStatus('capture_failed');
            $txn->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_FAILED,
                $txn->toArray() + array('message' => 'Transaction Capture Request Failed'));
        }

        return $txn;
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
