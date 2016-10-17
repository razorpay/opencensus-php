<?php

namespace RZP\Models\Payment\Processor;

use App;
use Crypt;
use Mail;
use Config;

use Carbon\Carbon;
use Lib\PhoneBook;
use RZP\Models\Emi;
use RZP\Http\Route;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Pricing;
use RZP\Constants\Mode;
use RZP\Models\Card\IIN;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Transaction;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Methods;
use RZP\Models\Payment\Analytics;
use RZP\Models\Payment\Analytics\Entity as AnalyticsEntity;
use RZP\Models\Payment\TerminalAnalytics;


use RZP\Error;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    public function authorize(Payment\Entity $payment, array $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->prePaymentAuthorizeProcessing($payment, $input, $gatewayInput);

        $this->selectedTerminals = (new TerminalProcessor)->getTerminalsForPayment($payment);

        return $this->authorizeAcrossTerminals($payment, $input, $gatewayInput);
    }

    protected function authorizeAcrossTerminals(Payment\Entity $payment, array $input, array $gatewayInput)
    {
        $totalTerminals = count($this->selectedTerminals);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = null;

        $retry = false;

        // We are attempting to rotate across multiple terminals to get a successful payment here.
        // For each of the terminals tried, we want to record the terminal metrics using recordTerminalAudit()
        // At the end of a successful/failed payment, we want to record the payment details
        // using createAnalyticsLog. There could be cases where terminal #1 failed and terminal #2 succeeded.
        // In the above scenario, we will have 2 records in terminal analytics, but only one record
        // for the entire payment in payment analytics. The terminal chosen here in payment analytics
        // will be the last terminal tried.

        while ($retryAttempts < $maxRetryAttempts)
        {
            $terminalGatewayInput = $gatewayInput;

            $currentTerminal = $this->selectedTerminals[$retryAttempts];

            $payment->associateTerminal($currentTerminal);

            $this->runPostGatewaySelectionPreProcessing($payment, $terminalGatewayInput);

            if ($this->canRunOtpPaymentFlow($payment, $input))
            {
                $this->createAnalyticsLog($payment);

                $request = $this->runOtpPaymentFlow($terminalGatewayInput, $payment);

                return $request;
            }

            // data for terminal analytics
            $terminalData = [
                            'payment_id'    => $payment['id'],
                            'input'         => $input,
                            'terminal_id'   => $payment['terminal_id'],
                            'start'         => microtime(true),
                        ];

            try
            {
                $request = $this->callGatewayAuthorize($terminalGatewayInput);

                $retry = false;

                break;
            }
            catch (Exception\GatewayRequestException $e)
            {
                // record a failed payment for given terminal and continue
                $terminalData['exception'] = $e;

                $retryAttempts += 1;

                $retry = $this->logAndCheckForAuthRetry($e, $payment);

                if (($retry === true) and
                    ($retryAttempts < $maxRetryAttempts))
                {
                    continue;
                }

                $this->updatePaymentAuthFailedAndThrowException($e);
            }
            catch (Exception\BaseException $e)
            {
                //
                // An error occurred on gateway due to user or gateway.
                // We need to record this and mark payment as failed.
                //
                $terminalData['exception'] = $e;

                $this->updatePaymentAuthFailedAndThrowException($e);
            }
            finally
            {
                $terminalData['end'] = microtime(true);

                $this->recordTerminalAudit($terminalData);

                if (($retry === false) or
                    ($retryAttempts >= $maxRetryAttempts))
                {
                    $this->createAnalyticsLog($payment);
                }
            }
        }

        return $this->processAuthResponse($request, $payment);
    }

    protected function logAndCheckForAuthRetry($e, $payment)
    {
        $traceData = array(
            'error_code'    => $e->getCode(),
            'message'       => $e->getMessage(),
            'payment_id'    => $payment->getId(),
            'terminal_id'   => $payment->terminal->getId()
        );

        $this->trace->info(TraceCode::TERMINAL_FAILURE, $traceData);

        // retry only if it is safe to do so
        return ((property_exists($e, 'safeRetry') === true) and
                ($e->getSafeRetry() === true));
    }

    protected function updatePaymentAuthFailedAndThrowException($e)
    {
        $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

        throw $e;
    }

    protected function verifyFeesLessThanAmount($payment)
    {
        // try calculating the fees, throws exception if fees is more than amount
        list($fee, $serviceTax, $ruleKey) = (new Pricing\Fee)->calculateMerchantFees($payment);
    }

    protected function processAuthResponse($request, $payment)
    {
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

        return $this->postPaymentAuthorizeProcessing($payment);
    }

    protected function autoCapturePaymentIfApplicable($payment)
    {
        if ($this->shouldAutoCapture($payment) === true)
        {
            // If payment_capture was sent as true in order,
            // then we capture it in this step only.
            $this->autoCapturePayment($payment);
        }
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
    public function forceAuthorizeFailedPayment(Payment\Entity $payment, array $input = [])
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
     * @param RZP /Models/Payment/Entity $payment Payment entity that needs to be processed.
     * @param array $input Input data received from checkout/merchant.
     * @param array $gatewayInput Data that is required by gateway for the payment to be processed.
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\RuntimeException
     */
    protected function prePaymentAuthorizeProcessing($payment, $input, array & $gatewayInput)
    {
        // also sets the card details in $gatewayInput (passed by reference), if applicable.

        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->verifyMerchantFeatures($payment, $input);

        $this->verifyPaymentMethodEnabled($payment);

        return $gatewayInput;
    }

    protected function runPostGatewaySelectionPreProcessing($payment, array & $gatewayInput)
    {
        // International card validation happens here because we want to save the failure.
        // For payment creation, gateway is compulsory field which is only finalized in
        // previous step.
        $this->runInternationalChecks($payment);

        // Fees validation can only happen after international validation has gone through
        // otherwise can cause issues with international pricing rule being not available when
        // international is not enabled.
        $this->verifyFeesLessThanAmount($payment);

        $this->repo->saveOrFail($payment);

        $this->tracePaymentInfo(TraceCode::PAYMENT_CREATED, Trace::DEBUG);

        //
        // Call gateway input
        //
        $gatewayInput['payment'] = $payment->toArray();

        $gatewayInput['callbackUrl'] = $this->getCallbackUrl();

        if ($payment->order)
        {
            $gatewayInput['order'] = $payment->order->toArray();
        }

        // set token for local card saving in gateway input
        if ($payment->getTokenId() !== null)
        {
            $gatewayInput['token'] = $payment->localToken;
        }
    }

    protected function logTerminalPickedAndSelected($terminalSelected, $terminalPicked, $payment)
    {
        $terminalSelectionStatus = 'TERMINAL_SELECTION_MISMATCH';

        $terminalPickedId = $terminalPicked->getId();

        if ($terminalSelected)
        {
            $terminalSelectedId = $terminalSelected->getId();

            if ($terminalSelectedId === $terminalPickedId)
            {
                $terminalSelectionStatus = 'TERMINAL_SELECTION_MATCH';
            }
        }
        else
        {
            $terminalSelectedId = '';
        }

        $traceData = [
            'picked'     => $terminalPickedId,
            'selected'   => $terminalSelectedId,
            'status'     => $terminalSelectionStatus,
            'payment_id' => $payment->getId(),
        ];

        if ($terminalSelectionStatus === 'TERMINAL_SELECTION_MISMATCH')
        {
            $traceData['payment_id_link'] = $payment->getDashboardEntityLinkForSlack();

            $this->trace->warn(TraceCode::TERMINAL_SELECTION_MISMATCH, $traceData);
        }
        else
        {
            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
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

    protected function runInternationalChecks($payment)
    {
        // return if method is not card or card is not international
        if (($payment->getMethod() !== Method::CARD) or
            ($payment->card->isInternational() === false))
        {
            return;
        }

        $this->validateInternationalAllowed($payment);

        $this->validateFraudDetection($payment);

        $this->validateBlockedInternationalCard($payment->card);
    }

    protected function validateInternationalAllowed($payment)
    {
        $merchant = $payment->merchant;

        if ($merchant->isInternational() === false)
        {
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED);

            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

            throw $e;
        }
    }

    protected function validateBlockedInternationalCard($card)
    {
        if ($card->isBlocked())
        {
            $e = new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BLOCKED_DUE_TO_FRAUD);

            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

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

    protected function verifyMerchantFeatures($payment, $input)
    {
        $merchant = $payment->merchant;

        if ($payment->isRecurring() === true)
        {
            $this->verifyFeatureForMerchant($merchant, Merchant\Features::RECURRING);
        }

        if ((empty($input[Payment\Entity::TOKEN]) === false) and
            ($payment->isSecondRecurring() === true))
        {
            $this->verifyPrivateAuth();
        }
        else if ($this->app['basicauth']->isPrivateAuth() === true)
        {
            if($payment->isWallet())
            {
                $this->verifyFeatureForMerchant($merchant, Merchant\Features::S2SWALLET);
            }
            else
            {
                $this->verifyFeatureForMerchant($merchant, Merchant\Features::S2S);
            }
        }
    }

    protected function verifyPrivateAuth()
    {
        if ($this->app['basicauth']->isPrivateAuth() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_RECURRING_AUTH_NOT_SUPPORTED);
        }
    }

    protected function runPaymentMethodRelatedPreProcessing($payment, & $input, array & $gatewayInput)
    {
        $this->checkAndFillSavedAppToken($input);

        // First fetch the relevant customer
        list($customer, $customerApp) = (new Customer\Core)->getCustomerAndApp($input, $this->merchant);

        if ($customer === null)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else if ($customer->isLocal() === true)
        {
            $this->preProcessPaymentForLocalCustomer($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            $this->preProcessPaymentForGlobalCustomer($customer, $customerApp, $payment, $input, $gatewayInput);
        }

        if ($payment->isEmi() === true)
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            $this->setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration);
        }

        $payment->setInternational();
    }

    protected function preProcessPaymentWithoutSaving($payment, & $input, array & $gatewayInput)
    {
        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi())
        {
            $payment->setSave(false);

            $payment->setRecurring(false);

            $vault = $payment->isEmi();

            $gatewayInput['card'] = $this->createCardEntity($input['card'], $vault, $this->merchant);
        }
    }

    protected function preProcessPaymentForLocalCustomer($customer, $payment, & $input, & $gatewayInput)
    {
        $this->payment->customer()->associate($customer);

        // if token is set, payment is from a saved card
        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $this->preProcessPaymentFromSavedCardLocal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserDataLocal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentForGlobalCustomer($customer, $customerApp, $payment, & $input, & $gatewayInput)
    {
        $this->payment->app()->associate($customerApp);

        $this->payment->globalCustomer()->associate($customer);

        // If token is set, then pay using global saved card
        if (empty($input[Payment\Entity::TOKEN]) === false)
        {
            $this->preProcessPaymentFromSavedCardGlobal($customer, $payment, $input, $gatewayInput);
        }
        else
        {
            // Does processing like creating card entity, saving card if passed in the input, etc..
            $this->preProcessPaymentFromUserDataGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromSavedCardLocal($customer, $payment, & $input, & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_LOCAL,
            [
                'token' => $input[Payment\Entity::TOKEN]
            ]);

        $tokenId = $input[Payment\Entity::TOKEN];

        $token = (new Token\Core)->getByTokenAndCustomer($tokenId, $customer);

        if ($payment->isMethodCardOrEmi())
        {
            $payment->localToken()->associate($token);

            $gatewayInput['card'] = $this->getCardArrayForSavedToken($token, $input);
        }
        else
        {
            // @todo for netbanking/wallets
        }

        $this->validateRecurringPayment($payment, $input);
    }

    protected function preProcessPaymentFromSavedCardGlobal($customer, $payment, & $input, & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_GLOBAL,
            [
                'token' => $input[Payment\Entity::TOKEN]
            ]);

        // Token should definitely exist in database.
        $tokenId = $input[Payment\Entity::TOKEN];

        $token = (new Token\Core)->getByTokenAndCustomer($tokenId, $customer);

        if ($payment->isMethodCardOrEmi())
        {
            $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input);

            $payment->globalToken()->associate($token);

            $payment->card->globalCard()->associate($token->card);

            $this->repo->saveOrFail($payment->card);
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $payment->setWallet($token->getWallet());
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $payment->setBank($token->getBank());
        }
    }

    protected function preProcessPaymentFromUserDataLocal($customer, $payment, $input, & $gatewayInput)
    {
        // Flow if card details are entered with save set to true/false
        $saveMethod = (($payment->getSave() === true)  or
                       ($payment->isRecurring() === true));

        if ($saveMethod === false)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else
        {
            $this->savePaymentMethodLocal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function preProcessPaymentFromUserDataGlobal($customer, $payment, $input, & $gatewayInput)
    {
        // Flow if card details are entered with save set to true/false
        $saveMethod = $payment->getSave();

        if ($saveMethod === false)
        {
            $this->preProcessPaymentWithoutSaving($payment, $input, $gatewayInput);
        }
        else
        {
            $this->savePaymentMethodGlobal($customer, $payment, $input, $gatewayInput);
        }
    }

    protected function savePaymentMethodLocal($customer, $payment, $input, array & $gatewayInput)
    {
        // create local saved card and link to payment
        $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

        $savedLocalCard = $payment->card;

        // save local saved card for local customer
        $token = $this->savePaymentMethod($customer, $payment, $savedLocalCard->getId());

        if ($token !== null)
        {
            $this->payment->localToken()->associate($token);
        }

        $this->validateRecurringPayment($payment, $input);
    }

    protected function savePaymentMethodGlobal($customer, $payment, $input, array & $gatewayInput)
    {
        // create global saved card and link to payment
        $gatewayInput['card'] = $this->createCardEntity($input['card'], true, $customer->merchant);

        $savedGlobalCard = $payment->card;

        // create merchant local card entity and link to payment
        $gatewayInput['card'] = $this->createCardEntity($input['card'], false, $this->merchant);

        // link local card to global card entity
        $payment->card->globalCard()->associate($savedGlobalCard);

        $this->repo->saveOrFail($payment->card);

        // save global saved card for global customer
        $token = $this->savePaymentMethod($customer, $payment, $savedGlobalCard->getId());

        if ($token !== null)
        {
            $this->payment->globalToken()->associate($token);
        }
    }

    protected function savePaymentMethod($customer, $payment, $savedCardId)
    {
        $this->trace->info(
            TraceCode::PAYMENT_SAVE_METHOD,
            [
                'method'      => $payment->getMethod(),
                'payment_id'  => $payment->getId(),
                'merchant_id' => $payment->merchant->getId(),
                'customer_id' => $customer->getId(),
                'local'       => $customer->isLocal(),
                'card_id'     => $savedCardId
            ]);

        $saveMethodInput = array(
            'method' => $payment->getMethod(),
        );

        if ($payment->isMethodCardOrEmi())
        {
            $saveMethodInput[Token\Entity::METHOD] = Payment\Method::CARD;

            $saveMethodInput[Token\Entity::CARD_ID] = $savedCardId;
        }
        else if ($payment->isMethod(Payment\Method::NETBANKING))
        {
            $saveMethodInput[Token\Entity::BANK] = $payment->getBank();
        }
        else if ($payment->isMethod(Payment\Method::WALLET))
        {
            $saveMethodInput[Token\Entity::WALLET] = $payment->getWallet();
        }

        $token = null;

        try
        {
            $token = (new Token\Core)->create($customer, $saveMethodInput);
        }
        catch (Exception\RecoverableException $e)
        {
            // Ignore the exception, can be an already saved method
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);
        }

        return $token;
    }

    protected function verifyPaymentMethodEnabled($payment)
    {
        $paymentMethod = $payment->getMethod();

        switch ($paymentMethod)
        {
            case Payment\Method::CARD:
                $this->verifyCardEnabledInLive($payment);
                break;

            case Payment\Method::NETBANKING:
                $this->verifyBankEnabled($payment);
                break;

            case Payment\Method::WALLET:
                $this->verifyWalletEnabled($payment);
                break;

            case Payment\Method::EMI:
                $this->verifyEmiEnabled($payment);
                break;

            case Payment\Method::UPI:
                $this->verifyUpiEnabled();
                break;

            default:
                throw new Exception\LogicException(
                    'Should not reach here.',
                    null,
                    ['payment_method' => $paymentMethod]);
        }
    }

    protected function setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration)
    {
        $iinEntity = $payment->card->iinRelation;

        IIN\IIN::validateEmiAvailableForCard($iinEntity, $cardNumber);

        $payment->setBank($iinEntity->getIssuer());

        // Set emi plan id
        $emiPlan = $this->repo->emi_plan->fetchRelevantEmiPlan(
                                            $iinEntity, $emiDuration);

        $payment->getValidator()->validateMinAmountWithEmiPlanAmount($emiPlan);

        $payment->emiPlan()->associate($emiPlan);
    }

    protected function fillReturnRequestDataForMerchant(Payment\Entity $payment, array & $returnData)
    {
        assert ($payment->getCallbackUrl() !== null);

        // This would be normal request data at this point.
        // But since we will be redirecting to merchant's callback url
        // we need to push the request data into coproto structure
        // so that controller can then redirect peacefully.
        $content = $returnData;

        $returnData = array(
            'version' => 1,
            'type' => 'return',
            'request' => [
                'url' => $payment->getCallbackUrl(),
                'method' => 'post',
                'content' => $content,
            ],
        );
    }

    protected function checkAndFillSavedAppToken(array & $input)
    {
        if (isset($input['customer_id']) === true)
        {
            return;
        }

        if ($this->request->hasSession() === false)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_FILL_SAVED_APP_TOKEN,
            [
                'session' => $this->request->session()->all()
            ]);

        $key = $this->mode . '_app_token';

        $appToken = $this->request->session()->get($key);

        if ($appToken !== null)
        {
            $input['app_token'] = $appToken;
        }
    }

    protected function getMerchantCallbackUrl($payment)
    {
        return $this->payment->getCallbackUrl();
    }

    protected function getPaymentGatewayRequestData($request, $payment)
    {
        if (Payment\Gateway::supportsAsync($payment->getGateway()))
        {
            return $this->getAsyncPaymentCreatedResponse($request, $payment);
        }

        return $this->getFirstPaymentCreatedResponse($request, $payment);
    }

    /**
     * @see  CoProto supports async payments https://github.com/razorpay/api/wiki/COPROTO
     * @return array payment response
     */
    protected function getAsyncPaymentCreatedResponse($request, Payment\Entity $payment)
    {
        $id = $payment->getPublicId();

        return [
            'type'          => 'async',
            'version'       => 1,
            'payment_id'    => $id,
            'key_id'        => \BasicAuth::getPublicKey(),
            'gateway'       => $this->getEncryptedGatewayText($payment->getGateway()),
            'request'       => [
                'url'    => $this->route->getUrl('payment_get_status', ['id' => $id]),
                'method' => 'GET',
            ]
        ];
    }

    protected function getFirstPaymentCreatedResponse($request, Payment\Entity $payment)
    {
        $data['type'] = 'first';

        $data['request'] = $request;

        $data['version'] = 1;

        $data['payment_id'] = $payment->getPublicId();

        $data['gateway'] = $this->getEncryptedGatewayText($payment->getGateway());

        $amount = $payment->getAmount() / 100;

        $data['amount'] = sprintf($amount == intval($amount) ? '%d' : '%.2f', $amount);

        $data['image'] = $payment->merchant->getFullLogoUrlWithSize(Merchant\Logo::MEDIUM_SIZE);

        return $data;
    }

    protected function updateTokenOnAuthorized()
    {
        $payment = $this->payment;

        $token = $payment->getGlobalOrLocalTokenEntity();

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_TOKEN,
            [
                'payment_id'      => $payment->getId(),
                'token_id'        => $payment->getTokenId(),
                'global_token_id' => $payment->getGlobalTokenId()
            ]);

        // update token stats, assuming same token is not getting used in
        // multiple payments, actually we should locking
        if ($token !== null)
        {
            $createdAt = $payment->getCreatedAt();

            $token->setUsedAt($createdAt);

            $token->incrementUsedCount();

            if (($token->isLocal()) and
                ($payment->isCard()) and
                ($payment->isRecurring() == true) and
                ($token->isRecurring() === false))
            {
                $token->setRecurring(true);
            }

            $this->repo->saveOrFail($token);
        }
    }

    protected function updateAndNotifyPaymentAuthorized($wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $this->updatePaymentAuthorized($wasFailed);

        $this->eventPaymentAuthorized();

        $this->notifyIfCardSaved();

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
     * after payment authorize processing and auto capturing, if applicable.
     */
    protected function postPaymentAuthorizeProcessing($payment)
    {
        // Auto capture payment, if applicable
        $this->autoCapturePaymentIfApplicable($payment);

        return $this->processAuthorizeResponse($payment);
    }

    /**
     * Returns the proper response to checkout
     * in case of the payment is authorized
     * @param  Payment\Entity $payment
     * @return array
     */
    protected function processAuthorizeResponse($payment)
    {
        //
        // If callback url has been set, then we need to redirect
        // to the callback url and prepare data using coproto protocol.
        //
        // Otherwise we simply return 'razorpay_payment_id' as is normal.
        //

        $returnData = [
            'razorpay_payment_id' => $payment->getPublicId()
        ];

        if ($payment->getAutoCaptured() === true)
        {
            $this->fillReturnDataForAutoCaptureOrders($payment, $returnData);
        }

        if ($payment->getCallbackUrl())
        {
            $this->fillReturnRequestDataForMerchant($payment, $returnData);
        }

        return $returnData;
    }

    protected function fillReturnDataForAutoCaptureOrders($payment, & $data)
    {
        $data['razorpay_order_id'] = $payment->order->getPublicId();

        $data['razorpay_signature'] = $this->getSignature($data);
    }

    /**
     * @param boolean $wasFailed If a payment is being converted from authorized to failed.
     */
    protected function notifyAuthorized($wasFailed)
    {
        // Trigger notification events for authorization
        $notifier = new Notify($this->payment);

        if ($wasFailed)
        {
            $currentTime = Carbon::now('Asia/Kolkata')->timestamp;

            // If a payment has been authorized 15 minutes after the creation, we do not send a notification.

            if (($this->payment->getCreatedAt() - $currentTime) > self::FAILED_TO_AUTHORIZED_NOTIFY_DURATION)
            {
                return;
            }

            $trigger = Notify::FAILED_TO_AUTHORIZED;
        }
        else
        {
            $trigger = Notify::AUTHORIZED;
        }

        $notifier->trigger($trigger);
    }

    protected function notifyIfCardSaved()
    {
        $payment = $this->payment;

        if (($payment->isMethod(Payment\Method::CARD)) and
            ($payment->getSave() === true) and
            ($payment->getGlobalTokenId() !== null))
        {
            $notifier = new Notify($this->payment);

            $trigger = Notify::CARD_SAVED;

            $notifier->trigger($trigger);
        }
    }

    protected function eventPaymentAuthorized()
    {
        $this->app['events']->fire('api.payment.authorized', array($this->payment));
    }

    protected function traceAuthorizeFailedOperationData($payment)
    {
        $traceData = array(
            'payment_id' => $payment->getId(),
            'error' => $payment->getErrorDetails(),
        );

        $message = 'Payment failed earlier converte to authorized';

        $slackData = ['id' => $payment->getDashboardEntityLinkForSlack()];

        $this->app['slack']->queue(
            $message, $slackData, ['color' => 'good', 'channel' => Config::get('slack.channels.tech_logs')]);

        $this->trace->info(
            TraceCode::PAYMENT_FAILED_TO_AUTHORIZED,
            $traceData);
    }

    protected function recordTerminalAudit($terminalData)
    {
        try
        {
            $log = [
                TerminalAnalytics\Entity::PAYMENT_ID    => $terminalData['payment_id'],
                TerminalAnalytics\Entity::TERMINAL_ID   => $terminalData['terminal_id']
            ];

            // convert difference to milliseconds to record as integer
            $responseTime = (int) (($terminalData['end'] - $terminalData['start']) * 1000);

            $log[TerminalAnalytics\Entity::TERMINAL_RESPONSE_TIME] = $responseTime;

            $log[TerminalAnalytics\Entity::PAYMENT_TYPE] = 1;

            $log[TerminalAnalytics\Entity::TERMINAL_STATUS] = 1;

            $errorCode = null;

            $errorMsg = null;

            if (isset($terminalData['exception']))
            {
                $e = $terminalData['exception'];

                $log[TerminalAnalytics\Entity::TERMINAL_STATUS] = 0;

                // we care about this exception, since its an indicator of
                // terminal failure
                $log[TerminalAnalytics\Entity::TERMINAL_STATUS_CODE] = $e->getError()->getHttpStatusCode();

                $log[TerminalAnalytics\Entity::TERMINAL_STATUS_MSG] = $e->getError()->getDescription();
            }

            (new TerminalAnalytics\Core)->create($log);
        }
        catch(\Exception $e)
        {
            $this->trace->warning(
                TraceCode::TERMINAL_ANALYTICS_SAVE_FAILED,
                ['terminalData' => $terminalData]
            );

            $this->trace->traceException($e);
        }
    }

    protected function createAnalyticsLog($payment)
    {
        try
        {
            $analyticsEntity = (new Analytics\Core)->create($payment);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::WARNING,
                TraceCode::PAYMENT_ANALYTICS_SAVE_FAILED);
        }
    }

    protected function callGatewayAuthorize(array $data)
    {
        return $this->callGatewayFunction(Action::AUTHORIZE, $data);
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

    protected function callGatewayOtpGenerate($data, $payment, $otpResend = false)
    {
        try
        {
            $this->type = 'otp_generate';

            $data['otp_resend'] = $otpResend;

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
                // TODO: Return metadata in a better format
                'contact' => $payment->getContact(),
                'amount'  => number_format(($payment->getAmount()/100), 2),
                'wallet'  => $payment->getWallet()
            );
        }
        catch (Exception\BaseException $e)
        {
            $this->updatePaymentFailed($e, TraceCode::PAYMENT_AUTH_FAILURE);

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

    /**
     * creates gateway input using saved card token, this method is used for
     * local card saving and we can associate the same card with the payment
     */
    protected function getCardArrayForSavedToken($token, & $input)
    {
        $card = $token->card;

        $cardNumber = Card\Tokenex::getCardNumber($card->getVaultToken());

        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $this->payment->card()->associate($card);

        return array_merge(
                $card->toArray(),
                [
                    'number' => $cardNumber,
                    'cvv' => $cvv
                ]);
    }

    /**
     * creates gateway input using saved card token, this method is used for
     * global card saving. we need to create a new card entity for merchant
     * and associate with the payment
     */
    protected function createCardEntityFromSavedToken($token, & $input)
    {
        $cardNumber = Card\Tokenex::getCardNumber($token->card->getVaultToken());

        $cvv = isset($input['card']['cvv']) ? $input['card']['cvv'] : null;

        $savedCard = $token->card->toArray();
        $savedCard['number'] = $cardNumber;
        $savedCard['cvv'] = $cvv;

        //create a card entity for merchant
        $cardCore = new Card\Core();

        $card = $cardCore->createDuplicateCard($savedCard, $this->merchant);

        $this->payment->card()->associate($card);

        return array_merge(
            $card->toArray(),
            [
                'number' => $cardNumber,
                'cvv' => $cvv
            ]);
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

    protected function verifyEmiEnabled($payment)
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isEmiEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_EMI_NOT_ENALBED_FOR_MERCHANT);
        }

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $payment->card);
    }

    protected function verifyUpiEnabled()
    {
        $merchantMethods = $this->methods;

        if (($merchantMethods === null) or
            ($merchantMethods->isUPIEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyCardEnabledInLive($payment)
    {
        $card = $payment->card;

        $merchantMethods = $this->methods;

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $card);

        // Only check enabled or not on live mode
        if ($this->mode === Mode::TEST)
        {
            return;
        }

        if ($merchantMethods->isCardEnabled() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NOT_ENALBED_FOR_MERCHANT);
        }

        $type = $card->getType();

        if ($type === Card\Type::UNKNOWN)
        {
            return;
        }

        $type = ucfirst($type);

        $func = 'is' . $type . 'CardEnabled';

        if ($merchantMethods->$func() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                $type . ' card transactions are not allowed',
                'number');
        }
    }

    protected function verifyFeatureForMerchant($merchant, $feature)
    {
        if ($merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                    "$feature is not supported");
        }
    }

    protected function validateRecurringPayment($payment, $input)
    {
        // checks if payment is recurring
        if (($payment->isRecurring()) and
            ($payment->card->isRecurringSupported() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_RECURRING_NOT_SUPPORTED);
        }

        // if not recurring, validate card data
        if (($payment->isRecurring() === false) and
            ($payment->getTokenId() !== null) and
            ($payment->localToken->isRecurring() === false))
        {
            $payment->getValidator()->validateCardAndCvv($input);
        }
    }

    protected function checkAndValidateAmexIfNotEnabled($methods, $card)
    {
        $amex = $methods->getAmex();

        if (($card->isAmex() === true) and
            ($amex === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_NETWORK_NOT_SUPPORTED,
                'number');
        }
    }

    protected function savePaymentAndCard()
    {
        $this->repo->saveOrFail($this->payment->card);

        $this->repo->saveOrFail($this->payment);
    }

    protected function updatePaymentAuthorized($wasFailed = false)
    {
        $payment = $this->payment;

        $this->repo->transaction(function() use ($payment, $wasFailed)
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

            // If payment was earlier failed, then that means it's
            // getting authorized late.
            $payment->setLateAuthorized($wasFailed);

            //
            // If gateway is authorizing the payment (basically, no authAndCapture support), create transaction.
            //
            if ($this->isGatewayActuallyAuthorizingPayment($payment) === false)
            {
                $payment->setGatewayCaptured(true);

                // Also sets the transaction association with the payment.
                $txn = (new Transaction\Core)->createFromPaymentAuthorized($payment);

                $this->repo->saveOrFail($txn);
            }

            $this->repo->saveOrFail($payment);
            $this->repo->saveOrFail($payment->terminal);

            $this->updateTokenOnAuthorized();

            // If payment has an associated order
            // set the order to be paid
            $this->updateAuthorizedOrderStatus($payment);

            $this->tracePaymentInfo(TraceCode::PAYMENT_AUTH_SUCCESS);
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

        return Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode);
    }

    protected function getEncryptedGatewayText($gateway)
    {
        return Crypt::encrypt($gateway . '__' . time());
    }

    protected function verifyHash($inputHash, $paymentPublicId)
    {
        $expectedHash = $this->getHashOf($paymentPublicId);

        if (hash_equals($expectedHash, $inputHash) !== true)
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

        $callbackUrl = $this->route->getUrlWithPublicCallbackAuth($params);

        return $callbackUrl;
    }

    protected function getOtpSubmitUrl()
    {
        $params = $this->getPaymentIdAndHashParams();

        $otpSubmitUrl = $this->route->getUrlWithPublicAuth('payment_otp_submit', $params);

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
