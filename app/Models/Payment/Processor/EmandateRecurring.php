<?php

namespace RZP\Models\Payment\Processor;

use DateTime;
use DateTimeZone;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Entity;
use RZP\Models\Customer\Token;
use RZP\Models\Payment\Gateway;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\EMandate\Constants as EmandateConstants;


trait EmandateRecurring
{
    /**
     * shouldSkipAuthorizeOnRecurringForEmandate : Check to see if emandate
     * token should be updated for API based gateways having async token flow,
     * when payment is already authorized.
     * This flow is similar to file based emandate flow.
     *
     * Called from updateAndNotifyPaymentAuthorized.
     * @param Entity $payment
     * @param array $data
     */
    protected function shouldSkipAuthorizeOnRecurringForEmandate(Entity $payment, array $data): bool
    {
        if (($payment->isEmandateRecurring() === false) or
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === false))
        {
           return false;
        }

        // For Auto Recurring
        if ($payment->isEmandateAutoRecurring() === true)
        {
            // Only skip authorize flow if gateway has not processed payment.
            // We will wait for webhook in this case.
            if ((isset($data['additional_data']) === true) and
                (isset($data['additional_data']['gateway_payment_status']) === true) and
                ($data['additional_data']['gateway_payment_status'] === 'pending'))
            {
                return true;
            }

            return false;
        }

