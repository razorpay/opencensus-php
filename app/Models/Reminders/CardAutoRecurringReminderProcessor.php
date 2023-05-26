<?php

namespace RZP\Models\Reminders;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Models\CardMandate\CardMandateNotification;

class CardAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        // We were getting 2 parallel calls from reminder service and both of the payments
        // were going through the gateway, resulting in unexpected payment status. Hence, adding mutex
        // to avoid data race condition.

        $mutexKey = 'card_recurring_reminder_' . $id;

        return $this->app['api.mutex']->acquireAndRelease(
        $mutexKey,
        function() use ($id)
        {
            return $this->processPayment($id);
        },
        120);
    }

    public function processPayment(string $id)
    {
        $payment = (new Payment\Core)->retrievePaymentById($id);

        if(($payment->isAuthorized() === true) or
           ($payment->isCaptured() === true) or
           ($payment->getStatus() === Payment\Status::REFUNDED))
        {
            $this->trace->info(TraceCode::PAYMENT_ALREADY_CAPTURED_OR_AUTHORIZED, [
                'paymentId'    => $payment->getId(),
                'isAuthorized' => $payment->isAuthorized(),
                'isCaptured'   => $payment->isCaptured(),
            ]);

            return [];
        }

        $processor = (new Payment\Processor\Processor($payment->merchant));

        $processor->setPayment($payment);

        $verified = false;

        try
        {
            $validatePayment = (new CardMandateNotification\Core)->verifyNotification($payment);

            $verified = true;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                null,
                TraceCode::CARD_MANDATE_NOTIFICATION_PAYMENT_VERIFY_FAILED,
                ["payment_id" => $id]);

            $processor->failNotificationVerifyFailedCardAutoRecurringPayment($payment, $e);
        }

        if ($verified === false)
        {
            return [];
        }

        try
        {
            $this->validateRecurringToken($payment);
        }
        catch (\Exception $e)
        {
            $traceCode = TraceCode::RECURRING_TOKEN_DELETED_OR_EXPIRED;

            if ($e->getCode() === ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS)
            {
                $traceCode = TraceCode::RECURRING_CARD_PAYMENT_IIN_MISSING;
            }

            $this->trace->traceException(
                $e,
                null,
                $traceCode
            );

            $processor->failInvalidRecurringTokenCardAutoRecurringPayment($payment, $e);

            return [];
        }

        $gatewayInput = $this->getGatewayInputForPayment($payment, $processor);
        $gatewayInput['acs_afa_authentication'] = array();
        if ($gatewayInput['card']['network_code'] == "VISA" &&
            (($validatePayment['validate_payment']['afa_required'] === true) ||
                (isset($validatePayment['validate_payment']['gateway']) &&
                    ($validatePayment['validate_payment']['gateway'] === 'billdesk_sihub'))))
        {
            $gatewayInput['acs_afa_authentication'] = array(
                'xid'   => $validatePayment['validate_payment']['xid'],
                'cavv2' => $validatePayment['validate_payment']['cavv2']
            );
        }

        //read payment_analytics from DB for Rupay
        if ($payment->card->isRupay())
        {
            $gatewayInput['payment_analytics'] = $this->repo->payment_analytics->findByPaymentID($payment->getId());
            $payment->setMetadataKey('payment_analytics', $gatewayInput['payment_analytics']);
        }

        $processor->gatewayRelatedProcessing($payment, [], $gatewayInput);

        return [];
    }

    public function getGatewayInputForPayment(Payment\Entity $payment, Payment\Processor\Processor $processor)
    {
        $token = $payment->localToken;

        $card = $this->repo->card->fetchForToken($token);

        $cardActualIin = $card->fetchIinUsingTokenIinForRecurringIfApplicable();

        $iin = $this->app['repo']->iin->find($cardActualIin);

        if (($card->isRzpSavedCard() === false) and
            ($this->isExperimentEnabledForTokenisedCard($token->getMerchantId()) === true) and
            ($this->shouldRecurringAutoPaymentGoThroughTokenisedCard($cardActualIin) === true))
        {
            $this->logPaymentRoutingInfo($payment, $card, false);

            $cardInput = $processor->createCardForNetworkTokenCardMandate($card, $token, [], $payment);
        }
        else
        {
            $this->logPaymentRoutingInfo($payment, $card, true);

            // This is for those case in which tokenised card subsequent payment using actual card
            if ($card->isRzpSavedCard() === false)
            {
                $payment->card()->associate($card);
                $this->repo->saveOrFail($payment);
            }

            $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken(),$card->toArray(),$payment->getGateway());

            $cardInput = array_merge(
                $card->toArray(),
                [
                    'number'       => $cardNumber,
                    'cvv'          => null,
                    'message_type' => $iin['message_type'],
                ]);
        }

        return [
            'card' => $cardInput,
            'iin'  => $iin->toArray(),
        ];
    }

    public function isExperimentEnabledForTokenisedCard($merchantId): bool
    {
        try
        {
            $variant = $this->app['razorx']->getTreatment(
                $merchantId,
                Merchant\RazorxTreatment::RECURRING_SUBSEQUENT_THROUGH_TOKENISED_CARD,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::RECURRING_SUBSEQUENT_TOKENISATION_RAZORX_EXPERIMENT
            );
        }

        return false;
    }

    public function shouldRecurringAutoPaymentGoThroughTokenisedCard($iin): bool
    {
        try
        {
            $variant = $this->app['razorx']->getTreatment(
                $iin,
                Merchant\RazorxTreatment::RECURRING_SUBSEQUENT_THROUGH_TOKENISED_CARD,
                $this->mode
            );

            if (strtolower($variant) === 'on')
            {
                return true;
            }
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::RECURRING_SUBSEQUENT_TOKENISATION_RAZORX_EXPERIMENT
            );
        }

        return false;
    }

    protected function logPaymentRoutingInfo(Payment\Entity $payment,
                                             Card\Entity $card,
                                             bool $isActualCard)
    {
        $this->trace->info(
            TraceCode::RECURRING_CARD_PAYMENT_ROUTING_INFO,
            [
                'paymentId'             => $payment->getId(),
                'tokenId'               => $payment->localToken->getId(),
                'merchantId'            => $payment->getMerchantId(),
                'isTokenised'           => ($card->isRzpSavedCard() === false) ? 'true' : 'false',
                'routedThrough'         => $isActualCard ? 'actualCard' : 'tokenisedCard',
                'cardInfo'      => [
                    'issuer'    => $card->getIssuer(),
                    'network'   => $card->getNetworkCode(),
                    'type'      => $card->getType(),
                ],
            ]
        );
    }

    protected function validateRecurringToken($payment)
    {
        $tokenId = $payment->localToken->getId();

        $token = $this->app['repo']->token->find($tokenId);

        if ($token === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_ABSENT_FOR_RECURRING_PAYMENT,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'token_id'   => $tokenId
                ]);
        }

        $currentTime = Carbon::now()->getTimestamp();

        if (($token->getExpiredAt() !== null) and ($token->getExpiredAt() < $currentTime) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_RECURRING_TOKEN_EXPIRED,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'token_id'   => $token->getId(),
                    'expired_at' => $token->getExpiredAt(),
                ]);
        }

        $card = $this->repo->card->fetchForToken($token);

        $cardActualIin = $card->fetchIinUsingTokenIinForRecurringIfApplicable();

        if (empty($cardActualIin) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS,
                null,
                [
                    'payment_id' => $payment->getId(),
                    'token_id'   => $token->getId(),
                ]);
        }
    }
}
