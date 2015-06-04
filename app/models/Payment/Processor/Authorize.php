<?php

namespace Models\Payment\Processor;

use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Merchant\Banks;
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

        if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $this->verifyBankEnabled($payment);
        }

        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        $this->repo->saveOrFail($payment);

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        //
        // Call gateway with required info
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        $request = $this->callGatewayAuthorize($gatewayInput);

        //
        // If $request is not null, then payment is two-step process
        // where client needs to provide additional info via his browser.
        //
        if ($request !== null)
        {
            $data['request'] = $request;
            $data['version'] = 1;
            $data['payment_id'] = $payment->getPublicId();

            $data['gateway'] = \Crypt::encrypt($payment->getGateway() . '__' . time());

            return $data;
        }

        $this->updatePaymentAuthorized();

        return $payment;
    }

    /**
     * After payment initiation, bank redirects to us
     * and we send it to gateway for further
     * processing (auth).
     * Returning from this function implies payment action has been successful.
     *
     * @param  string              $id      Payment id
     * @param  array               $input   contains fields provided
     *                                      by bank
     *
     * @return Payment\Entity           Updated payment entity
     */
    public function callback($id, $hash, array $gatewayInput)
    {
        $payment = $this->retrieve($id);

        //
        // This field is received back from bank acs.
        // Kinda weird! And it's always null.
        //
        unset($gatewayInput['csrf']);

        $this->verifyHash($hash, $payment->getPublicId());

        $input['payment'] = $payment->toArray();
        $input['gateway'] = $gatewayInput;

        try
        {
            Payment\Validator::bankAcsCallbackValidate($payment, $input);

            $data = $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                $e->getError(),
                TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }

        $this->updatePaymentAuthorized();

        $this->postCallbackProcessing($data);

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

    protected function postCallbackProcessing($data)
    {
        $payment = $this->payment;

        if (($payment->isGateway(Payment\Gateway::ATOM) === false) or
            ($payment->isMethod(Payment\Method::CARD) === false))
        {
            return;
        }

        $card = $payment->card;

        if (isset($data['card']['type']) === false)
        {
            // @todo: trace here
            $this->trace->error(TraceCode::MISC_TRACE_CODE, ['message' => 'Card type not returned from atom']);
            return;
        }

        $type = $data['card']['type'];

        if ($card->getType() === $type)
        {
            return;
        }

        if (($card->getType() !== Card\Type::UNKNOWN) and
            ($type !== $card->getType()))
        {
            $this->trace->error(
                TraceCode::MISC_TRACE_CODE,
                ['message' => 'Atom card type does not match stored type',
                'type' => $type,
                'card_type' => $card->getType()]);
        }

        if (Card\Type::isValidType($type) === false)
        {
            $this->trace->error(
                TraceCode::MISC_TRACE_CODE,
                ['message' => 'Card type returned from atom is not valid.',
                'type' => $type,
                'card_type' => $card->getType()]);

            return;
        }

        $card->setType($type);
        (new Card\Repository)->saveOrFail($card);
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

    protected function verifyBankEnabled($payment)
    {
        $merchant = $payment->merchant;

        $banks = (new Banks\Core)->getMerchantBanks($merchant);

        if ($banks === null)
            $banks = [];
        else
            $banks = $banks->getBanks();

        $bank = $payment->getBank();

        if (in_array($bank, $banks) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
        }
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

    protected function verifyHash($hash, $paymentPublicId)
    {
        $expectedHash = $this->getHashOfPaymentPublicId();

        if ($expectedHash !== $hash)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Callback payment hash does not match. Please notify the admin of this error.');
        }
    }

    /**
     * Creates the callback url for payment
     * where the gateway can hit back to say payment
     * is finished/authorized.
     *
     * @return string Callback url
     */
    protected function getCallbackUrl()
    {
        $publicId = $this->payment->getPublicId();

        $hash = $this->getHashOfPaymentPublicId();

        $params = ['id' => $publicId, 'hash' => $hash];

        $callbackUrl = Route::getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    /**
     * Returns a hash of payment public id.
     *
     * @return string Hash of payment public id
     */
    protected function getHashOfPaymentPublicId()
    {
        $secret = \App::make('config')->get('app.key');

        $publicId = $this->payment->getPublicId();

        return hash_hmac('sha1', $publicId, $secret);
    }
}
