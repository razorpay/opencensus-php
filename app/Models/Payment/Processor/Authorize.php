<?php

namespace RZP\Models\Payment\Processor;

use Constants\Mode;
use EE\Exception;
use EE\Error;
use EE\Error\ErrorCode;
use Http\Route;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Customer;
use RZP\Models\Customer\Token;
use RZP\Models\Emi;
use RZP\Models\Payment;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Transaction;
use RZP\Models\Order;
use Trace\Trace;
use Trace\TraceCode;
use Mail;
use Lib\PhoneBook;

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

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
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

        $this->updateAndNotifyPaymentAuthorized();

        $payment = $this->payment;

        if ($payment->isSigned())
        {
            // If payment is signed, then we capture it in this step only.
            $payment = $this->capturePayment($payment, $payment->getAmount());
        }

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
     * This is a hack authorize function specially for authorizing
     * migs pg payments. The limit there is that, migs provides
     * reconciliation only for three days. If we miss any failed payment
     * reconciliation there then we need to do it manually later.
     *
     * @param Payment\Entity $payment
     * @param array $input
     * @return array $payment
     * @throws Exception\BadRequestValidationFailureException
     */
    public function forceAuthorizeFailedPayment($payment, $input)
    {
        $this->setPayment($payment);

        if ($payment->isFailed() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Non failed payment given for authorization');
        }

        if ($payment->getGateway() !== Payment\Gateway::AXIS_MIGS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Can force authorize only on axis migs gateway');
        }

        $this->repo->transaction(function() use ($payment, $input)
        {
            $data = array('payment' => $payment->toArray(), 'gateway' => $input);

            $flag = $this->callGatewayFunction('forceAuthorizeFailed', $data);

            if ($flag === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $this->lockForUpdateAndReload($payment);

            assert ($payment->isFailed() === true);

            $payment->setErrorNull();
            $payment->setVerified(true);

            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized(true);

            $this->repo->saveOrFail($payment);
        });

        // TODO: Remove reload once the branch hotfix/authorize-transaction-save is merged.
        return $payment->reload()->toArrayAdmin();
    }

    /**
     * It does the following -
     * Verifies payment method, if it's enabled for the merchant or not.
     * Saves card entities, bank account, etc.
     * Selects a terminal, based on the gateway.
     * Validates if international is allowed or not.
     *
     * @param RZP/Models/Payment/Entity $payment Payment entity that needs to be processed.
     * @param array $input Input data received from checkout/merchant.
     * @param array $gatewayInput Data that is required by gateway for the payment to be processed.
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     */
    protected function prePaymentAuthorizeProcessing($payment, $input, array & $gatewayInput)
    {
        $this->verifyPaymentMethodEnabled($payment, $input);

        // also sets the card details in $gatewayInput (passed by reference), if applicable.
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        // Sets gateway and terminal for the payment.
        (new TerminalPicker)->selectTerminal($payment, $this->mode);

        $this->repo->saveOrFail($payment);

        $this->trace(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        $this->validateInternationalAllowed($payment);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        if ($payment->order)
        {
            $gatewayInput['order'] = $payment->order->toArray();
        }
    }

    protected function dummyPrePaymentAuthorizeProcessing($payment, $input)
    {
        $gatewayInput = [];

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);
    }

    protected function parseContact($contact)
    {
        // Constructor does the basic validation
        $phoneBook = new PhoneBook($contact, true);

        // Setting an instance just like carbon
        return $phoneBook;
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
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);

            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    protected function runAuthorizeFailedTransaction($payment)
    {
        $this->repo->transaction(function() use ($payment)
        {
            $data = array('payment' => $payment->toArray());

            if ($payment->isMethodCardOrEmi())
            {
                $data['card'] = $payment->card->toArray();
            }

            $flag = $this->callGatewayFunction('authorizeFailed', $data);

            if ($flag === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment expected to have succeeded on the gateway has actually not. ' .
                    'Should not have called this function in this scenario');
            }

            $this->lockForUpdateAndReload($payment);

            if ($payment->isStatusCreatedOrFailed() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment being authorized is actually already authorized by some other thread.',
                    null,
                    ['payment_id' => $payment->getId()]);
            }

            $payment->setErrorNull();
            $payment->setVerified(true);

            // The first argument marks the payment as converted from failed
            // to authorized
            $this->updateAndNotifyPaymentAuthorized(true);

            $this->repo->saveOrFail($payment);

            $this->setPayment($payment);
        });
    }

    protected function runPaymentMethodRelatedPreProcessing($payment, & $input, array & $gatewayInput)
    {
        // First fetch the relevant customer
        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp($input, $this->merchant);

        // if local card saving, associate customer with payment
        if (($customer !== null) and ($customer->isLocal() === true))
        {
            $this->payment->customer()->associate($customer);
        }

        // for global card saving, associate app with payment
        if (($customerApp !== null) and ($customer->isLocal() === false))
        {
            $this->payment->app()->associate($customerApp);
        }

        // If token is set, then that means we have a saved card
        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $this->preProcessPaymentFromSavedCard($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserData($customer, $payment, $input, $gatewayInput);
        }

        if ($payment->isMethod(Payment\Method::EMI))
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            $this->setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration);
        }
    }

    protected function preProcessPaymentFromSavedCard($customer, $payment, & $input, & $gatewayInput)
    {
        $tokenInput = $input[Payment\Entity::TOKEN];

        // Customer should definitely exist in this case.
        if ($customer === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Customer does not exist');
        }

        // Token should definitely exist in database.
        $token = (new Token\Repository)->getByTokenAndCustomerId(
                                            $tokenInput, $customer->getId());

        assert ($token !== null);

        if ($customer->isLocal())
        {
            // Local customer, get token, get card, job done.
            if ($payment->isMethodCardOrEmi())
            {
                $gatewayInput['card'] = $this->getCardArrayForSavedToken($token, $input);
            }
        }
        else
        {
            // Global customer
            if ($payment->isMethodCardOrEmi())
            {
                $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input);

                $payment->card->globalCard()->associate($token->card);

                $this->repo->saveOrFail($payment->card);
            }
            else if ($payment->isMethod(Payment\Method::WALLET))
            {
                $payment->setBank($token->getWallet());
            }
            else if ($payment->isMethod(Payment\Method::BANK))
            {
                $payment->setWallet($token->getBank());
            }
        }
    }

    protected function preProcessPaymentFromUserData($customer, $payment, $input, & $gatewayInput)
    {
        // Flow if card details are entered with save set to true/false
        $saveMethod = ((isset($input['save'])) and (boolval($input['save']) === true));

        if ($saveMethod === false)
        {
            // No card saving, normal simple flow
            if ($payment->isMethodCardOrEmi())
            {
                $vault = $payment->isMethod(Payment\Method::EMI);

                $gatewayInput['card'] = $this->createCardEntity($input['card'], $vault, $this->merchant);
            }
        }
        else
        {
            // Card needs to be saved
            $this->savePaymentMethod($customer, $payment, $input, $gatewayInput);

            unset($input['save']);
        }
    }

    protected function savePaymentMethod($customer, $payment, $input, array & $gatewayInput)
    {
        $saveMethodInput = array(
            'method'      => $payment->getMethod(),
        );

        if ($payment->isMethodCardOrEmi())
        {
            // Create a global or local saved card entity
            $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

            $savedCard = $payment->card;

            $saveMethodInput['method'] = Payment\Method::CARD;

            $saveMethodInput['card_id'] = $savedCard->getId();

            if ($customer->isLocal() === false)
            {
                // Create a local card entity specific to merchant.
                // Link to parent global card entity and to payment entity.

                $gatewayInput['card'] = $this->createCardEntity($input['card'], false, $this->merchant);

                $payment->card->globalCard()->associate($savedCard);

                $this->repo->saveOrFail($payment->card);
            }

        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $saveMethodInput['bank'] = $payment->getBank();
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $saveMethodInput['wallet'] = $payment->getWallet();
        }

        try
        {
            (new Token\Core)->create($customer, $saveMethodInput);
        }
        catch (Exception\RecoverableException $e)
        {
            // Ignore the exception, can be an already saved method
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }
    }

    protected function verifyPaymentMethodEnabled($payment, $input)
    {
        $paymentMethod = $payment->getMethod();

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
                $this->verifyCardEnabledInLive($input);
                break;

            case Payment\Method::NETBANKING:
                $this->verifyBankEnabled($payment);
                break;

            case Payment\Method::WALLET:
                $this->verifyWalletEnabled($payment);
                break;

            case Payment\Method::EMI:
                $this->verifyEmiEnabled();
                break;

            default:
                throw new Exception\LogicException(
                    'Should not reach here.',
                    ['payment_method' => $paymentMethod]);
        }
    }

    protected function setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration)
    {
        // Set the bank
        $iin = substr($cardNumber, 0, 6);

        $iinEntity = (new IIN\Repository)->findOrFail($iin);

        if (IIN\IIN::isEmiAvailableForCard($iinEntity, $cardNumber) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_AVAILABLE_ON_CARD);
        }

        $payment->setBank($iinEntity->getIssuer());

        // Set emi plan id
        $emiPlan = (new Emi\Repository)->fetchByBankAndDuration(
                        $iinEntity->getIssuer(), $emiDuration);

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

        $amount = $payment->getAmount() / 100;
        $data['amount'] = sprintf($amount == intval($amount) ? "%d" : "%.2f", $amount);
        $data['image'] = $payment->merchant->getFullLogoUrlWithSize();

        return $data;
    }

    protected function updateAndNotifyPaymentAuthorized($wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $this->updatePaymentAuthorized();

        $this->eventPaymentAuthorized();

        $this->notifyAuthorized($wasFailed);
    }

    protected function updateAuthorizedOrderStatus($payment)
    {
        $order = $payment->order;

        if (isset($order))
        {
            $order->setAuthorized(true);

            $this->repo->saveOrFail($order);
        }
    }

    /**
     * This function is just meant for preparing the return value
     * after payment authorize processing. This should not contain
     * any state updating statements.
     */
    protected function postPaymentAuthorizeProcessing($payment)
    {
        //
        // If it's signed payment, then we return signed data from our
        // end as well.
        //
        // If callback url has been set, then we need to redirect
        // to the callback url and prepare data using coproto protocol.
        //
        // Otherwise we simply return 'razorpay_payment_id' as is normal.
        //

        if ($payment->isSigned())
        {
            return $this->getReturnDataForSignedPayment($payment);
        }

        if ($payment->getCallbackUrl())
        {
            return $this->getReturnRequestDataForMerchant($payment);
        }

        return ['razorpay_payment_id' => $payment->getPublicId()];
    }

    protected function getReturnDataForSignedPayment($payment)
    {
        $data = array(
            'razorpay_payment_id'   => $payment->getPublicId(),
            'amount'                => $payment->getAmount(),
            'currency'              => $payment->getCurrency(),
            'merchant_order_id'     => $payment->getNotes()['merchant_order_id'],
        );

        $sortedData = $data;
        ksort($sortedData);

        $str = implode('|', $sortedData);

        $data['signature'] = $this->getSignature($str);

        return $data;
    }

    protected function notifyAuthorized($wasFailed)
    {
        // Trigger notification events for authorization
        $notifier = new Notify($this->payment);

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

    protected function eventPaymentAuthorized()
    {
        $this->app['events']->fire('api.payment.authorized', array($this->payment));
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

        $message = 'Payment failed earlier converte to authorized';

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
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed(
                    $e->getError(),
                    TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    /**
     * Do we support the OTP flow for a given payment
     * and input combination
     * @param  Payment\Entity $payment
     * @param  array $input
     * @return boolean
     */
    protected function canRunOtpPaymentFlow($payment, $input)
    {
        $wallet = $payment->getWallet();

        // Only wallets have otp flow currently.
        // Plus, only power wallets support otp flow.
        if (($payment->isWallet() === false) or
            (Payment\Gateway::isPowerWallet($wallet) === false))
        {
            return false;
        }

        //
        // Special case check for mobikwik
        // Only checkout currently supports otp flow
        // not on android so we need to check for source. Slightly hacky.
        //

        if ($wallet === Wallet::MOBIKWIK)
        {
            $sources = ['checkoutjs', 's2s'];

            if ((isset($input['_']['source']) === false) or
                (in_array($input['_']['source'], $sources) === false))
            {
                return false;
            }
        }

        return true;
    }

    protected function runOtpPaymentFlow($gatewayInput, $payment)
    {
        return $this->callGatewayOtpGenerate($gatewayInput, $payment);
    }

    protected function callGatewayOtpGenerate($data, $payment)
    {
        try
        {
            $this->type = 'otp_generate';

            $this->callGatewayFunction('otpGenerate', $data);

            $payment->incrementOtpCount();
            $payment->save();

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

    protected function createCardEntity(array $cardInput, $vault, $merchant)
    {
        //
        // Creates card entity. Card number is vaulted if vault is true
        //

        if ($vault)
        {
            $vaultToken = Card\Tokenex::getVaultToken($cardInput['number']);

            if (empty($vaultToken) === false)
            {
                $cardInput[Card\Entity::VAULT_TOKEN] = $vaultToken;
                $cardInput[Card\Entity::VAULT] = Card\Vault::TOKENEX;
            }
        }

        $cardCore = new Card\Core();

        $cardData = $cardCore->createAndReturnWithSensitiveData($cardInput, $merchant);

        $card = $cardCore->getCard();

        if ($card->isUnsupported())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED);
        }

        $this->payment->card()->associate($card);

        $this->repo->saveOrFail($card);

        return $cardData;

    }

    protected function getCardArrayForSavedToken($token, $input)
    {
        $card = $token->card;

        $cardNumber = Card\Tokenex::getCardNumber($card->getVaultToken());
        $cvv = $input['card']['cvv'];

        $this->payment->card()->associate($card);

        return array_merge(
                $card->toArray(),
                ['number' => $cardNumber,
                 'cvv' => $cvv]);
    }

    protected function createCardEntityFromSavedToken($token, $input)
    {
        $cardNumber = Card\Tokenex::getCardNumber($token->card->getVaultToken());
        $cvv = $input['card']['cvv'];

        $savedCard = $token->card->toArray();
        $savedCard['number'] = $cardNumber;
        $savedCard['cvv'] = $cvv;

        //create a card entity for merchant
        $cardCore = new Card\Core();

        $card = $cardCore->createDuplicateCard($savedCard, $this->merchant);

        $this->payment->card()->associate($card);

        return array_merge(
            $card->toArray(),
            ['number' => $cardNumber,
             'cvv' => $cvv]);
    }

    protected function verifyBankEnabled($payment)
    {
        $merchant = $payment->merchant;

        $merchantMethods = (new Methods\Core)->getMethods($merchant);

        $merchantBanks = ($merchantMethods === null) ? [] : $merchantMethods->getBanks();

        $paymentBank = $payment->getBank();

        if (in_array($paymentBank, $merchantBanks) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyWalletEnabled($payment)
    {
        $merchantMethods = $this->methods;

        $paymentWallet = $payment->getWallet();

        if (($merchantMethods === null) or
            ($merchantMethods->isWalletEnabled($paymentWallet) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyEmiEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_ENALBED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabledInLive($input)
    {
        $merchantMethods = $this->methods;

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $input['card']);

        // Only check enabled or not on live mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        if (($merchantMethods === null) or
            ($merchantMethods->isCardEnabled() === false))
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

        $cardNumber = $card['number'];

        $prefix = substr($cardNumber, 0, 2);

        if ((($prefix === '34') or ($prefix === '37')) and
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
        $this->repo->saveOrFail($this->payment->card);

        $this->repo->saveOrFail($this->payment);
    }

    protected function updatePaymentAuthorized()
    {
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment)
        {
            $this->lockForUpdateAndReload($payment);

            if ($this->payment->getStatus() === Status::AUTHORIZED)
            {
                return;
            }

            $payment->setErrorNull();

            $payment->setAmountAuthorized();

            $payment->setStatus(Payment\Status::AUTHORIZED);

            $payment->setAuthorizeTimestamp();

            $payment->terminal->incrementUsedCount();

            $this->repo->saveOrFail($payment);
            $this->repo->saveOrFail($payment->terminal);

            if ($this->isGatewayActuallyAuthorizingPayment($payment) === false)
            {
                // Also sets the transaction association with the payment.
                $txn = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                $this->repo->saveOrFail($txn);
            }

            $this->repo->saveOrFail($payment);

            // If payment has an associated order
            // set the order to be paid
            $this->updateAuthorizedOrderStatus($payment);

            $this->trace(TraceCode::PAYMENT_AUTH_SUCCESS);
        });
    }

    protected function isGatewayActuallyAuthorizingPayment($payment)
    {
        $gateway = $payment->getGateway();

        $networkCode = null;
        $paymentCard = $payment->card;

        // If payment method is wallet or net banking.
        if ($paymentCard !== null)
        {
            $networkCode = $paymentCard->getNetworkCode();
        }

        if (Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode) === false)
        {
            return false;
        }

        return true;
    }

    protected function getEncryptedGatewayText($gateway)
    {
        return \Crypt::encrypt($gateway . '__' . time());
    }


    protected function verifyHash($hash, $paymentPublicId)
    {
        $expectedHash = $this->getHashOf($paymentPublicId);

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

        $hash = $this->getHashOf($publicId);

        return ['id' => $publicId, 'hash' => $hash];
    }

    /**
     * Returns a hash of a string.
     *
     * @param string $string
     * @return string Hash of the string
     */
    protected function getHashOf($string)
    {
        $secret = $this->app->config->get('app.key');

        return hash_hmac('sha1', $string, $secret);
    }
}
