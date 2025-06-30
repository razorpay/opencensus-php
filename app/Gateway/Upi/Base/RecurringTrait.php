<?php

namespace RZP\Gateway\Upi\Base;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\UpiMandate;
use RZP\Models\PaymentsUpi;
use RZP\Constants\Timezone;
use RZP\Models\Notification;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Mozart\Gateway;
use RZP\Exception\BaseException;
use RZP\Exception\LogicException;
use RZP\Models\Payment\UpiMetadata;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\VirtualAccount\Receiver;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\UpiMandate\Metrics as UpiMandateMetrics;

trait RecurringTrait
{
    /**
     * @var Entity
     */
    protected $upi;

    protected $gatewayDataIdToActionMap = [
        Action::AUTHENTICATE    => 'create',
        Action::AUTHORIZE       => 'execte',
        Action::DEBIT           => 'execte',
        Action::MANDATE_CANCEL  => 'revoke',
        Action::PRE_DEBIT       => 'notify',
    ];

    /**
     * Gateway error codes that should be excluded from UPI Autopay OTM deemed debit processing
     */
    public static $upiAutopayDeemedDebitExclusionErrorCodes = [
        '59',
        'K1',
        'VO',
        'VH'
    ];

    protected $entityActionMapFromGateway = [
        'create'                => Action::AUTHENTICATE,
        'execte'                => Action::AUTHORIZE,
        'notify'                => Action::PRE_DEBIT,
    ];

    public static $optimizerUpiRecurringGateway = [
        'payu'    => Payment\Gateway::PAYU,
    ];

    // 24 + 1 hours in second
    protected $defaultExecuteBuffer = 90000;

    protected $optimizerRecurringExecuteBuffer = 176400;

    public function redirectCallbackIfRequired(array $response, $content, $headers)
    {
        $details = $this->getRecurringDetailsFromServerCallback($response);

        if ((isset($response['data']['upi']) === true) and
            (isset($response['data']['upi']['merchant_reference']) === true))
        {
            $merchantReference = $response['data']['upi']['merchant_reference'];
            $details = [
                Entity::PAYMENT_ID      => substr($merchantReference, 0, 14),
                Constants::ENVIRONMENT  => substr($merchantReference, 14, 1),
                Constants::ACTION       => substr($merchantReference, 15, 6),
                Constants::ATTEMPT      => substr($merchantReference, 21),
            ];
        }

        $env     = $details[Constants::ENVIRONMENT] ?? 0;

        // Env=1 signifies its a dark payment
        if ((int) $env === 1)
        {
            $this->trace->info(TraceCode::MISC_TRACE_CODE,
                [
                    'message'    => 'Redirection to dark',
                ]);
            // Only if we are not on dark, we need to redirect
            if ($this->isRunningOnDark() === false)
            {
                $uri = route('gateway_payment_callback_recurring', ["gateway" => $this->gateway], false);
                $url = 'https://api-dark-concierge.razorpay.com' . $uri;

                $this->trace->info(TraceCode::MISC_TRACE_CODE, [
                    'message'       => 'callback redirected',
                    'details'       => $details,
                    'url'           => $url,
                ]);

                $response = $this->sendProxyRequestToDark($url, $content, $headers);

                $response = json_decode($response->body, true);

                $this->trace->info(TraceCode::MISC_TRACE_CODE, [
                    'message'       => 'response from dark',
                    'response'      => $response,
                ]);

                return $response;
            }
        }
    }

    public function debit(array $input)
    {
        parent::action($input, Action::DEBIT);

        $debit = $this->firstOrCreateEntityForRecurring($input, Action::AUTHORIZE, true);

        $this->setRequestDataForUpiRecurring($input, $debit);

        $response = $this->sendDebitRequest($input, $debit);

        return $response;
    }

    public function preDebit(array $input)
    {
        parent::action($input, Action::PRE_DEBIT);

        if(isset($input['notification']) === true)
        {
            return $this->sendUpiAutopayNotificationRequest($input);
        }
        // PreDebit action for gateway requires a notification
        // First we need check if there is already notify attempted.
        $preDebit = $this->firstOrCreateEntityForRecurring($input, Action::PRE_DEBIT, true);

        $this->trace->count(UpiMandateMetrics::UPI_AUTOPAY_NOTIFICATION_CREATED,
            [
                'flow'    => 'coupled',
                'gateway' => $this->gateway,
                'is_tpv'  => $input['terminal']['tpv']
            ]);

        // Even if we have to skip the pre debit on gateway, we need to
        // create an entity for that, it will help with consistency and recon
        if ($this->shouldSkipNotityForAutoRecurring($input) === true)
        {
            return $this->getResponseForAutoRecurring($input, [], $preDebit);
        }

        $this->setRequestDataForUpiRecurring($input, $preDebit);

        $response = $this->sendPreDebitRequest($input, $preDebit);

        return $response;
    }