        // Other checks for initial payment
        //
        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return false;
        }

        //
        // If payment is authorized and token is not yet updated, it means
        // gateway is sending a webhook with token status asynchronously.
        // In such cases, just update the token.
        //
        if (($payment->isEmandate() === true) and
            ($token->isRecurring() === false) and
            ($payment->hasBeenAuthorized() === true))
        {
            return true;
        }

        return false;
    }

    /**
     * updateRecurringEntitiesForEmandateIfApplicable : Update token recurring
     * parameters.
     *
     * Called from updateAndNotifyPaymentAuthorized.
     * @param Entity $payment
     * @param array $data
     * @param bool $wasFailed
     */
    protected function updateRecurringEntitiesForEmandateIfApplicable(Entity $payment, array $data, bool $wasFailed = false)
    {
        // For Auto Recurring
        if ($payment->isEmandateAutoRecurring() === true)
        {
            $this->updatePaymentEntityForEmandateAsyncRecurringPayment($payment, $data);
            return;
        }

        // only for emandate payments
        if (($payment->isEmandateRecurring() === false) or
            ($payment->isRecurringTypeInitial() === false) or
            ($payment->isSecondRecurring() === true) or
            ($payment->hasBeenAuthorized() === false) or
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === false))
        {
           return;
        }

        $token = $payment->getGlobalOrLocalTokenEntity();

        if ($token === null)
        {
            return;
        }

        $this->trace->info(
            TraceCode::PAYMENT_UPDATE_TOKEN,
            [
                'payment_id'      => $payment->getId(),
                'token_id'        => $payment->getTokenId(),
                'global_token_id' => $payment->getGlobalTokenId(),
                'gateway_data'    => $data,
            ]);

        // Token used count should not be incremented here,
        // since this flow is there to update only the recurring parameters

        $oldRecurringStatus = $token->getRecurringStatus();

        (new Token\Core)->updateTokenFromEmandateGatewayData($token, $data);

        if ($token->getTerminalId() === null)
        {
            $token->terminal()->associate($payment->terminal);
        }

        $this->repo->saveOrFail($token);

        $gatewayRecurringStatus = $data[Token\Entity::RECURRING_STATUS];
        
        if ($gatewayRecurringStatus === Token\RecurringStatus::REJECTED)
        {
            $this->refundPayment($payment);
        }
        elseif ($gatewayRecurringStatus === Token\RecurringStatus::CONFIRMED)
        {
            $this->updateGatewayTokenAttributes($payment, $token);
        }
        
        $this->eventTokenStatus($token, $oldRecurringStatus);
    }

    private function updateGatewayTokenAttributes(Entity $payment, Token\Entity $token)
    {
        $reference = $payment->getReferenceForGatewayToken();

        $gatewayTokens = $this->repo->gateway_token->findByTokenAndReference($token, $reference);

        $gateway = $payment->getGateway();

        $gatewayTokensToUpdate = $gatewayTokens->filter(
                                        function($gatewayToken) use ($gateway)
                                        {
                                            return ($gatewayToken->getGateway() === $gateway);
                                        });


        if ($gatewayTokensToUpdate->count() === 0)
        {
            $this->trace->critical(
                TraceCode::GATEWAY_TOKEN_MISMATCH,
                [
                    'payment_id'    => $payment->getId(),
                    'token_id'      => $token->getId(),
                    'gateway'       => $gateway,
                    'reference'     => $reference,
                ]);

            // GT should have already been created if the flow reaches here.
            // Just exit if that is the case.
            return;
        }
        else
        {
            // There will be only one for sure.
            // There can't be more than 1 because, the only time we create is
            // when there doesn't exist a single gateway_token of the gateway.
            // All other cases, we only update the existing one. Hence, there
            // can never be more than one gateway_token of a gateway.

            if ($gatewayTokensToUpdate->count() > 1)
            {
                $this->trace->critical(
                    TraceCode::GATEWAY_TOKEN_TOO_MANY_PRESENT,
                    [
                        'count'         => $gatewayTokensToUpdate->count(),
                        'payment_id'    => $payment->getId(),
                        'token_id'      => $token->getId()
                    ]);

                // This is unexpected behaviour and should never
                // happen and hence just returning back from here.
                return;
            }

            $gatewayTokenToUpdate = $gatewayTokensToUpdate->first();

            $gatewayTokenToUpdate->setRecurring($token->isRecurring());

            $this->repo->saveOrFail($gatewayTokenToUpdate);
        }
    }

    private function refundPayment(Entity $payment)
    {
        if ($payment->isAuthorized() === false)
        {
            $this->trace->critical(TraceCode::PAYMENT_RECURRING_INVALID_STATUS,
                [
                    'status' => $payment->getStatus(),
                    'payment_id' => $payment->getId(),
                ]);

            return;
        }

        $processor = new Processor($this->merchant);

        // based on experiment, refund request will be routed to Scrooge
        return $processor->refundAuthorizedPayment($payment);
    }

    /**
     * This is called when gateway returns a pending status.
     * In such cases we return the payment id and other details to merchant.
     * For payment to reach terminal status, we wait for webhook from gateway.
     *
     * Called from processPaymentFinal.
     * @param Entity $payment
     * @param array $data
     */
    protected function processRecurringCreatedForEmandateAsyncGateway(Entity $payment, array $data)
    {
        if (($payment->isEmandateAutoRecurring() === true) and
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true))
        {
            $data = ['razorpay_payment_id' => $payment->getPublicId()];

            if (($payment->hasOrder() === true) and
                ($this->app['basicauth']->isProxyOrPrivilegeAuth() === false) and
                ($this->app->runningInQueue() === false))
            {
                $this->fillReturnDataWithOrder($payment, $data);
            }

            return $data;
        }

        throw new Exception\LogicException('Should not be called for any payment other than Emandate Auto Recurring');
    }

    // if we got a webhook and payment is still in pending status,
    // then just log it and skip authorize flow.
    // No need to update any entities.
    protected function updatePaymentEntityForEmandateAsyncRecurringPayment(Entity $payment, array $data)
    {
        if (($payment->isCreated() === true) and
            (Gateway::isApiBasedAsyncEMandateGateway($payment->getGateway()) === true) and
            (isset($data['additional_data']) === true) and
            (isset($data['additional_data']['gateway_payment_status']) === true) and
            ($data['additional_data']['gateway_payment_status'] === 'pending'))
        {
            $this->trace->info(
                TraceCode::PAYMENT_RECURRING_DEBIT_STATUS_UNCHANGED,
                [
                    'payment_id'      => $payment->getId(),
                    'token_id'        => $payment->getTokenId(),
                    'gateway_data'    => $data,
                ]);
        }
    }
    
    public function getCurrentMonthIST($timestamp=null)
    {
        try {
            $timezone = new DateTimeZone('Asia/Kolkata');
    
            if($timestamp !== null)
            {
                $datetime = new DateTime("@$timestamp");
        
                $datetime->setTimezone($timezone);
        
                return $datetime->format('M');
            }
    
            $now = new DateTime('now', $timezone);
    
            return $now->format('M');
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::CURRENT_MONTH_FETCH_ERROR);
        }
        
        return null;
    }
    
    public function resetEmandateTokenDetails(Entity $payment)
    {
        $token = $payment->getGlobalOrLocalTokenEntity();
    
        $configs = $token->getNotes()[Token\Constants::EMANDATE_CONFIGS] ?? [];
    
        if($configs !== null or empty($configs) === false)
        {
            $this->trace->info(TraceCode::EMANDATE_TOKEN_CONFIG_RESET, [
                "step" => "payment_processing"
            ]);
    
            $token->setNotes([]);
    
            $this->repo->saveOrFail($token);
        }
    }
    
    public function achReturnProcessingFlow(Entity $payment, $errorCode)
    {
        try
        {
            $this->trace->info(
                TraceCode::EMANDATE_TOKEN_BLOCK_FLOW,
                [
                    "payment_id"    => $payment->getId(),
                    "token_id"      => $payment->getTokenId(),
                    "error"         => $errorCode,
                    "merchant_id"   => $payment->getMerchantId(),
                    "step"          => EmandateConstants::ACH_RETURNS_FLOW
                ]);

            // if payment created and response received are different months we ignore them
            if ($this->isCurrentMonth($payment) === false)
            {
                return [];
            }
    
            $token = $payment->getGlobalOrLocalTokenEntity();
            
            $emandateConfigs = $token->getNotes()[Token\Constants::EMANDATE_CONFIGS] ?? [];
    
            $retriesAttempted = (int)$emandateConfigs[Token\Constants::RETRY_ATTEMPTS] ?? 0;
            
            $previousFlow = $emandateConfigs[Token\Constants::TOKEN_FLOW] ?? "";
            
            if ($previousFlow !== EmandateConstants::ACH_RETURNS_FLOW)
            {
                $retriesAttempted = 0;
            }
    
            $updatedConfigs = [
                EmandateConstants::COOLDOWN_PERIOD      => $this->calculateBlockPeriod(EmandateConstants::EMANDATE_DEBIT_COOLDOWN),
                EmandateConstants::RETRY_ATTEMPTS       => $retriesAttempted + 1,
                Token\Constants::LAST_UPDATED_MONTH     => $this->getCurrentMonthIST(),
                Token\Constants::LAST_UPDATED_ON        => Carbon::now(Timezone::IST)->toDateTimeString(),
                Token\Constants::GATEWAY_ERROR          => $errorCode,
                Token\Constants::EMANDATE_TOKEN_STATUS  => Token\Constants::BLOCKED_TEMPORARILY,
                Token\Constants::TOKEN_FLOW             => EmandateConstants::ACH_RETURNS_FLOW
            ];
    
            (new Token\Core)->updateEmandateTokenDetails($token, $updatedConfigs);
    
            $this->repo->saveOrFail($token);
    
            $this->trace->info(TraceCode::EMANDATE_CONFIG_SET_DETAILS, [
                "present_configs" => $updatedConfigs,
                "previous_configs" => $emandateConfigs
            ]);
    
            if ($updatedConfigs[Token\Constants::EMANDATE_TOKEN_STATUS] === Token\Constants::BLOCKED_TEMPORARILY)
            {
                    $this->emandateDescError = " The token has been put on hold temporarily for raising recurring payments.";
            }
            
            return [];
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex, null, TraceCode::EMANDATE_NR_TOKEN_UPDATE_ERROR, [
                "merchant_id" => $payment->getMerchantId(),
                "payment_id"  => $payment->getId()
            ]);
        }
    }
    
    public function isCurrentMonth($payment)
    {
        $paymentCreatedMonth = $this->getCurrentMonthIST($payment->getCreatedAt());
    
        $currentMonth = $this->getCurrentMonthIST();
    
        // if payment created and response received are different months we ignore them
        if ($paymentCreatedMonth !== $currentMonth)
        {
            $this->trace->info(TraceCode::EMANDATE_PAYMENT_CREATED_MONTH,
                [
                    "payment_created_month" => $paymentCreatedMonth,
                    "current_month"         => $currentMonth,
                    "payment_created_at"    => $payment->getCreatedAt(),
                    'token_id'              => $payment->getTokenId(),
                    'payment_id'            => $payment->getId(),
                    "merchant_id"           => $payment->getMerchantId()
                ]);
        
            return false;
        }
        
        return true;
    }
    
    public function nrProcessingFlow(Entity $payment, $nrErrorCode)
    {
        $this->trace->info(
            TraceCode::EMANDATE_PAYMENT_UPDATE_TOKEN,
            [
                "payment_id"      => $payment->getId(),
                "token_id"        => $payment->getTokenId(),
                "nr_error_code"   => $nrErrorCode,
                "merchant_id"     => $payment->getMerchantId(),
                "step"            => EmandateConstants::NR_FLOW
            ]);
    
        // if payment created and response received are different months we ignore them
        if ($this->isCurrentMonth($payment) === false)
        {
            return [];
        }
        
        $merchantConfig = $this->fetchEmandateDcsConfigs($payment->getMerchantId());
    
        $this->trace->info(TraceCode::EMANDATE_FETCH_MERCHANT_CONFIG, [
            "merchant_config"       => $merchantConfig,
            'token_id'              => $payment->getTokenId(),
            'payment_id'            => $payment->getId(),
            "merchant_id"           => $payment->getMerchantId()
        ]);
        
        $token = $payment->getGlobalOrLocalTokenEntity();
        
        if ($merchantConfig === null or $token === null)
        {
            return [];
        }
        
        $emandateConfig = $this->fetchConfigsForToken($token, $merchantConfig, $nrErrorCode);
        
        $configArray = [
            "emandate_new_configs"      => $emandateConfig,
            "emandate_previous_configs" => $token->getNotes()[Token\Constants::EMANDATE_CONFIGS] ?? [],
            "token_id"                  => $token->getId(),
            'payment_id'                => $payment->getId(),
            "merchant_id"               => $payment->getMerchantId()
        ];
    
        $this->trace->info(TraceCode::EMANDATE_CONFIG_SET_DETAILS,  $configArray);
        
        if($emandateConfig === null)
        {
            return [];
        }
    
        (new Token\Core)->updateEmandateTokenDetails($token, $emandateConfig);
        
        $this->repo->saveOrFail($token);
        
        if(isset($emandateConfig[Token\Constants::EMANDATE_TOKEN_STATUS]) === true and
            $emandateConfig[Token\Constants::EMANDATE_TOKEN_STATUS] === Token\Constants::BLOCKED_TEMPORARILY)
        {
            $this->trace->info(TraceCode::EMANDATE_TOKEN_BLOCKED, $configArray);
            
            $this->emandateDescError = " The token has been put on hold temporarily for raising recurring payments.";
        }
        
        return [];
    }
    
    public function fetchConfigsForToken($token, $merchantConfig, $nrErrorCode)
    {
        // merchant configs
        $retriesAllowed = $merchantConfig[Token\Constants::RETRY_ATTEMPTS] ?? 0;
        
        $coolDownPeriod = $merchantConfig[Token\Constants::COOLDOWN_PERIOD] ?? 0;
    
        $tempErrorEnableFlag = $merchantConfig[EmandateConstants::TEMPORARY_ERRORS_ENABLE_FLAG] ?? false;
        
        // token configs
        $emandateConfig = $token->getNotes()[Token\Constants::EMANDATE_CONFIGS] ?? [];
    
        $retriesAttempted = (int) $emandateConfig[Token\Constants::RETRY_ATTEMPTS] ?? 0;
    
        $emandateTokenStatus = $emandateConfig[Token\Constants::EMANDATE_TOKEN_STATUS] ?? null;
    
        $temporaryErrorCode = $nrErrorCode["temporary_error_code"] ?? null;
    
        $previousFlow = $emandateConfig[Token\Constants::TOKEN_FLOW] ?? "";
    
        // if previously ach flow is present need to reset values
        if ($previousFlow != EmandateConstants::NR_FLOW)
        {
            $retriesAttempted = 0;
    
            $emandateTokenStatus = null;
        }
    
        // Case 1: already token blocked or temp config is not enabled, no need to go to flow
        if($tempErrorEnableFlag === false or $emandateTokenStatus !== null)
        {
            return null;
        }
    
        //Incase if error is not temporary and config exists, we are removing emandate configs
        // Need to do this if we got different error in between
        if($temporaryErrorCode === null)
        {
            if($emandateConfig !== null or empty($emandateConfig) === false)
            {
                $this->trace->info(
                    TraceCode::EMANDATE_TOKEN_CONFIG_RESET,
                    [
                        'token_id'        => $token->getId(),
                        'nr_error_code'   => $nrErrorCode
                    ]);
    
                return [];
            }
        
            return null;
        }
    
    
        // cases for temporarily blocking token
        if($tempErrorEnableFlag === true and $retriesAllowed > 0 and $coolDownPeriod > 0)
        {
            $lastUpdatedMonth = $emandateConfig[Token\Constants::LAST_UPDATED_MONTH] ?? '';
            
            $lastUpdatedDate =  Carbon::now(Timezone::IST)->toDateTimeString();
    
            $currentMonth = $this->getCurrentMonthIST();
            
            $previousError = $emandateConfig[Token\Constants::GATEWAY_ERROR] ?? null;
            
            // Case 2: Previous error doesn't match with present error, reset with new error
            // Case 3: Previously no error present, start new retry
            // Edge Case: If update attempt in token month doesn't match with present month restart again
            if($previousError === null or
                $previousError !== $temporaryErrorCode or
                $currentMonth !== $lastUpdatedMonth)
            {
                return [
                    Token\Constants::RETRY_ATTEMPTS                 => 1,
                    Token\Constants::LAST_UPDATED_MONTH             => $currentMonth,
                    Token\Constants::LAST_UPDATED_ON                => $lastUpdatedDate,
                    Token\Constants::GATEWAY_ERROR                  => $temporaryErrorCode,
                    Token\Constants::TOKEN_FLOW                     => EmandateConstants::NR_FLOW
                ];
                
            }
            else
            {
                // Case 4: Already max retries attempted, will block token
                // Case 5: If not reached, will increase retry count
                if ($retriesAttempted + 1 >= $retriesAllowed)
                {
                    return [
                        Token\Constants::RETRY_ATTEMPTS             => $retriesAttempted + 1,
                        Token\Constants::COOLDOWN_PERIOD            => $this->calculateBlockPeriod($coolDownPeriod),
                        Token\Constants::EMANDATE_TOKEN_STATUS      => Token\Constants::BLOCKED_TEMPORARILY,
                        Token\Constants::LAST_UPDATED_MONTH         => $currentMonth,
                        Token\Constants::LAST_UPDATED_ON            => $lastUpdatedDate,
                        Token\Constants::GATEWAY_ERROR              => $temporaryErrorCode,
                        Token\Constants::TOKEN_FLOW                 => EmandateConstants::NR_FLOW
                    ];
                }
                else
                {
                    return [
                        Token\Constants::RETRY_ATTEMPTS             => $retriesAttempted + 1,
                        Token\Constants::LAST_UPDATED_MONTH         => $currentMonth,
                        Token\Constants::LAST_UPDATED_ON            => $lastUpdatedDate,
                        Token\Constants::GATEWAY_ERROR              => $temporaryErrorCode,
                        Token\Constants::TOKEN_FLOW                 => EmandateConstants::NR_FLOW
                    ];
                }
            }
        }
        
        return null;
    }
    
    public function calculateBlockPeriod($coolDownPeriod)
    {
        $currentTime = Carbon::now('Asia/Kolkata');
        
        $blockDate = $currentTime->addDays((int) $coolDownPeriod);
        
        $endOfMonthDate = Carbon::now('Asia/Kolkata')->endOfMonth();
        
        if($blockDate <= $endOfMonthDate)
        {
            return $this->modifyCooloffBasedOnPresentedDay($blockDate->getTimestamp());
        }
        else
        {
            return $this->modifyCooloffBasedOnPresentedDay($endOfMonthDate->getTimestamp());
        }
    }
    
    public function modifyCooloffBasedOnPresentedDay($timestamp)
    {
        $startOfDay = Carbon::createFromTimestamp($timestamp)->tz('Asia/Kolkata')->startOfDay();
        
        return $startOfDay->addHours(9)->getTimestamp();
    }
}
