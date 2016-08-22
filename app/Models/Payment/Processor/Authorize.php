<?php

namespace RZP\Models\Payment\Processor;

use Mail;
use Lib\PhoneBook;
use RZP\Models\Emi;
use RZP\Http\Route;
use RZP\Models\Card;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Card\IIN;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Transaction;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Status;
use RZP\Models\Merchant\Methods;
use RZP\Models\Payment\Analytics;

use RZP\Error;
use RZP\Exception;
use RZP\Trace\Trace;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

use App;
use Crypt;

trait Authorize
{
    /**
     * There are different ways of doing payment authorization.
     */
    protected $type;

    protected $terminalSelector;

    protected $terminalsSelected;

    public function authorize($payment, $input)
    {
        $this->verifyMerchantIsLiveForLiveRequest();

        $gatewayInput = [];

        // $gatewayInput is being passed by reference.
        // Adds callback url, payment and card info to $gatewayInput
        $this->prePaymentAuthorizeProcessing($payment, $input, $gatewayInput);

        $this->getTerminalsForPayment($payment);

        return  $this->authorizeAcrossTerminals($gatewayInput, $payment, $input);

    }

    protected function getTerminalsForPayment($payment)
    {
        $this->terminalSelector = new Terminal\Selector($payment, $this->mode);

        $this->terminalsSelected = $this->terminalSelector->selectTerminals();
    }

    protected function authorizeAcrossTerminals($gatewayInput, $payment, $input)
    {
        $totalTerminals = count($this->terminalsSelected);

        $maxRetryAttempts = min($totalTerminals, self::MAX_RETRY_ATTEMPTS);

        $retryAttempts = 0;

        $request = null;

        while ($retryAttempts < $maxRetryAttempts)
        {
            $terminalGatewayInput = $gatewayInput;

            $currentTerminal = $this->terminalsSelected[$retryAttempts];

            $payment->associateTerminal($currentTerminal);

            $this->runGatewaySpecificPreProcessing($payment, $terminalGatewayInput);

            if ($this->canRunOtpPaymentFlow($payment, $input))
            {
                return $this->runOtpPaymentFlow($terminalGatewayInput, $payment);
            }

            $start = microtime();

            try
            {
                $request = $this->callGatewayAuthorize($terminalGatewayInput);

                // record a successful payment here for the given terminal id
                $this->recordTerminalAudit($start, $terminalGatewayInput['payment']);

                break;
            }
            catch (Exception\GatewayRequestException $e)
            {
                // record a failed payment for given terminal and continue
                $this->recordTerminalAudit($start, $terminalGatewayInput['payment'], $e);

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
                $this->updatePaymentAuthFailedAndThrowException($e);
            }
        }

        return $this->processAuthResponse($request, $payment);
    }

    protected function logAndCheckForAuthRetry($e, $payment)
    {
        $traceData = array(
            'errorcode' => $e->getCode(),
            'message' => $e->getMessage(),
            'payment_id' => $payment->getId(),
            'terminal_id' => $payment->terminal->getId()
        );

        $this->trace->info(
            TraceCode::TERMINAL_FAILURE, $traceData);

        // retry only if it is safe to do so
        if ((property_exists($e, 'safeRetry') === true) and
            ($e->safeRetry === true))
        {
            return true;
        }

        return false;
    }

    protected function updatePaymentAuthFailedAndThrowException($e)
    {
        $this->updatePaymentFailed(
            $e->getError(),
            TraceCode::PAYMENT_AUTH_FAILURE);

        throw $e;
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
        // also sets the card details in $gatewayInput (passed by reference), if applicable.
        $this->runPaymentMethodRelatedPreProcessing($payment, $input, $gatewayInput);

        $this->verifyPaymentMethodEnabled($payment, $input);

        return $gatewayInput;
    }

    protected function runGatewaySpecificPreProcessing($payment, array & $gatewayInput)
    {
        // International card validation happens here because we want to save the failure.
        // For payment creation, gateway is compulsory field which is only finalized in
        // previous step.
        $this->validateInternationalAllowed($payment);

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

        if ($payment->isEmi())
        {
            $cardNumber = $gatewayInput['card']['number'];

            $emiDuration = $input['emi_duration'];

            $this->setBankAndEmiPlanDetails($payment, $cardNumber, $emiDuration);
        }
    }

