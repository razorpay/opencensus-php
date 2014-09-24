<?php

namespace Models\Payment;

use BasicAuth;
use EE\Exception;
use Http\Route;
use Models\Card;
use Models\Payment;
use Request;
use Trace\Trace;
use Trace\TraceCode;


class Authorize extends Action
{
    public function process($input)
    {
        $input['merchant_id'] = $this->merchant->getKey();

        $this->tracePaymentNewRequest($input);

        list($txn, $cardData) = $this->createEntitites($input);
        $this->txn = $txn;

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

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

        $this->updatePaymentAuthorized();

        return $txn;
    }

    /**
     * After card enroll, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies 'auth' is successful.
     *
     * @param  string              $id      Payment id
     * @param  array               $input   contains fields provided
     *                                      by bank
     *
     * @return Payment\Entity           Updated txn entity
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
            Payment\Validator::bankAcsCallbackValidate($txn, $input);

            $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                $txn,
                $e->getError(),
                TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }

        $this->updatePaymentAuthorized();

        return $txn;
    }

    protected function attachCallbackUrl(& $callbackData)
    {
        $urlSegment = Route::getApiRouteUrl('payment_callback');

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
                                            Payment\Action::AUTHORIZE,
                                            $data);

            return $callbackData;
        }
        catch(Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_AUTH_FAILURE);

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
     *                      Payment\Entity object and
     *                      card data array
     */
    public function createEntitites(array $input)
    {
        //
        // Check that card key exists
        //
        Payment\Validator::checkCardKey($input);

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
        $this->txn = $this->createPaymentEntity($input, $card);

        $this->saveEntities();

        return array($this->txn, $cardData);
    }

    protected function saveEntities()
    {
        (new Card\Repository)->saveOrFail($this->txn->card);

        $this->repo->saveOrFail($this->txn);
    }

    /**
     * Creates an entry for a new payment
     *
     * @param  array                $input  Input relevant to creating
     *                                      a txn row in db
     * @param  Card\Entity          $card   Card
     *
     * @return Payment\Entity   A Payment\Entity object
     */
    public function createPaymentEntity($input, Card\Entity $card)
    {
        $txn = (new Payment\Entity)->build($input);

        //
        // Associate payment to card
        //
        $txn->card()->associate($card);

        return $txn;
    }

    protected function updatePaymentAuthorized()
    {
        $txn = $this->txn;

        $txn->setStatus(Payment\Status::AUTHORIZED);

        $txn->save();

        $this->trace(TraceCode::PAYMENT_AUTH_SUCCESS);
    }

    protected function tracePaymentNewRequest($input)
    {
        $this->trace->debug(TraceCode::PAYMENT_NEW_REQUEST, $input);
    }
}
