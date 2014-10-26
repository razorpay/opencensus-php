<?php

namespace Models\Payment\Processor;

use EE\Exception;
use Models\Card;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Authorize
{
    public function authorize($payment, $input)
    {
        $cardData = $this->createCardEntity($input);

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        $this->savePaymentAndCard();

        //
        // Call gateway with required info
        //
        $paymentInfo = array(
                    'payment' => $payment->toArray(),
                    'card' => $cardData);

        $callbackData = $this->callGatewayAuthorize($paymentInfo);

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

            $callbackData['callbackUrl'] = $this->getCallbackUrl();

            return $callbackData;
        }

        $this->updatePaymentAuthorized();

        return $payment;
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
     * @return Payment\Entity           Updated payment entity
     */
    public function callback($id, array $input)
    {
        $payment = $this->retrieve($id);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($input['csrf']);

        $input['payment'] = $payment->toArray();

        try
        {
            Payment\Validator::bankAcsCallbackValidate($payment, $input);

            $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }
        catch (BaseException $e)
        {
            $this->updatePaymentFailed(
                $payment,
                $e->getError(),
                TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }

        $this->updatePaymentAuthorized();

        return $payment;
    }

    protected function callGatewayAuthorize(array $data)
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
     * Creates card and payment entities
     *
     * @param  array $input Input required for creating
     *                      card and payment entities
     *
     * @return array        Returns an array containing
     *                      Payment\Entity object and
     *                      card data array
     */
    public function createCardEntity(array $input)
    {
        //
        // Creates card entity. But since we don't store
        // number and cvv for now, we get back a card data
        // array contianing Card\Entity with number and cvv
        //
        $cardCore = new Card\Core();

        $cardData = $cardCore->createAndReturnWithSensitiveData($input['card']);

        $card = $cardCore->getCard();

        $this->payment->card()->associate($card);

        return $cardData;
    }

    protected function savePaymentAndCard()
    {
        (new Card\Repository)->saveOrFail($this->payment->card);

        $this->repo->saveOrFail($this->payment);
    }

    protected function updatePaymentAuthorized()
    {
        $payment = $this->payment;

        $payment->setAmountAuthorized();

        $payment->setStatus(Payment\Status::AUTHORIZED);

        $payment->terminal->incrementUsedCount();

        $payment->save();
        $payment->terminal->save();

        $this->trace(TraceCode::PAYMENT_AUTH_SUCCESS);
    }
}