    protected function preProcessPaymentWithoutSaving($payment, & $input, array & $gatewayInput)
    {
        // No card saving, normal simple flow
        if ($payment->isMethodCardOrEmi())
        {
            $payment->setSave(false);

            $vault = $payment->isMethod(Payment\Method::EMI);

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
            $this->payment->setToken(null);

            $this->payment->setGlobalToken($input[Payment\Entity::TOKEN]);

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

        // Token should definitely exist in database.
        $token = $this->repo->token->getByTokenAndCustomerId(
            $input[Payment\Entity::TOKEN],
            $customer->getId());

        if ($payment->isMethodCardOrEmi())
        {
            $gatewayInput['card'] = $this->getCardArrayForSavedToken($token, $input);
        }
        else
        {
            //TODO for netbanking/wallets
        }
    }

    protected function preProcessPaymentFromSavedCardGlobal($customer, $payment, & $input, & $gatewayInput)
    {
        $this->trace->info(
            TraceCode::PAYMENT_PROCESS_FROM_SAVED_GLOBAL,
            [
                'token' => $input[Payment\Entity::TOKEN]
            ]);

        // Token should definitely exist in database.
        $token = $this->repo->token->getByTokenAndCustomerId(
            $input[Payment\Entity::TOKEN],
            $customer->getId());

        if ($payment->isMethodCardOrEmi())
        {
            $gatewayInput['card'] = $this->createCardEntityFromSavedToken($token, $input);

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
        $saveMethod = ((isset($input['save'])) and (boolval($input['save']) === true));

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
        $saveMethod = ((isset($input['save'])) and (boolval($input['save']) === true));

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
            $this->payment->setToken($token->getToken());
        }
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
            $this->payment->setGlobalToken($token->getToken());
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
            $saveMethodInput['method'] = Payment\Method::CARD;

            $saveMethodInput['card_id'] = $savedCardId;
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
            $token = (new Token\Core)->create($customer, $saveMethodInput);

            return $token;
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
                $this->verifyCardEnabledInLive($payment, $input);
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

    protected function updateAndNotifyPaymentAuthorized($wasFailed = false)
    {
        // Updates payment entity to authorized and adds a transaction.
        $this->updatePaymentAuthorized();

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
            'razorpay_payment_id' => $payment->getPublicId(),
            'amount'              => $payment->getAmount(),
            'currency'            => $payment->getCurrency(),
            'merchant_order_id'   => $payment->getNotes()['merchant_order_id'],
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

    protected function notifyIfCardSaved()
    {
        $payment = $this->payment;

        if (($payment->isMethod(Payment\Method::CARD)) and
            ($payment->getSave() === true) and
            ($payment->getGlobalToken() !== null))
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

        $this->app['slack']->queue($message, $slackData, ['color' => 'good', 'channel' => '#tech_logs']);

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
            [
                'payment_id' => $payment->getPublicId(),
                'public_error_code' => $publicErrorCode,
                'internal_error_code' => $internalErrorCode,
                'error_description' => $errorDesc,
                'message' => 'Failed to convert error code to the appropriate exception'
            ]);

        // If no appropriate exception mapping was found then show
        // the usual message that payment already processed.

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCCESSED);
    }

    protected function recordTerminalAudit(
        $start, array $payment, Exception\GatewayRequestException $e = null)
    {
        $end = microtime();

        $responseTime = $end - $start;

        // record payment actions
        $pAnalyticsService = new Analytics\Service();

        $input = array('payment_id' => $payment['id'],
                       'terminal_id' => $payment['terminal_id'],
                       'terminal_response_time' => $responseTime,
                       'payment_type' => 1,
                       'terminal_status' => 1);

        $errorCode = null;

        $errorMsg = null;

        if ($e !== null)
        {
            $input['terminal_status'] = 0;

            // we care about this exception, since its an indicator of
            // terminal failure
            $input['terminal_status_code'] = $e->getError()->getHttpStatusCode();

            $input['terminal_status_msg'] = $e->getError()->getDescription();
        }

        $pAnalyticsService->createAuditLog($input);
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
                // TODO: Return metadata in a better format
                'contact' => $payment->getContact(),
                'amount'  => number_format(($payment->getAmount()/100), 2),
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

    protected function verifyCardEnabledInLive($payment, $input)
    {
        $card = $payment->card;

        $merchantMethods = $this->methods;

        $this->checkAndValidateAmexIfNotEnabled($merchantMethods, $input['card']);

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

            //
            // If gateway is authorizing the payment (basically, no authAndCapture support), create transaction.
            //
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

        if (Payment\Gateway::supportsAuthAndCapture($gateway, $networkCode) === false)
        {
            return false;
        }

        return true;
    }

    protected function getEncryptedGatewayText($gateway)
    {
        return Crypt::encrypt($gateway . '__' . time());
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
