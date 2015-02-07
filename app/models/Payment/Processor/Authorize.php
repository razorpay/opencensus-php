<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Card;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

trait Authorize
{
    public function authorize($payment, $input)
    {
        $gatewayInput = [];

        if ($payment->isMethod(Payment\Method::CARD))
        {
            $cardData = $this->createCardEntity($input);

            (new Card\Repository)->saveOrFail($payment->card);

            $gatewayInput['card'] = $cardData;
        }

        $this->repo->saveOrFail($payment);

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        //
        // Call gateway with required info
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gateway = $payment->getGateway();

        if ($gateway === Payment\Gateway::ATOM)
        {
            $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
        }

        $data = $this->callGatewayAuthorize($gatewayInput);

        if ($data !== null)
        {
            if ($gateway === Payment\Gateway::HDFC)
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

                $data['callbackUrl'] = $this->getCallbackUrl();
            }

            return $data;
        }

        // For atom gateway, authorization cannot happen in a single step
        if ($payment->isGateway(Payment\Gateway::ATOM))
        {
            return $data;
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

        if ($payment->isSigned())
        {
            return $this->captureSignedPayment($payment);
        }

        return ['razorpay_payment_id' => $payment->getPublicId()];
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

        $cardData = $cardCore->createAndReturnWithSensitiveData($input['card'], $this->merchant);

        $card = $cardCore->getCard();

        if ($card->isUnsupported())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
        }

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