    protected function getGatewayDataBlockForUpiRecurring($input, $action)
    {
        $attempt = 0;

        $action = $this->gatewayDataIdToActionMap[$action];

        $id = $input['payment']['id'] . $action . $attempt;

        $gatewayData = [
            'id'     => $id,
            'act'    => $action,
            'ano'    => $attempt,
        ];

        return $gatewayData;
    }

    protected function setGatewayDataBlockForUpiRecurring(array & $input)
    {
        $input['upi']['gateway_data'] = $this->getGatewayDataBlockForUpiRecurring($input, $this->action);
    }

    protected function setRequestDataForUpiRecurring(array & $input, Entity $upi)
    {
        // We need to add api action, just for tracking purposes
        $input[Constants::UPI][Constants::ACTION] = $this->getAction();

        $gatewayData    = $upi->getGatewayData();
        $action         = $gatewayData[Constants::ACTION];
        $attempt        = $gatewayData[Constants::ATTEMPT];
        $env            = $gatewayData[Constants::ENVIRONMENT] ?? 0;

        // For already created payment we can trace if there is any anomaly
        $id = $input[Entity::PAYMENT][Entity::ID] . $env . $action . $attempt;

        $routeName = $this->app['api.route']->getCurrentRouteName();

        // in case of recon if merchant_reference is different so will update merchant_reference id from recon input
        if (($routeName === 'payment_upi_authorize_failed') and
            (isset($input['upi']) === true) and ($input['upi']['act'] === Action::VERIFY))
        {
            if ((isset($input['gateway_data']) == true) and
                (isset($input['gateway_data']['merchant_reference']) === true) and
                (str_contains($input['gateway_data']['merchant_reference'], 'execte')) and
                ($input['gateway_data']['merchant_reference'] !== $upi->getMerchantReference()))
            {
                $id = $input['gateway_data']['merchant_reference'];
                $gatewayData[Constants::ID] = $id;
                $upi->setGatewayData($gatewayData);
            }
            else if ($upi->getMerchantReference() !== $id)
            {
                $id = $upi->getMerchantReference();
            }
        }

        $gatewayData[Constants::ID] = $id;

        // First set the correct gateway data
        $input[Constants::UPI][Entity::GATEWAY_DATA] = $gatewayData;

        // Since Gateway Upi entity has VPA taken, UPI in input does.
        // Need to fix that too
        $input[Constants::UPI][Entity::VPA] = $upi->getVpa();

        // Action is mostly needed to process the response
        $input[Constants::UPI][Entity::ACTION] = $upi->getAction();

        if ($this->getAction() === Action::AUTHENTICATE)
        {
            $variant = $this->evaluateSplitzExperimentForUpiAutopayPaymentRemark($input['payment']['merchant_id']);

            $description = "";
            if($variant === true)
            {
                $paymentDescription = $input['payment']['description'] ?? '';
                $description = Payment\Entity::getFilteredDescription($paymentDescription);
                $description =  ($description ? substr($description, 0, 50) : 'Pay via Razorpay');
            }
            else
            {
                $description = $this->getPaymentRemark($input);
            }

            $input[Constants::UPI][Entity::REMARK] = $description;
        }
    }

