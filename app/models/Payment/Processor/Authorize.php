<?php

namespace Models\Payment\Processor;

use App;
use Constants\Mode;
use EE\Exception;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Merchant\Methods;
use Models\Card;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;
use Mail;

trait Authorize
{
    public function authorize($payment, $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        $this->verifyPaymentMethodEnabled($payment);

        if ($payment->isMethod(Payment\Method::CARD))
        {
            $gatewayInput['card'] = $this->createCardEntity($input);
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
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    public function authorizeFailedPayment($payment)
    {
        $this->setPayment($payment);

        if ($payment->isFailed() === false)
        {
            throw new Exception\InvalidArgumentException(
                'Non failed payment given for authorization where failed payment is needed',
                ['payment_id' => $payment->getPublicId()]);
        }

        $data = array(
            'payment' => $payment->toArray(),
        );

        $this->repo->transaction(function() use ($data)
        {
            $this->repo->lockForUpdate($this->payment->getKey());

            $flag = $this->callGatewayFunction('authorizeFailed', $data);

            if ($flag === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $payment = $this->payment;

            $payment->setErrorNull();
            $payment->setVerified(true);

            $this->postPaymentAuthorizeProcessing($payment);

            $this->repo->saveOrFail($payment);
        });

        $traceData = array(
            'payment_id' => $payment->getId(),
            'error' => $payment->getErrorDetails(),
        );

        $data['message'] = 'Payment failed earlier converted to authorized';
        $data['payment'] = $payment->toArrayAdmin();

        $this->notifyInSlack($data);

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            $traceData);

        return $data;
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

        // For redirect flow
        $this->checkForMerchantCallbackUrl($payment);

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

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function verifyPaymentMethodEnabled($payment)
    {
        if ($payment->isMethod(Payment\Method::CARD))
        {
            $this->verifyCardEnabled($payment);
        }

        if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $this->verifyBankEnabled($payment);
        }

        if ($payment->isMethod(Payment\Method::WALLET))
        {
            $this->verifyWalletEnabled($payment);
        }
    }

    protected function getReturnRequestDataForMerchant($payment)
    {
        assert ($payment->getCallbackUrl() !== null);

        $data = array(
            'version' => 1,
            'type' => 'return',
            'request' => [
                'url' => $payment->getCallbackUrl(),
                'method' => 'post',
                'content' => array(
                    'razorpay_payment_id' => $payment->getPublicId(),
                ),
            ],
        );

        return $data;
    }

    protected function getMerchantCallbackUrl($payment)
    {
        return $this->payment->getCallbackUrl();
    }

    protected function getPaymentGatewayRequestData($request, $payment)
    {
        $data['type'] = 'first';
        $data['request'] = $request;
        $data['version'] = 1;
        $data['payment_id'] = $payment->getPublicId();

        $data['gateway'] = \Crypt::encrypt($payment->getGateway() . '__' . time());

        return $data;
    }

    protected function postPaymentAuthorizeProcessing($payment)
    {
        $this->updatePaymentAuthorized();

        //
        // The returned value could be either Payment
        // model or an array containing callback data.
        // We convert payment model to array
        // if it's a payment model
        //
        if ($payment->isSigned())
        {
            return $this->captureSignedPayment($payment);
        }

        if ($payment->getCallbackUrl())
        {
            return $this->getReturnRequestDataForMerchant($payment);
        }

        if ($payment->merchant->isReceiptEmailsEnabled())
        {
            // Send email to the customer
            // Can be extended later for SMS as well
            $this->notifyCustomer($payment);
        }

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    protected function notifyCustomer($payment)
    {
        $app = App::getFacadeRoot();

        // Dont send mails in test mode
        // @todo: remove this somehow
        if (($this->mode === Mode::TEST) and
            ($app->environment('dev') === false))
        {
            return;
        }

        $templateData = [
            'customer'  =>  [
                'email' =>  $payment->getEmail(),
                'phone' =>  $payment->getContact()
            ],
            'merchant'  =>  [
                'billing_label' =>  $payment->merchant->getBillingLabel(),
                'website'       =>  $payment->merchant->getWebsite()
            ],
            'payment'   =>  [
                'id'        =>  $payment->getId(),
                'amount'    =>  "INR ".number_format($payment['amount']/100, 2),
                'timestamp' =>  $payment->getUpdatedAt(),
                'method'    =>  $payment->getMethodWithDetail()
            ]
        ];

        $config = $app->config->get('applications.mailgun');

        $subject = "Payment Successful for {$templateData['payment']['amount']}";

        if (isset($templateData['merchant']['billing_label']))
        {
            $subject = "Payment Successful for {$templateData['merchant']['billing_label']}";
        }

        $app['mailer']->queue(
            [
                'html' => 'emails/payment/customer',
                'text'=> 'emails/payment/customer_text'
            ],
            $templateData,
            function ($message) use ($templateData, $config, $subject)
            {
                $message->to($templateData['customer']['email']);
                $message->from($config['from_email'], $config['from_name']);
                $message->subject($subject);
            }
        );
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

        (new Card\Repository)->saveOrFail($card);

        return $cardData;
    }

    protected function verifyBankEnabled($payment)
    {
        $merchant = $payment->merchant;

        $banks = (new Methods\Core)->getMerchantBanks($merchant);

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

    protected function verifyWalletEnabled($payment)
    {
        $methods = $this->methods;

        if (($methods === null) or
            ($methods->getPaytm() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabled($payment)
    {
        $methods = $this->methods;

        if (($methods === null) or
            ($methods->isCardEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function checkForMerchantCallbackUrl($payment)
    {
        if ($payment->getCallbackUrl() !== null)
        {
            $app = \App::getFacadeRoot();
            $app['rzp.merchant_callback_url'] = $payment->getCallbackUrl();
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

        $payment->setAuthorizeTimestamp();

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
