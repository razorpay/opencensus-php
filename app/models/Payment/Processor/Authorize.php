<?php

namespace Models\Payment\Processor;

use Constants\Mode;
use EE\Exception;
use EE\Error;
use EE\Error\ErrorCode;
use Http\Route;
use Models\Merchant\Methods;
use Models\Card;
use Models\Card\IIN;
use Models\Emi;
use Models\Payment;
use Models\Payment\Method;
use Models\Transaction;
use Models\Order;
use Trace\Trace;
use Trace\TraceCode;
use Mail;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    public function authorize($payment, $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        $this->prePaymentAuthorizeProcessing($payment, $input, $gatewayInput);

        if ($this->canRunOtpPaymentFlow($payment, $input))
        {
            return $this->runOtpPaymentFlow($gatewayInput, $payment);
        }

        $request = $this->callGatewayAuthorize($gatewayInput);

        //
        // If $request is not null, then payment is two-step process
        // where client needs to provide additional info via his browser.
        //
        if ($request !== null)
        {
            return $this->getPaymentGatewayRequestData($request, $payment);
        }

        $this->updateAndNotifyPaymentAuthorized($payment);

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    public function authorizeFailedPayment($payment)
    {
        $this->setPayment($payment);

        if ($payment->isStatusCreatedOrFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization where failed payment is needed');
        }

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            ['payment_id' => $payment->getId()]);

        $this->runAuthorizeFailedTransaction($payment);

        $this->traceAuthorizeFailedOperationData($payment);

        return $payment->toArrayAdmin();
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

        if ($payment->card !== null)
        {
            $input['card'] = $payment->card->toArray();
        }

        $this->checkForRecentFailedPayment($payment);

        Payment\Validator::bankAcsCallbackValidate($payment, $input);

        try
        {
            $data = $this->callGatewayFunction(Payment\Action::CALLBACK, $input);
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                $e->getError(),
                TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }

        $this->updateAndNotifyPaymentAuthorized($payment);

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function prePaymentAuthorizeProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment, $input);

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        $this->repo->saveOrFail($payment);

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        $this->validateInternationalAllowed($payment);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();
    }

    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $gatewayInput = [];

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);
    }

    protected function validateInternationalAllowed($payment)
    {
        if ($payment->getMethod() !== Method::CARD)
        {
            return;
        }

        $card = $payment->card;
        $merchant = $payment->merchant;

        if (($card->isInternational() === true) and
            ($merchant->isInternational() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);
        }
    }

    protected function runAuthorizeFailedTransaction($payment)
    {
        $this->repo->transaction(function() use ($payment)
        {
            $data = array('payment' => $payment->toArray());

            $this->repo->lockForUpdate($payment->getKey());

            $flag = $this->callGatewayFunction('authorizeFailed', $data);

            if ($flag === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $payment->setErrorNull();
            $payment->setVerified(true);

            // The second argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized($payment, true);

            $this->repo->saveOrFail($payment);
        });
    }

    protected function runPaymentMethodRelatedPreProcessing($payment, $input, array & $gatewayInput)
    {
        $save = false;

        if ($payment->isMethod(Payment\Method::EMI))
        {
            $save = true;
        }

        if (($payment->isMethod(Payment\Method::CARD)) or
            ($payment->isMethod(Payment\Method::EMI)))
        {
            $gatewayInput['card'] = $this->createCardEntity($input, $save);
        }

        if ($payment->isMethod(Payment\Method::EMI))
        {
            $this->setBankAndEmiPlanDetails($payment, $input);
        }
    }

    protected function verifyPaymentMethodEnabled($payment, $input)
    {
        if ($payment->isMethod(Payment\Method::CARD))
        {
            $this->verifyCardEnabledInLive($payment, $input);
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $this->verifyBankEnabled($payment);
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $this->verifyWalletEnabled($payment);
        }
        else if ($payment->isMethod(Payment\Method::EMI))
        {
            $this->verifyEmiEnabled($payment);
        }
    }

    protected function setBankAndEmiPlanDetails(& $payment, $input)
    {
        // Set the bank
        $iin = substr($input['card']['number'], 0, 6);

        $iinEntity = (new IIN\Repository)->findOrFail($iin);

        if (($iinEntity->isEmiAvailable() === false) or
            (IIN\IIN::isValidCardForBank($iinEntity->getIssuer(), $input['card']['number'])) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD);
        }

        $payment->setBank($iinEntity->getIssuer());

        // Set emi plan id
        $emiPlan = (new Emi\Repository)->fetchByBankAndDuration(
                        $iinEntity->getIssuer(), $input['emi_duration']);

        $payment->setEmiPlanId($emiPlan->getId());
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

        $data['gateway'] = $this->getEncryptedGatewayText($payment->getGateway());

        return $data;
    }

    protected function updateAndNotifyPaymentAuthorized($payment, $wasFailed = false)
    {
        $this->updatePaymentAuthorized();

        $this->eventPaymentAuthorized($payment);

        $this->notifyAuthorized($payment, $wasFailed);
    }

    protected function updateAuthorizedOrderStatus($payment)
    {
        $order = $payment->order;

        if (isset($order))
        {
            $order->setAuthorized(true);

            $order->saveOrFail();
        }
    }

    protected function postPaymentAuthorizeProcessing($payment)
    {
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

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    protected function notifyAuthorized($payment, $wasFailed)
    {
        // Trigger notification events for authorization
        $notifier = new Notify($payment);

        if ($wasFailed)
        {
            $trigger = Notify::FAILED_TO_AUTHORIZED;
        }
        else
        {
            $trigger = Notify::AUTHORIZED;
        }

        $notifier->trigger($trigger);
    }

    protected function eventPaymentAuthorized($payment)
    {
        $this->app['events']->fire('api.payment.authorized', array($payment));
    }

    protected function checkForRecentFailedPayment($payment)
    {
        // Difference should be less than 30 minutes
        $diff = time() - $payment->getUpdatedAt();

        if (($payment->isFailed()) and
            ($diff < 30 * 60))
        {
            $this->rethrowFailedPaymentErrorException($payment);
        }
    }

    protected function traceAuthorizeFailedOperationData($payment)
    {
        $traceData = array(
            'payment_id' => $payment->getId(),
            'error' => $payment->getErrorDetails(),
        );

        $message = 'Payment failed earlier converted to authorized';

        $slackData = ['id' => $payment->getDashboardEntityLinkForSlack()];

        $this->slackPost($message, $slackData, ['color' => 'good', 'channel' => '#tech_logs']);

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            $traceData);
    }

    protected function rethrowFailedPaymentErrorException($payment)
    {
        $internalErrorCode = $payment->getInternalErrorCode();
        $publicErrorCode = $payment->getErrorCode();
        $errorDesc = $payment->getErrorDescription();

        Error\Map::throwExceptionFromErrorDetails(
            $publicErrorCode, $internalErrorCode, $errorDesc);

        //
        // If it has reached here, then an edge case occurred, for which
        // a suitable exception was not found and which must be handled.
        // So, we trace an error message, ringing alerts to our devs.
        //

        $this->trace->error(
            TraceCode::PAYMENT_CALLBACK_FAILURE,
            ['payment_id' => $payment->getPublicId(),
             'public_error_code' => $publicErrorCode,
             'internal_error_code' => $internalErrorCode,
             'error_description' => $errorDesc,
             'message' => 'Failed to convert error code to the appropriate exception']);

        // If no appropriate exception mapping was found then show
        // the usual message that payment already processed.

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
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

    protected function canRunOtpPaymentFlow($payment, $input)
    {
        return ((isset($input['_']['source'])) and
                ($input['_']['source'] === 'checkoutjs') and
                ($payment->getMethod() === Method::WALLET) and
                ($payment->getWallet() === Wallet::MOBIKWIK));
    }

    protected function runOtpPaymentFlow($gatewayInput, $payment)
    {
        return $this->callGatewayMobikwikOtpGenerate($gatewayInput, $payment);
    }

    protected function callGatewayMobikwikOtpGenerate($data, $payment)
    {
        try
        {
            $this->type = 'otp_generate';

            $this->callGatewayFunction('checkExistingUser', $data);

            $this->callGatewayFunction('otpGenerate', $data);

            return array(
                'type' => 'otp',
                'request' => [
                    'url' => $this->getOtpSubmitUrl(),
                    'method' => 'post',
                ],
                'version' => 1,
                'payment_id' => $payment->getPublicId(),
                'gateway' => $this->getEncryptedGatewayText($payment->getGateway()),
            );
        }
        catch (Exception\BaseException $e)
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
    public function createCardEntity(array $input, $save = false)
    {
        //
        // Creates card entity. if save flag is set to true
        // number is stored with tokenex, and token is stored
        // in card entity. we do not store cvv for now.
        // we get back a card data array contianing Card\Entity
        // with number and cvv
        //
        $cardInput = $input['card'];

        if($save)
        {
            $token = $this->getCardToken($cardInput['number']);

            if (empty($token) === false) 
            {
                $cardInput[Card\Entity::TOKEN] = $token;
                $cardInput[Card\Entity::SERVICE] = 'tokenex';            
            }
        }

        $cardCore = new Card\Core();
        
        $cardData = $cardCore->createAndReturnWithSensitiveData($cardInput, $this->merchant);
        
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

    protected function getCardToken($cardNumber)
    {
        $app = \App::getFacadeRoot();

        try 
        {
            $token = $app['card.tokenex']->tokenize($cardNumber);            
        } 
        catch (Exception $e) 
        {
            $this->trace->info(
                TraceCode::TOKENEX_REQUEST,
                "failed to tokenize data");                   
        }

        return $token;
    }

    protected function verifyBankEnabled($payment)
    {
        $merchant = $payment->merchant;

        $banks = (new Methods\Core)->getMethods($merchant);

        $banks = ($banks === null) ? [] : $banks->getBanks();

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

        $wallet = $payment->getWallet();

        if (($methods === null) or
            ($methods->isWalletEnabled($wallet) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyEmiEnabled($payment)
    {
        $methods = $this->methods;

        if (($methods === null) or
            ($methods->isEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabledInLive($payment, $input)
    {
        $methods = $this->methods;

        $this->checkAndValidateAmexIfNotEnabled($methods, $input['card']);

        if ($this->mode === Mode::TEST)
        {
            return;
        }

        // Only check enabled or not on live mode

        if (($methods === null) or
            ($methods->isCardEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function checkAndValidateAmexIfNotEnabled($methods, $card)
    {
        if (isset($card['number']) === false)
        {
            return;
        }

        $amex = $methods->getAmex();

        $num = $card['number'];

        $prefix = substr($num, 0, 2);

        if ((($prefix === '34') or
             ($prefix === '37')) and
            ($amex === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                'number');
        }
    }

    protected function checkForMerchantCallbackUrl($payment)
    {
        if ($payment->getCallbackUrl() !== null)
        {
            $this->app['rzp.merchant_callback_url'] = $payment->getCallbackUrl();
        }
    }

    protected function savePaymentAndCard()
    {
        (new Card\Repository)->saveOrFail($this->payment->card);

        $this->repo->saveOrFail($this->payment);
    }

    protected function updatePaymentAuthorized()
    {
        $this->repo->transaction(function()
        {
            $payment = $this->payment;

            $payment->setAmountAuthorized();

            $payment->setStatus(Payment\Status::AUTHORIZED);

            $payment->setAuthorizeTimestamp();

            $payment->terminal->incrementUsedCount();

            $payment->saveOrFail();
            $payment->terminal->saveOrFail();

            $gateway = $payment->getGateway();

            if ($this->isGatewayActuallyAuthorizingPayment($payment) === false)
            {
                $txn = (new Transaction\Core)->createFromPaymentAuthorized($this->payment);

                $txn->saveOrFail();
            }

            $payment->saveOrFail();

            // If payment has an associated order
            // set the order to be paid
            $this->updateAuthorizedOrderStatus($payment);

            $this->trace(TraceCode::PAYMENT_AUTH_SUCCESS);
        });
    }

    protected function isGatewayActuallyAuthorizingPayment($payment)
    {
        $gateway = $payment->getGateway();

        if (Payment\Gateway::supportsAuthAndCapture($gateway) === false)
        {
            return false;
        }

        if ($gateway === Payment\Gateway::HDFC)
        {
            $network = $payment->card->getNetwork();
            $network = Card\Network::getCode($network);

            if (($network === Card\Network::MAES) or
                ($network === Card\Network::RUPAY))
            {
                return false;
            }
        }

        return true;
    }

    protected function getEncryptedGatewayText($gateway)
    {
        return \Crypt::encrypt($gateway . '__' . time());
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
        $params = $this->getPaymentIdAndHashParams();

        $callbackUrl = Route::getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    protected function getOtpSubmitUrl()
    {
        $params = $this->getPaymentIdAndHashParams();

        $otpSubmitUrl = Route::getUrlWithPublicAuth('payment_otp_submit', $params);

        return $otpSubmitUrl;
    }

    protected function getPaymentIdAndHashParams()
    {
        $publicId = $this->payment->getPublicId();

        $hash = $this->getHashOfPaymentPublicId();

        return ['id' => $publicId, 'hash' => $hash];
    }

    /**
     * Returns a hash of payment public id.
     *
     * @return string Hash of payment public id
     */
    protected function getHashOfPaymentPublicId()
    {
        $secret = $this->app->config->get('app.key');

        $publicId = $this->payment->getPublicId();

        return hash_hmac('sha1', $publicId, $secret);
    }
}