    /**
     * Evaluates the Splitz experiment for UPI Autopay Payment Remark.
     *
     * @param string $merchantId
     * @return bool
     */
    protected function evaluateSplitzExperimentForUpiAutopayPaymentRemark($merchantId)
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.upi_autopay_payment_remark'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::UPI_AUTOPAY_PAYMENT_REMARK
            );
        }

        return false;
    }

    protected function isFirstRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::INITIAL));
    }

    protected function isSecondRecurringPayment(array $input): bool
    {
        return (($input[Entity::PAYMENT][Payment\Entity::RECURRING] === true) and
            ($input[Entity::PAYMENT][Payment\Entity::RECURRING_TYPE] === Payment\RecurringType::AUTO));
    }

    // Not Used
    protected function recurringMandateCreateCallback(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->callback($input);
    }

    protected function recurringMandateRevoke(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->mandateRevoke($input);
    }

    protected function recurringCallbackDecryption(array $input)
    {
        $mozart = $this->getMozartGatewayWithModeSet();

        if ($this->env === 'production')
        {
            $mozart->setMode(Mode::LIVE);
        }
        else
        {
            $mozart->setMode(Mode::TEST);
        }

        return $mozart->callbackDecryption($input);
    }

    protected function firstDebit(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->debit($input);
    }

    protected function authenticate(array $input)
    {
        $authenticate = $this->firstOrCreateEntityForRecurring($input, Action::AUTHENTICATE, true);

        $this->setRequestDataForUpiRecurring($input, $authenticate);

        $response = $this->sendMandateCreateRequest($input, $authenticate);

        return $response;
    }

    protected function getVpaDetails(array $input)
    {
        try
        {
            $gateway = $this->getMozartGatewayWithModeSet();

            $vpaCore = new PaymentsUpi\Vpa\Core;

            $vpa = $vpaCore->firstByAddress($input['payment']['vpa']);

            if (($vpa !== null) and ($vpa['name'] === null))
            {
                $validate_vpa = $gateway->validateVpa($input);
                $vpa['name'] = $validate_vpa['data']['payer_name'];

                $vpaCore->updateOrCreate([
                    'vpa' => $input['payment']['vpa'],
                    'name' => $validate_vpa['data']['payer_name'],
                    'received_at' => Carbon::now()->getTimestamp()
                ]);
            }

            return $vpa;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::VALIDATE_VPA_STATUS_FAILED,
                [
                    'payment' => $input['payment']['id'],
                    'vpa' => $input['payment']['vpa']
                ]);

            throw $e;
        }
    }

    protected function sendMandateCreateRequest(array $input, Entity $upi)
    {
        if (Payment\Gateway::isUpiRecurringValidateVPASupportedGateway($input['payment']['gateway']) === true)
        {
            if ($input['upi']['flow'] === Constants::COLLECT)
            {
                $vpa = $this->getVpaDetails($input);

                $input['vpa'] = $vpa;
            }
        }

        $gateway = $this->getMozartGatewayWithModeSet();
        $response = $gateway->mandateCreate($input);

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                null,
                null,
                $this->action);

            $exception->setData($this->getResponseForAutoRecurring($input, $response['data'], $upi, $exception));

            throw $exception;
        }

        return $this->getResponseForAutoRecurring($input, $response['data'], $upi);
    }

    protected function sendDebitRequest(array $input, Entity $upi)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->debit($input);

        $attributes = array_only($response['data'], (new Entity)->getFillable());

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? null,
                $response['error']['gateway_error_description'] ?? null,
                null,
                null,
                $this->action);

            $exception->setData($this->getResponseForAutoRecurring($input, $response['data'], $upi, $exception));

            throw $exception;
        }

        return $this->getResponseForAutoRecurring($input, $response['data'], $upi);
    }

    protected function sendUpiAutopayNotificationRequest(array $input)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        return $gateway->preDebit($input);
    }

    protected function sendPreDebitRequest(array $input, Entity $upi)
    {
        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->preDebit($input);

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                null,
                null,
                $this->action);

            $exception->setData($this->getResponseForAutoRecurring($input, $response['data'], $upi, $exception));

            throw $exception;
        }

        if ((in_array($input['payment']['gateway'], self::$optimizerUpiRecurringGateway) === true) and
            (isset($input['notify_action']) === true) and
            ($input['notify_action'] === 'retrieve_upi') and
            ($input['payment']['amount'] >= Payment\Processor\Constants::WITHOUT_AFA_AMOUNT_LIMIT_UPI) and
            isset($response['invoice_status']) === true and
            $response['invoice_status'] !== 'Approved')
        {
            $exception =  new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? 'gateway_error_code',
                $response['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [
                    'message' => 'AFA approval is still pending from customer end'
                ],
                null,
                $this->action);

            $exception->setData($this->getResponseForAutoRecurring($input, $response['data'], $upi, $exception));

            throw $exception;
        }

        return $this->getResponseForAutoRecurring($input, $response['data'], $upi);
    }

    protected function processRecurringRearchCallback(array $input, Entity $upi)
    {
        if(isset($input['gateway']['data']['upi_mandate']) === true)
        {
            $input['gateway']['data']['mandate'] = $input['gateway']['data']['upi_mandate'];
        }

        if((isset($input['gateway']['data']['upi']['merchant_reference']) === true) and
            (str_contains($input['gateway']['data']['upi']['merchant_reference'], 'execte') === true))
        {
            $input['gateway']['data']['upi']['vpa'] = $input['upi']['vpa'];
        }

        if (isset($input['gateway']['error']) === true)
        {
            $error = $input['gateway']['error'];

            $exception = new GatewayErrorException(
                $error['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $error['gateway_error_code'] ?? null,
                $error['gateway_error_description'] ?? null,
                null,
                null,
                $this->action);

            $deemedDebitResponse = $this->handleUpiAutopayDeemedDebitForCallback($input, $input['gateway']);
            if ($deemedDebitResponse === false)
            {
                $exception->setData($this->getResponseForAutoRecurring($input, $input['gateway']['data'], $upi, $exception));
                throw $exception;
            }
        }

        return $this->getResponseForAutoRecurring($input, $input['gateway']['data'], $upi);
    }

    protected function processRecurringCallback(array $input)
    {
        if (in_array($input['payment']['gateway'],self::$optimizerUpiRecurringGateway, true))
        {
            $upi = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], $input['action']);
        }
        else {
            $details = $this->getRecurringDetailsFromServerCallback($input['gateway']);

            $upi = $this->repo->findByPaymentIdAndActionOrFail($details[Entity::PAYMENT_ID], $details[Entity::ACTION]);
        }

        if(isset($input['gateway']['success']) === true)
        {
            return $this->processRecurringRearchCallback($input, $upi);
        }

        $this->setRequestDataForUpiRecurring($input, $upi);

        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->upiRecurringCallback($input);

        if ($response['success'] !==  true)
        {
            $exception = new GatewayErrorException(
                $response['error']['internal_error_code'] ?? 'BAD_REQUEST_PAYMENT_FAILED',
                $response['error']['gateway_error_code'] ?? null,
                $response['error']['gateway_error_description'] ?? null,
                null,
                null,
                $this->action);

            if((isset($response['data']['status_desc'])) and
                ($response['data']['status_desc'] !== null) and
                ($response['data']['terminal']['gateway'] === 'upi_mindgate') and
                ($response['error']['gateway_error_description']) !== null)
            {
                $response['data']['status_desc'] = $response['error']['gateway_error_description'] . "|" . $response['data']['status_desc'];
                $this->trace->info(TraceCode::UPI_RECURRING_PAYER_RESPONSE_CODE, [
                    'status_desc'       => $response['data']['status_desc'],
                    'error'             => $response['error']
                ]);
            }

            // Handle UPI Autopay deemed debit for one-time frequency payments
            $deemedDebitResponse = $this->handleUpiAutopayDeemedDebitForCallback($input, $response);
            if ($deemedDebitResponse === false)
            {
                $exception->setData($this->getResponseForAutoRecurring($input, $response['data'], $upi, $exception));
                throw $exception;
            }
        }

        return $this->getResponseForAutoRecurring($input, $response['data'], $upi);
    }

    /**
     * Handle UPI Autopay deemed debit logic for callback flow
     * Checks if the payment qualifies for deemed debit and returns success response if applicable
     *
     * @param array $input
     * @param array $response
     * @param Entity $upi
     * @return bool Returns success response if deemed debit applies, null otherwise
     */
    protected function handleUpiAutopayDeemedDebitForCallback(array $input, array &$response)
    {
        // Check if this payment qualifies for UPI Autopay deemed debit processing
        if (isset($input['payment']['recurring_type']) &&
            $input['payment']['recurring_type'] === Payment\RecurringType::AUTO &&
            isset($input['upi_mandate']['frequency']) &&
            $input['upi_mandate']['frequency'] === UpiMandate\Frequency::ONETIME &&
            isset($response['error']['gateway_error_code']) &&
            !$this->containsErrorCodes($response['error']['gateway_error_code'], self::$upiAutopayDeemedDebitExclusionErrorCodes)) {

            $this->trace->info(TraceCode::UPI_AUTOPAY_DEEMED_DEBIT, [
                'payment_id' => $input['payment']['id'],
                'error_code' => $response['error']['gateway_error_code'],
                'gateway' => $input['payment']['gateway']
            ]);

            // Return success response for deemed debit case
            $response['data']['success'] = true;

            return true;
        }

        return false;
    }

    /**
     * Check if the gateway error code contains any of the specified error codes as substrings
     *
     * @param string|null $gatewayErrorCode
     * @param array $errorCodes
     * @return bool
     */
    protected function containsErrorCodes($gatewayErrorCode, array $errorCodes): bool
    {
        if (empty($gatewayErrorCode)) {
            return false;
        }

        $pspErrorCode = $gatewayErrorCode;

        // If gateway error code contains underscore, split and use the second part
        if (str_contains($gatewayErrorCode, '_')) {
            $parts = explode('_', $gatewayErrorCode, 2);
            $pspErrorCode = $parts[1];
        }

        // Check if the code is present in the error codes list
        return in_array($pspErrorCode, $errorCodes, true);
    }

    protected function getRecurringDetailsFromServerCallback(array $response): array
    {
        if((isset($response['success']) === true) and
            (isset($response['data']['upi']['merchant_reference']) === true) and
            (isset($response['data']['terminal']['gateway'])) === true
            and ($response['data']['terminal']['gateway'] === 'upi_icici'))
        {
            $actualId = $response['data']['upi']['merchant_reference'];
        }
        else
        {
            $actualId = $this->getActualPaymentIdFromServerCallback($response);
        }

        $paymentId      = substr($actualId, 0, 14);
        $env            = substr($actualId, 14, 1);
        $action         = substr($actualId, 15, 6);
        $attempt        = substr($actualId, 21);

        // Now we will validate if this is correct recurring callback
        $entityAction   = $this->entityActionMapFromGateway[$action] ?? null;

        // If API action is not empty, it is valid recurring payment
        if (empty($entityAction) === false)
        {
            // We can also add checks like $attempt > 0, but this much is fine for now
            // If we see even a single case where the Unexpected payment is taken as recurring
            // payment, we can add more checks, And the best check is just a DB call here
            // which can check for paymentId+entityAction. We will need to trace the anomaly then.
            return [
                Entity::PAYMENT_ID      => $paymentId,
                Entity::ACTION          => $entityAction,
                Constants::ENVIRONMENT  => $env,
                Constants::ATTEMPT      => $attempt,
            ];
        }

        return [
            Entity::PAYMENT_ID      => null,
            Entity::ACTION          => null,
            Constants::ENVIRONMENT  => null,
            Constants::ATTEMPT      => null,
        ];
    }

    protected function isFirstUpiRecurringPayment($payment): bool
    {
        return ($payment['method'] === 'upi' and $payment['recurring_type'] === 'initial');
    }

    public function upiRecurringUpdateGatewayStatus($statusDesc, $statusCode)
    {
        $payerResponseCode = explode("|", $statusDesc);

        if((count($payerResponseCode) > 1) and
            (strlen($payerResponseCode[0])<6))
        {
            unset($payerResponseCode[0]);
            $payerResponseCode = array_values($payerResponseCode);
        }

        $gatewayData = [];
        $gatewayData[Constants::GATEWAY_STATUS_CODE] = $statusCode;
        $gatewayData[Constants::GATEWAY_STATUS_DESC] = rtrim($payerResponseCode[0]);

        if((isset($payerResponseCode[1])) and
            (empty($payerResponseCode[1] === false)))
        {
            $gatewayData[Constants::PSP_STATUS_CODE] = $payerResponseCode[1];
            $gatewayData[Constants::PSP_STATUS_DESC] = $payerResponseCode[2];
        }

        return $gatewayData;
    }

    /**
     * @param array $input
     * @return Entity
     */
    protected function firstOrCreateEntityForRecurring(array $input, string $action, bool $countAttempt = false)
    {
        $entity = $this->getUpiEntityForAction($input, $action);

        if (($entity instanceof Entity) === true)
        {
            if ($countAttempt === true)
            {
                $gatewayData    = $entity->getGatewayData();

                // If the entities do not have the attempt
                $newAttempt     = ($gatewayData[Constants::ATTEMPT] ?? 1) + 1;
                $gatewayData[Constants::ATTEMPT] = $newAttempt;

                $entity->setGatewayData($gatewayData);
                $this->repo->saveOrFail($entity);
            }
            return $entity;
        }

        $mozartAction = $this->gatewayDataIdToActionMap[$action];

        $executeAt = null;
        // For notify call ICICI is expecting the execution, we are going to make it centralize
        if ($mozartAction === 'notify')
        {
            // add 49 hours for payu
            if(in_array($input['payment']['gateway'],self::$optimizerUpiRecurringGateway) === true) {
                $executeAt = $input['payment']['created_at'] + $this->optimizerRecurringExecuteBuffer;
            } else {
                $executeAt = $input['payment']['created_at'] + $this->defaultExecuteBuffer;
            }

            $position = strpos($input['payment']['description'], 'execute at ');

            if ($position > 0)
            {
                $executeAt = (int) substr($input['payment']['description'], ($position + 11), 10);
            }
        }

        $sequenceNo = explode('seqno ', $input['payment']['description'])[1] ?? 1;

        /* Earlier, sequence number was populated with the hack : '<text>seqno <seqno>' where,
        the sequence no was being passed in payment description.
        This has now been replaced by sequence number generating algorithm.
         */

        if ($sequenceNo === 1)
        {
            // If this is debit call then make sure sequence no must be same with pre-debit call in case of auto
            // recurring payments
            $preDebitUpiEntity = null;
            $notificationEntity = null;
            if(($input['payment']['recurring_type'] === Payment\RecurringType::AUTO) and
                ($action === Action::AUTHORIZE))
            {
                $preDebitUpiEntity = $this->getUpiEntityForAction($input, Action::PRE_DEBIT);

                if($preDebitUpiEntity === null)
                {
                    $notification = new Notification\Core;

                    $notificationEntity = $notification->findDeliveredNotification($input['payment']['order_id']);
                }
            }

            if((($preDebitUpiEntity instanceof Entity) === true) and
                (empty($preDebitUpiEntity->getGatewayData()) === false) and
                (empty($preDebitUpiEntity->getGatewayData()[Constants::SEQUENCE]) === false))
            {
                $sequenceNo = $preDebitUpiEntity->getGatewayData()[Constants::SEQUENCE];
            }
            else if ((($notificationEntity instanceof Notification\Entity) === true) and
                (empty($notificationEntity->getGatewayRequest()) === false) and
                (empty($notificationEntity->getGatewayRequest()['seqNo']) === false))
            {
                $sequenceNo = $notificationEntity->getGatewayRequest()['seqNo'];
            }
            else if (isset($input['upi_mandate']['sequence_number']) === true)
            {
                $sequenceNo = $input['upi_mandate']['sequence_number'];
            }
            else
            {
                //When mandate is not in confirmed state, $input['upi_mandate']['sequence_number']) will not be set.
                //In such situation return default sequence number.
                $sequenceNo = UpiMandate\SequenceNumber::DEFAULT_SEQUENCE_NUMBER;
            }
        }

        // For first debit (intent) - retrieve the customer VPA from input (i.e. mandate create callback data),
        // since payment will not have it. This vpa is passed in the $processed array and used to set the vpa
        // in the payment entity while processing the first debit callback
        $vpa = $input['payment']['vpa'] ?? $input['upi']['vpa'] ?? null;

        $attr = [
            Entity::VPA           => $vpa,
            Entity::TYPE          => $input['upi']['flow'] ?? null,
            Entity::STATUS_CODE   => 'pending',
            Entity::GATEWAY_DATA  => [
                // Action which mozart will send in request id
                Constants::ACTION     => $mozartAction,
                // Attempt NO which will be sent in request
                Constants::ATTEMPT    => 1,
                // Short for execution time
                Constants::EXECUTE_AT => $executeAt,
                // Seq number
                Constants::SEQUENCE   => $sequenceNo,
            ],
            Entity::GATEWAY_MERCHANT_ID     => $input['terminal']['gateway_merchant_id'],
            // Merchant reference is the only option where we can save mandate id in UPI entity.
            // Its in fact the mandate id which will be point of reference UPI Recurring like For BQR and UpiQR
            Entity::MERCHANT_REFERENCE      => $input['upi_mandate']['id'],
        ];

        if (($this->isRunningOnDark() === true) || ($this->isRunningOnHallmark() === true))
        {
            // Env=1 is set for dark
            $attr[Entity::GATEWAY_DATA][Constants::ENVIRONMENT] = 1;
        }

        if($action === Action::AUTHENTICATE)
        {
            $variant = $this->evaluateSplitzExperimentForUpiAutopayRevokableFeature($input['payment']['merchant_id']);

            if($variant === true)
            {
                $attr[Entity::GATEWAY_DATA][Constants::REVOKABLE] = "N";
            }

            if ((isset($input['upi_autopay_payment_type']) === true) and ($input['upi_autopay_payment_type'] === Payment\UpiMetadata\Mode::UPI_QR))
            {
                $attr[Entity::GATEWAY_DATA][Constants::QR_PAYMENT] = '1';
            }

            if ((isset($input['upi_autopay_promo_intent']) === true) and ($input['upi_autopay_promo_intent'] === 'promo_intent'))
            {
                $attr[Entity::GATEWAY_DATA][Constants::PROMO_INTENT] = '1';
            }
        }

        return $this->createGatewayPaymentEntity($attr, $action, false);
    }

    /**
     * Evaluates the Splitz experiment for the UPI Autopay revokable feature.
     *
     * @param int $merchantId The ID of the merchant to evaluate the experiment for.
     * @return bool True if the variant is 'variant_on', false otherwise.
     */
    protected function evaluateSplitzExperimentForUpiAutopayRevokableFeature($merchantId)
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'experiment_id' => $this->app['config']->get('app.upi_autopay_revokable_feature'),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $merchantId,
                    ]),
            ];

            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::SPLITZ_RESPONSE, $response);

            if ($variant === 'variant_on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::UPI_AUTOPAY_REVOKABLE_FEATURE
            );
        }

        return false;
    }

    protected function shouldSkipNotityForAutoRecurring(array $input)
    {
        // As of now, we will skip notify based on frequency only
        return UpiMandate\Frequency::shouldSkipNotify($this->gateway, $input['upi_mandate']['frequency']);
    }

    protected function getResponseForAutoRecurring(
        array $input,
        array $data,
        Entity $upi,
        BaseException $exception = null)
    {
        $anomalies = new Anomalies($this);

        $response = new Response($data);

        $this->updateRecurringEntityWithGatewayResponse($upi, $response);

        $mandateTransformer = (new UpiMandateTransformer($this, $anomalies));
        $mandate = $mandateTransformer->from($input, $response, $upi, $exception)->transform();

        $metadataTransformer = (new UpiMetadataTransformer($this, $anomalies));
        $metadata = $metadataTransformer->from($input, $response, $upi, $exception)->transform();

        $processed = [
            // Data which is needed for mandate
            'upi_mandate'                       => $mandateTransformer->toArray(),
            // Data which is needed for UPI Metadata
            'upi'                               => $metadataTransformer->toArray(),
            // Acquirer data which is needed to be saved in payment entity
            'acquirer'                          => [
                Payment\Entity::VPA             => $upi->getVpa(),
                Payment\Entity::REFERENCE1      => $upi->getNpciTransactionId(),
                Payment\Entity::REFERENCE16     => $upi->getNpciReferenceId(),
            ],
        ];

        if ((in_array($input['gateway'],self::$optimizerUpiRecurringGateway) === true) and
            ($input['payment']['recurring_type'] == 'auto') and
            (isset($data['upi']['txn_status']) === true) and
            ($data['upi']['txn_status'] == Payment\Status::CAPTURED )) {

            $processed['upi']['txn_status'] = $data['upi']['txn_status'];
        }

        // Data Block take preference over all other entities as this means some action is needed from customer
        $dataBlock = $metadataTransformer->getDataBlock();

        if (empty($dataBlock) === false)
        {
            // Data block and acquired can not go together
            unset($processed['acquirer']);

            $processed['data'] = $dataBlock;
        }

        $this->traceAnomalies('Anomalies found with upi recurring response', $anomalies);

        $this->trace->info(TraceCode::PAYMENT_UPI_RECURRING_GATEWAY_RESPONSE, [
            'payment_id'    => $upi->getPaymentId(),
            'action'        => $upi->getAction(),
            'mandate_id'    => $upi->getMerchantReference(),
            'mode'          => $input[Entity::UPI][UpiMetadata\Entity::MODE],
            'response'      => $response->toArrayTrace(),
            'processed'     => $processed,
            'sno'           => $input['upi_mandate'][UpiMandate\Entity::SEQUENCE_NUMBER],
            'mandate'       => $mandate->toArrayTrace(),
        ]);

        return $processed;
    }

    public function processRecurringRearchResponse(
        array $input,
        array $data = null,
        BaseException $exception = null)
    {
        $gateway = $this->app['gateway']->gateway($input['payment']['gateway']);

        $anomalies = new Anomalies($gateway);

        $response = new Response($data);

        $upi = new Entity($data['upi']);

        $input['action'] = $this->action;

        $action = $this->action;
        if($action === Action::RECURRING_CALLBACK or $action === Payment\Action::VERIFY_RECURRING)
        {
            $action = Action::DEBIT;
            if (str_contains($upi['gateway_data']['id'], 'create')) {
                $action = Action::AUTHENTICATE;
            }
        }
        $upi->setAction($action);

        $mandateTransformer = (new UpiMandateTransformer($gateway, $anomalies));
        $mandate = $mandateTransformer->from($input, $response, $upi, $exception)->transform();

        $metadataTransformer = (new UpiMetadataTransformer($gateway, $anomalies));
        $metadata = $metadataTransformer->from($input, $response, $upi, $exception)->transform();

        $processed = [
            // Data which is needed for mandate
            'upi_mandate'                       => $mandateTransformer->toArray(),
            // Data which is needed for UPI Metadata
            'upi'                               => $metadataTransformer->toArray(),
            // Acquirer data which is needed to be saved in payment entity
            'acquirer'                          => [
                Payment\Entity::VPA             => $input['payment']['vpa'] ?? $data['upi']['vpa'],
                Payment\Entity::REFERENCE1      => $data['npci_txn_id'] ?? $data['data']['npci_txn_id'],
                Payment\Entity::REFERENCE16     => $data['rrn'] ?? $data['mandate']['rrn'],
            ],
        ];

        // Data Block take preference over all other entities as this means some action is needed from customer
        $dataBlock = $metadataTransformer->getDataBlock();

        if (empty($dataBlock) === false)
        {
            // Data block and acquired can not go together
            unset($processed['acquirer']);

            $processed['data'] = $dataBlock;
        }

        $this->trace->info(TraceCode::PAYMENT_UPI_RECURRING_GATEWAY_RESPONSE, [
            'payment_id'    => $input['payment']['id'],
            'action'        => $input['action'],
            'mandate_id'    => $data['merchant_reference'],
            'mode'          => $input[Entity::UPI][UpiMetadata\Entity::MODE] ?? $input['payment']['recurring_type'],
            'response'      => $response->toArrayTrace(),
            'processed'     => $processed,
            'sno'           => $input['upi_mandate'][UpiMandate\Entity::SEQUENCE_NUMBER],
            'mandate'       => $mandate->toArrayTrace(),
        ]);

        return $processed;
    }

    protected function updateRecurringEntityWithGatewayResponse(Entity $upi, Response $response)
    {
        $attributes = array_only($response->getUpi(), $upi->getFillable());

        // First pull the gateway data from update call
        $new = array_pull($attributes, Entity::GATEWAY_DATA);

        // Only if mozart sends gateway data in the request
        if (is_array($new) === true)
        {
            $current = $upi->getGatewayData();

            // In fact, we should not allow gateway data to be updated from mozart response
            // But, as of now merging this in case it seems useful from mozart's side
            $updated = array_merge($current, $new);

            $attributes[Entity::GATEWAY_DATA] = $updated;
        }

        if((isset($response['status_desc'])) and
            (empty($response['status_desc']) === false))
        {
            $attributes[Entity::GATEWAY_ERROR] = $upi->getGatewayError();
            if($attributes[Entity::GATEWAY_ERROR] === null)
            {
                $attributes[Entity::GATEWAY_ERROR] = [];
            }

            $payerResponseCodeDes = $this->upiRecurringUpdateGatewayStatus($response['status_desc'], $response['status_code']);
            $attributes[Entity::GATEWAY_ERROR] = array_replace($attributes[Entity::GATEWAY_ERROR],$payerResponseCodeDes);

            if ((isset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_CODE]) === true) and
                (isset($payerResponseCodeDes[Constants::PSP_STATUS_CODE]) === false))
            {
                unset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_CODE]);
            }

            if ((isset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_DESC]) === true) and
                (isset($payerResponseCodeDes[Constants::PSP_STATUS_DESC]) === false))
            {
                unset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_DESC]);
            }

            $this->trace->info(TraceCode::UPI_RECURRING_PAYER_RESPONSE_CODE, [
                'attributes'                => $attributes,
                'payer_response_code'       => $payerResponseCodeDes,
            ]);
        }

        if(isset($response[Entity::UPI][Entity::GATEWAY_ERROR]) === true)
        {
            $attributes[Entity::GATEWAY_ERROR] = $upi->getGatewayError();
            if($attributes[Entity::GATEWAY_ERROR] === null)
            {
                $attributes[Entity::GATEWAY_ERROR] = [];
            }

            $attributes[Entity::GATEWAY_ERROR] = array_replace($attributes[Entity::GATEWAY_ERROR], $response[Entity::UPI][Entity::GATEWAY_ERROR]);

            if ((isset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_CODE]) === true) and
                (isset($response[Entity::UPI][Entity::GATEWAY_ERROR][Constants::PSP_STATUS_CODE]) === false))
            {
                unset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_CODE]);
            }

            if ((isset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_DESC]) === true) and
                (isset($response[Entity::UPI][Entity::GATEWAY_ERROR][Constants::PSP_STATUS_DESC]) === false))
            {
                unset($attributes[Entity::GATEWAY_ERROR][Constants::PSP_STATUS_DESC]);
            }

            $this->trace->info(TraceCode::UPI_RECURRING_PAYER_RESPONSE_CODE, [
                'attributes'                => $attributes,
                'payer_response_code'       => $response[Entity::UPI][Entity::GATEWAY_ERROR],
            ]);
        }

        // fields like npci_reference_id, npci_txn_id must be checked against mismatch for anomalies
        $this->updateGatewayPaymentResponse($upi, $attributes, false);
    }

    protected function recurringPaymentVerify(array $input)
    {
        // Here we check which step for the first recurring payment needs to be verified. If authorize entity has been
        // created, that means mandate creation was successful and we need to verify first debit. Otherwise, we need
        // to verify mandate creation only.

        $upiEntity = $this->getUpiEntityForAction($input, Action::AUTHORIZE);

        if (($upiEntity instanceof Entity) === false)
        {
            $upiEntity = $this->getUpiEntityForAction($input, Action::AUTHENTICATE);
        }

        if (($upiEntity instanceof Entity) === false)
        {
            throw new LogicException('Upi Entity not found');
        }

        $this->setRequestDataForUpiRecurring($input, $upiEntity);

        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->upiRecurringVerify($input, $upiEntity);

        return $response;
    }

    protected function recurringPaymentVerifyGateway(array $input)
    {
        // Here we check which step for the first recurring payment needs to be verified. If authorize entity has been
        // created, that means mandate creation was successful and we need to verify first debit. Otherwise, we need
        // to verify mandate creation only.

        $upiEntity = $this->getUpiEntityForAction($input, Action::AUTHORIZE);

        if (($upiEntity instanceof Entity) === false)
        {
            $upiEntity = $this->getUpiEntityForAction($input, Action::AUTHENTICATE);
        }

        if (($upiEntity instanceof Entity) === false)
        {
            throw new LogicException('Upi Entity not found');
        }

        $this->setRequestDataForUpiRecurring($input, $upiEntity);

        $gateway = $this->getMozartGatewayWithModeSet();

        $response = $gateway->upiRecurringVerifyGateway($input, $upiEntity);

        return $response;
    }

    public function extractUpiRecurringMandateAndPaymentProperties($upiEntity, $verify)
    {
        $input = $verify->input;

        $response = $verify->verifyResponseContent;

        $anomalies = new Anomalies($this);

        $responseData = new Response($response['data']);

        $mandateTransformer = (new UpiMandateTransformer($this, $anomalies));
        $mandate = $mandateTransformer->from($input, $responseData, $upiEntity)->transform();

        $metadataTransformer = (new UpiMetadataTransformer($this, $anomalies));
        $metadata = $metadataTransformer->from($input, $responseData, $upiEntity)->transform();

        $processed = [
            // Data which is needed for mandate
            'upi_mandate'                       => $mandateTransformer->toArray(),
            // Data which is needed for UPI Metadata
            'upi'                               => $metadataTransformer->toArray(),
            // Acquirer data which is needed to be saved in payment entity
            'acquirer'                          => [
                Payment\Entity::VPA             => $upiEntity->getVpa(),
                Payment\Entity::REFERENCE1      => $upiEntity->getNpciTransactionId(),
                Payment\Entity::REFERENCE16     => $upiEntity->getNpciReferenceId(),
            ],
        ];

        return $processed;
    }
}
