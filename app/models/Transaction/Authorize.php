<?php

namespace Models\Transaction;

use Http\Route;
use Models\Card;
use Models\Transaction;
use Trace\Trace;
use Trace\TraceCode;

use BasicAuth;
use Request;

class Authorize extends Action
{
    public function process($input)
    {
        $input['merchant_id'] = $this->merchant->getKey();

        $this->traceTransactionNewRequest($input);

        list($txn, $cardData) = $this->createEntitites($input);

        $this->trace(TraceCode::TRANSACTION_CREATED, Trace::DEBUG);

        //
        // Call gateway with required info
        //
        $txnInfo = array(
                    'txn' => $txn->toArray(),
                    'card' => $cardData);

        $callbackData = $this->callGateway($txnInfo);

        if ($callbackData !== null)
        {
            //
            // This case means that card is enrolled.
            // Now a form will be displayed and submitted
            // to bank ACS for for customer to enter 3d-secure
            // or OTP.
            // The data field required for generating the
            // form is returned by gateway.
            // It's now returned further to wherever it
            // will be used to display form.
            //

            $this->attachCallbackUrl($callbackData);

            return $callbackData;
        }

        $txn->setAmountAuthorized();

        $this->updateTransactionAuthorized();

        return $txn;
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies 'auth' is successful.
     *
     * @param  string              $id      Transaction id
     * @param  array               $input   contains fields provided
     *                                      by bank
     *
     * @return Transaction\Entity           Updated txn entity
     */
    public function callback($id, array $input)
    {
        $txn = $this->retrieve($id);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($input['csrf']);

        $input['txn'] = $txn->toArray();

        try
        {
            Transaction\Validator::bankAcsCallbackValidate($txn, $input);

            $this->callGatewayFunction(Transaction\Action::CALLBACK, $input);
        }
        catch (BaseException $e)
        {
            $this->updateTransactionFailed(
                $txn,
                $e->getError(),
                TraceCode::TRANSACTION_AUTH_FAILURE);

            throw $e;
        }

        $this->updateTransactionAuthorized();

        return $txn;
    }

    protected function attachCallbackUrl(& $callbackData)
    {
        $urlSegment = Route::getApiRouteUrl('transaction_callback');

        $pos = strrpos($urlSegment, '/');

        $urlSegment = substr($urlSegment, 0, $pos);

        $urlSegment .= '/' . $this->txn->getPublicId();

        $scheme = Request::getScheme().'://';
        $host = Request::getHost();
        $key = BasicAuth::getPublicKey();

        $callbackUrl = $scheme . $key . '@' . $host . '/v1/' . $urlSegment;

        $callbackData['callbackUrl'] = $callbackUrl;
    }

    protected function callGateway(array $data)
    {
        try
        {
            $callbackData = $this->callGatewayFunction(
                                            Transaction\Action::AUTHORIZE,
                                            $data);

            return $callbackData;
        }
        catch(BaseException $e)
        {
            $this->core->updateTransactionFailed(
                    $e->getError(),
                    TraceCode::TRANSACTION_AUTH_FAILURE);

            throw $e;
        }
    }

    /**
     * Creates card and txn entities
     *
     * @param  array $input Input required for creating
     *                      card and txn entities
     *
     * @return array        Returns an array containing
     *                      Transaction\Entity object and
     *                      card data array
     */
    public function createEntitites(array $input)
    {
        //
        // Check that card key exists
        //
        Transaction\Validator::checkCardKey($input);

        //
        // Creates card entity. But since we don't store
        // number and cvv for now, we get back a card data
        // array contianing Card\Entity with number and cvv
        //
        $cardCore = new Card\Core();

        $cardData = $cardCore->createAndReturnWithSensitiveData($input['card']);

        $card = $cardCore->getCard();

        //
        // Remove card key from input. Isn't needed
        //
        unset($input['card']);

        //
        // Create txn entity
        //
        $this->txn = $this->createTransactionEntity($input, $card);

        $this->saveEntities();

        return array($this->txn, $cardData);
    }

    protected function saveEntities()
    {
        (new Card\Repository)->saveOrFail($this->txn->card);

        $this->repo->saveOrFail($this->txn);
    }

    /**
     * Creates an entry for a new transaction
     *
     * @param  array                $input  Input relevant to creating
     *                                      a txn row in db
     * @param  Card\Entity          $card   Card
     *
     * @return Transaction\Entity   A Transaction\Entity object
     */
    public function createTransactionEntity($input, Card\Entity $card)
    {
        $txn = (new Transaction\Entity)->build($input);

        //
        // Associate transaction to card
        //
        $txn->card()->associate($card);

        return $txn;
    }

    protected function updateTransactionAuthorized()
    {
        $txn = $this->txn;

        $txn->setStatus(Transaction\Status::AUTHORIZED);

        $txn->save();

        $this->trace(TraceCode::TRANSACTION_AUTH_SUCCESS);
    }

    protected function traceTransactionNewRequest($input)
    {
        $this->trace->debug(TraceCode::TRANSACTION_NEW_REQUEST, $input);
    }
}
