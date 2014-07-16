<?php

namespace Models\Transaction;

use EE\Exception\BaseException;
use EE\Exception\BadRequestException;

use Gateway\GatewayManager;

use Models\Card;
use Models\Ledger;
use Models\Token;
use Models\Transaction;

use Trace\Trace;
use Trace\TraceCode;

class Core
{
    protected $txn;

    protected $trace;

    protected $txnRepo;

    public function __construct()
    {
        $this->trace = Trace::getInstance();

        $this->txnRepo = (new Transaction\Repository);
    }

    /**
     * Creates card, token and txn entities
     *
     * @param  array $input Input required for creating
     *                      card, token and txn entities
     *
     * @return array        Returns an array containing
     *                      Transaction\Entity object and
     *                      card data array
     */
    public function createEntitites(array $input)
    {
        $txnInput = $input;

        //
        // Check that card key exists
        //
        Transaction\Validator::checkCardKey($input);

        //
        // Creates card entity. But since we don't store
        // number and cvv for now, we get back a card data
        // array contianing DAL\Card with number and cvv
        //
        $cardCore = new Card\Core();

        $cardData = $cardCore->createAndReturnWithSensitiveData($input['card']);

        $card = $cardCore->getCard();

        //
        // Create token. Links merchant id and card
        //
        $token = (new Token\Core)->create(
                    $input['merchant_id'],
                    $card);

        //
        // Remove card key from input. Isn't needed
        //
        unset($txnInput['card']);

        //
        // Create txn entity
        //
        $txn = $this->create($txnInput, $token);

        $this->saveEntities($txn);

        return array($txn, $cardData);
    }

    protected function saveEntities( $txn)
    {
        (new Card\Repository)->saveOrFail($txn->token->card);

        (new Token\Repository)->saveOrFail($txn->token);

        $this->txnRepo->saveOrFail($txn);
    }

    /**
     * Creates an entry for a new transaction
     *
     * @param  array     $input  Input relevant to creating
     *                           a txn row in db
     * @param  DAL\Token $token  Token
     *
     * @return Transaction\Entity   A Transaction\Entity object
     */
    public function create($input, Token\Entity $token)
    {
        $txn = (new Transaction\Entity)->build($input);

        //
        // Assoicate transaction to token
        //
        $txn->token()->associate($token);

        return $txn;
    }

