<?php

namespace Models\Service\Core;

use EE\Exception\BaseException;

use Gateway\GatewayManager;

use Models\DAL;

use Models\Manager;
use Models\Manager\TransactionStatus;
use Models\Manager\TransactionAction;


use Trace\Trace;
use Trace\TraceCode;

class Transaction
{
    protected $txn;

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
            throw new BadRequestException(
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

        $status = null;
        $data = null;

        try
        {
            $callbackData = $this->callGatewayFunction(
                                TransactionAction::AUTH,
                                $txnInfo);
        }
        catch(BaseException $e)
        {
            $status = TransactionStatus::FAILED;

            $this->updateTransactionFailed(
                    $txn,
                    $e->getError(),
                    TraceCode::TRANSACTION_AUTH_FAILURE);

            throw $e;
        }

        if ($callbackData !== null)
        {
            //
            // This case means that card (DC) is enrolled.
            // Now a form will be displayed and submitted
            // to bank ACS for for customer to enter 3d-secure
            // or OTP.
            // The data field required for generating the
            // form is returned by gateway.
            // It's now returned further to wherever it
            // will be used to display form.
            //

            return $callbackData;
        }

        return $this->updateTransactionSuccess($txn, TransactionStatus::AUTH);
    }

    public function callback(
        DAL\Transaction $txn,
        array $input)
    {
        try
        {
            $this->callGatewayFunction(TransactionAction::CALLBACK, $input);

            $this->updateTransactionSuccess($txn, TransactionStatus::AUTH);
        }
        catch (BaseException $e)
        {
            $this->updateTransactionFailed(
                $txn,
                $e->getError(),
                TraceCode::TRANSACTION_AUTH_FAILURE);

            throw $e;
        }

        return $txn;
    }

    protected function updateTransactionSuccess($txn, $status)
    {
        switch ($status)
        {
            case TransactionStatus::AUTH:
                $this->updateTransactionAuth($txn);
                break;

            case TransactionStatus::CAPTURED:
                $this->updateTransactionCaptured($txn);
                break;

            case TransactionStatus::REFUNDED:
                $this->updateTransactionRefunded($txn);
                break;

            default:
                throw new \LogicException(
                    'Transaction Exception: ' . $status . ' is an invalid status');
        }

        $txn->save();

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

        try
        {
            $this->callGatewayFunction(TransactionAction::REFUND, $data);

            $this->updateTransactionSuccess($txn, TransactionStatus::REFUNDED);

            //
            // Analytics
            //
            \Dashboard\Transaction::getInstance()
                                  ->queueRecord($txn->toArray());
        }
        catch(BaseException $e)
        {
            $error = $e->getError();

            $txn->setError($error);

            //Logging
            $this->trace->error(
                TraceCode::TRANSACTION_REFUND_FAILURE,
                $txnData->toArray());

            throw $e;
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

        try
        {
            $this->callGatewayFunction(
                    TransactionAction::CAPTURE, $data);

            $this->updateTransactionSuccess($txn, TransactionStatus::CAPTURED);
        }
        catch (BaseException $e)
        {
            $txn->setStatus(TransactionStatus::CAPTURE_FAILED);
            $txn->setError($error);
            $txn->save();

            //Logging
            $this->trace->error(
                TraceCode::TRANSACTION_CAPTURE_FAILURE,
                $txn->toArray());

            throw $e;
        }

        return $txn;
    }

    protected function updateTransactionAuth($txn)
    {
        $txn->setStatus(TransactionStatus::AUTH);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_AUTH_SUCCESS,
            $txn->toArray());
    }

    protected function updateTransactionCaptured($txn)
    {
        $txn->setStatus(TransactionStatus::CAPTURED);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_CAPTURE_SUCCESS,
            $txn->toArray());
    }

    protected function updateTransactionRefunded($txn)
    {
        $txn->setStatus(TransactionStatus::REFUNDED);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_REFUND_SUCCESS,
            $txn->toArray());
    }

    protected function updateTransactionFailed($txn, $error, $traceCode)
    {
        $code = $error->getPublicErrorCode();

        $desc = $error->getPublicErrorDescription();

        $txn->setStatus(TransactionStatus::FAILED);

        $txn->setError($code, $desc);

        $txn->save();

        //Logging
        $this->trace->error(
            TraceCode::TRANSACTION_FAILED,
            $txn->toArray());
    }

    public function callGatewayFunction($action, $input)
    {
        $data = (new GatewayManager)->$action($input);

        // $ledger = (new DAL\Ledger)->updateRecords($txn);

        return $data;
    }
}
