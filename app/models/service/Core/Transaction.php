<?php

namespace Models\Service\Core;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\DAL;
use Gateway\GatewayManager;
use EE\Exception\GatewayTimeoutException;
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
    public function createEntitites(array $input)
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
        // Creates card entity. But since we don't store
        // number and cvv for now, we get back a card data
        // array instead of DAL\Card with number and cvv inserted
        // after storing card details (DAL\Card)
        //
        $cardData = (new Card)->createAndReturnWithSensitiveData($input['card']);

        //
        // Create token. Links to card id and merchant id
        //
        $token = (new Token)->create(
                    $input['merchant_id'],
                    $cardData['id']);

        //
        //  Links txn to token
        //
        $txnInput['token'] = $token->token;

        //
        // Remove card key from input. Isn't needed
        //
        unset($txnInput['card']);

        //
        // Create txn entity and saves
        //
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
        catch(GatewayTimeoutException $e)
        {
            $status = TransactionStatus::FAILED;

            $error = $e->getError();

            $this->updateTransactionFailed($error);

            throw $e;
        }

        return $this->updateTransactionStatus($status, $data);
    }

    function updateTransactionStatus($status, $data)
    {
        $txn = $this->txn;

        if ($status === 'enrolled')
            return $data;

        switch ($status)
        {
            //
            // This case means that card (DC) is enrolled.
            // Now a form will be displayed and submitted
            // to bank ACS for for customer to enter 3d-secure
            // or OTP.
            //
            case 'enrolled':
            return $data;

            // case 'not enrolled':
            //     $txn->setStatus(TransactionStatus::AUTH);
            //     return $txn;

            case TransactionStatus::AUTH:
                $this->updateTransactionAuth();
                break;

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
     * Refunds a transaction
     * Pass \DAL\Transaction object as argument
     */
    public function refund(DAL\Transaction $txn)
    {
        $data = array(
            'txn' => $txn->toArrayEx(
                        DAL\Transaction::WITH_CARD));

        list($status, $error) = (new GatewayManager)->refund($data);

        if ($status)
        {
            $txn->setStatus(TransactionStatus::REFUNDED);

            $txnArray = $txn->toArray();

            //Logging
            $this->trace->info(
                TraceEvent::TRANSACTION_REFUNDED,
                $txnArray);

            //Analytics
            //$txnArray['merchant_id'] = $txn->getMerchantId();
            \Dashboard\Transaction::getInstance()->queueRecord($txnArray);
        }
        else
        {
            $txn->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_REFUND_FAILED,
                $txnData->toArray());
        }

        return $txn;
    }

    /**
     * Capture a preivous auth transaction
     *
     * @param  DAL\Transaction $txn DAL\Transaction object
     *
     * @return array                Transaction array
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

            $ledger = (new DAL\Ledger)->updateRecords($txn);
        }
        else
        {
            $txn->setStatus(TransactionStatus::CAPTURE_FAILED);
            $txn->setError($error);

            //Logging
            $this->trace->error(
                TraceEvent::TRANSACTION_CAPTURE_FAILED,
                $txn->toArray());
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

    protected function updateTransactionFailed($error)
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