    /**
     * Processes a transaction.
     * Passes data along to the gateway
     * which does the actual processing.
     * After return, updates transaction status.
     *
     * @param  Transaction\Entity $txn      Transaction object
     * @param  array           $cardData card data array
     *
     * @return Transaction\Entity           Transaction object
     */
    public function process(
        Transaction\Entity $txn,
        array $cardData)
    {
        $this->txn = $txn;

        //
        // Call gateway with required info
        //
        $txnInfo = array(
                    'txn' => $txn->toArray(),
                    'card' => $cardData);

        try
        {
            $callbackData = $this->callGatewayFunction(
                                        Transaction\Action::AUTHORIZE,
                                        $txnInfo);
        }
        catch(BaseException $e)
        {
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

        $txn->setAuthAmount();

        return $this->updateTransactionSuccess($txn, Transaction\Status::AUTHORIZED);
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies
     * 'auth' is successful.
     *
     * @param  Transaction\Entity $txn   Txn dal
     * @param  array           $input contains fields provided
     *                                by bank
     * @return Transaction\Entity        Updated txn dal
     */
    public function callback(
        Transaction\Entity $txn,
        array $input)
    {
        try
        {
            $this->callGatewayFunction(Transaction\Action::CALLBACK, $input);

            $this->updateTransactionSuccess($txn, Transaction\Status::AUTHORIZED);
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

    /**
     * Capture a preivous auth transaction
     *
     * @param  Transaction\Entity $txn  Transaction\Entity object
     *
     * @return Transaction\Entity       Transaction\Entity object
     */
    public function capture(Transaction\Entity $txn, array $input = array())
    {
        (new Transaction\Validator)->captureValidate($txn, $input);

        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $input['amount']);

        $txn->setCaptureAmount($input['amount']);

        try
        {
            $this->callGatewayFunction(
                    Transaction\Action::CAPTURE, $data);

            $this->updateTransactionSuccess($txn, Transaction\Status::CAPTURED);

            //
            // Analytics
            //
            \Dashboard\Transaction::getInstance()
                                  ->queueRecord(array_merge(
                                        $txn->toArray(),
                                        ['merchant_id' => $txn->getMerchantId()]));
        }
        catch (BaseException $e)
        {
            $this->updateTransactionFailed(
                    $txn,
                    $e->getError(),
                    TraceCode::TRANSACTION_CAPTURE_FAILURE);

            throw $e;
        }

        return $txn;
    }

    /**
     * Refunds a transaction
     * Pass \Transaction\Entity object as argument
     */
    public function refund(Transaction\Entity $txn)
    {
        $data = array(
                    'txn' => $txn->toArrayWithCard(),
                    'amount' => $txn['amount']);

        try
        {
            $this->callGatewayFunction(Transaction\Action::REFUND, $data);

            $this->updateTransactionSuccess($txn, Transaction\Status::REFUNDED);

            //
            // Analytics
            //
            \Dashboard\Transaction::getInstance()
                                  ->queueRecord(array_merge(
                                        $txn->toArray(),
                                        ['merchant_id' => $txn->getMerchantId()]));
        }
        catch(BaseException $e)
        {
            $this->traceTransactionFailed(
                    $txn,
                    $e->getError(),
                    TraceCode::TRANSACTION_REFUND_FAILURE);

            throw $e;
        }

        return $txn;
    }

    protected function updateTransactionSuccess($txn, $status)
    {
        switch ($status)
        {
            case Transaction\Status::AUTHORIZED:
                $this->updateTransactionAuthorized($txn);
                $txn->save();
                break;

            case Transaction\Status::CAPTURED:
                $this->recordCapture($txn);
                break;

            case Transaction\Status::REFUNDED:
                $this->recordRefund($txn);
                break;

            default:
                throw new \LogicException(
                    'Transaction Exception: ' . $status . ' is an invalid status');
        }

        return $txn;
    }

    protected function recordCapture($txn)
    {
        $this->txnRepo->transaction(function() use ($txn)
        {
            $this->txnRepo->lockForUpdate($txn->getKey());

            (new Ledger\Core)->recordCapture($txn);

            $this->updateTransactionCaptured($txn);

            $txn->save();
        });
    }

    protected function recordRefund($txn)
    {
        $this->txnRepo->transaction(function() use ($txn)
        {
            $this->txnRepo->lockForUpdate($txn->getKey());

            (new Ledger\Core)->recordRefund($txn);

            $this->updateTransactionRefunded($txn);

            $txn->save();
        });
    }

    protected function updateTransactionCaptured(Transaction\Entity $txn)
    {
        $txn->setStatus(Transaction\Status::CAPTURED);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_CAPTURE_SUCCESS,
            $txn->toArrayTraceRelevant());
    }

    protected function updateTransactionRefunded(Transaction\Entity $txn)
    {
        $txn->setStatus(Transaction\Status::REFUNDED);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_REFUND_SUCCESS,
            $txn->toArrayTraceRelevant());
    }

    protected function updateTransactionAuthorized($txn)
    {
        $txn->setStatus(Transaction\Status::AUTHORIZED);

        //Logging
        $this->trace->info(
            TraceCode::TRANSACTION_AUTH_SUCCESS,
            $txn->toArrayTraceRelevant());
    }

    protected function updateTransactionFailed($txn, $error, $traceCode)
    {
        $code = $error->getPublicErrorCode();

        $desc = $error->getPublicErrorDescription();

        $txn->setStatus(Transaction\Status::FAILED);

        $txn->setError($code, $desc);

        $txn->save();

        $this->traceTransactionFailed($txn, $error, $traceCode);
    }

    protected function traceTransactionFailed($txn, $error, $traceCode)
    {
        $traceData = array_merge(
                        $txn->toArrayTraceRelevant(),
                        $error->toArray());

        //Logging
        $this->trace->error(
            $traceCode,
            $traceData);
    }

    /**
     * Responsible for actually calling the gateway function
     *
     * @param  string $action refund/capture etc.
     * @param  array  $input  Relevant input for the corresponding
     *                        action
     *
     * @return array or null
     */
    public function callGatewayFunction($action, array $input)
    {
        $data = (new GatewayManager)->$action($input);

        return $data;
    }

    public function retrieveTransaction($id, $merchantId)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        $txn = $this->txnRepo->findByIdAndMerchantId($id, $merchantId);

        return $txn;
    }
}
