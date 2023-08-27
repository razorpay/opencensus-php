<?php

namespace RZP\Models\CardMandate\MandateHubs\OptimizerHubs;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Timezone;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Models\CardMandate;
use RZP\Models\Plan\Subscription;


use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

// All common utility functions needs to be placed here. That way all optimzier gateways can share repeated code.
trait OptimizerHubUtils
{
    protected function buildMandateEntity(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        $token = $payment->localToken;

        $card = $payment->card;

        $maxAmount = $token->getMaxAmount();

        if ($maxAmount === null) {
            $maxAmount = Constants::MAX_AMOUNT_DEFAULT;
        }

        $startTime = $token->getStartTime();
        if ($startTime === null) {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $endTime = $token->getExpiredAt();
        if ($endTime === null) {
            $endTime = $token->card->getExpiryTimestamp();
        }

        $frequency = Constants::FREQUENCY_AS_PRESENTED;
        if (empty($input['frequency']) === false) {
            $frequency = $input['frequency'];
        }

        $debitType = Constants::DEBIT_TYPE_VARIABLE_AMOUNT;
        if (empty($input['debit_type']) === false) {
            $debitType = $input['debit_type'];
        }

        // changes for RBI guidelines to ensure same frequency as presented during subscription is passed on
        if ($payment->getSubscriptionId() !== null)
        {
            try
            {
                $input = [
                    Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId())
                ];

                $subscriptionData = $this->app['module']->subscription->fetchSubscriptionInfoCardMandate($input, $payment->merchant);

                $frequency = $subscriptionData['frequency'] ?? Constants::FREQUENCY_AS_PRESENTED;

                $maxAmount = $subscriptionData['max_amount'] ?? $maxAmount;

                $endTime = $subscriptionData['end_time'] ?? $endTime;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::CRITICAL,
                    TraceCode::CARD_MANDATE_SUBSCRIPTIONS_FETCH_FAILURE);
            }
        }

        $mandateAttributes = [
            Mandate::MANDATE_ID => null,
            Mandate::MANDATE_CARD_ID => $card ? $card[Constants::CARD_ID] : null,
            Mandate::MANDATE_CARD_NAME => $card ? $card[Constants::CARD_NAME] : null,
            Mandate::MANDATE_CARD_LAST4 => $card ? $card[Constants::CARD_LAST4] : null,
            Mandate::MANDATE_CARD_NETWORK => $card ? $card[Constants::CARD_NETWORK] : null,
            Mandate::MANDATE_CARD_TYPE => $card ? $card[Constants::CARD_TYPE] : null,
            Mandate::MANDATE_CARD_ISSUER => $card ? $card[Constants::CARD_ISSUER] : null,
            Mandate::MANDATE_CARD_INTERNATIONAL => $card ? $card[Constants::CARD_INTERNATIONAL] : null,
            Mandate::DEBIT_TYPE => $debitType,
            Mandate::CURRENCY => $payment->getCurrency() ?? null,
            Mandate::MAX_AMOUNT => $maxAmount,
            Mandate::AMOUNT => $payment->getAmount() ?? null,
            Mandate::START_AT => $startTime,
            Mandate::END_AT => $endTime,
//            Mandate::TOTAL_CYCLES               => $response[Constants::TOTAL_CYCLES] ?? null,
//            Mandate::MANDATE_INTERVAL           => $response[Constants::INTERVAL] ?? null,
            Mandate::FREQUENCY => $frequency,
        ];

        return (new Mandate(MandateHubs::PAYU_HUB, $mandateAttributes));
    }

    protected function buildCancelMandatePayload(CardMandate\Entity $cardMandate)
    {
        return [
            Constants::GATEWAY => $cardMandate->getMandateHub(),
            Constants::PAYMENT => [
                Constants::GATEWAY => $cardMandate->getMandateHub(),
                Constants::ID => null,
            ],
            Constants::TERMINAL => $cardMandate->terminal ? $cardMandate->terminal : null,
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::IS_OPTIMIZER_CARD_MANDATE => true
        ];
    }

    // Fetches data from Cards.Authorization entity based on gatewayReferenceKey and updates the result as card_mandate.mandate_id
    protected function updateMandateId(CardMandate\Entity $cardMandate, Payment\Entity $payment, string $gatewayReferenceKey)
    {
        $paymentId = $payment->getId();

        $request = [
            'fields' => [$gatewayReferenceKey],
            'payment_ids' => [$paymentId],
        ];

        $authorizationData = $this->app['card.payments']->fetchAuthorizationData($request);

        $cardMandate->setMandateId($authorizationData[$paymentId][$gatewayReferenceKey]);

        $cardMandate->saveOrFail();
    }

    // Store vaultTokenPan in card mandate entity
    protected function storeVaultTokenPan(CardMandate\Entity $cardMandate, $networkToken)
    {
        $input[Constants::TOKEN] = $networkToken;
        (new CardMandate\Core())->storeVaultTokenPan($cardMandate, $input);
    }

    protected function buildUpdateTokenPayload(CardMandate\Entity $cardMandate, $token)
    {
        return [
            Constants::GATEWAY => $cardMandate->getMandateHub(),
            Constants::PAYMENT => [
                Constants::GATEWAY => $cardMandate->getMandateHub(),
                Constants::ID => null,
            ],
            Constants::TOKEN => $token,
            Constants::TERMINAL => $cardMandate->terminal ? $cardMandate->terminal : null,
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::IS_OPTIMIZER_CARD_MANDATE => true
        ];
    }

    protected function buildMandateVerifyPayload(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        return [
            Constants::GATEWAY => $cardMandate->getMandateHub(),
            Constants::PAYMENT => [
                Constants::GATEWAY => $payment->getGateway(),
                Constants::ID => $payment->getId(),
            ],
            Constants::TERMINAL => $payment->terminal ? $payment->terminal : null,
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::IS_OPTIMIZER_CARD_MANDATE => true
        ];
    }

    protected function buildCreatePreDebitNotificationPayload(Payment\Entity $payment, CardMandate\Entity $cardMandate, $input)
    {
        $debitTimeCarbon = Carbon::createFromTimestamp($input[Constants::DEBIT_AT] ?? Carbon::now()->addDay()->timestamp,
            Timezone::IST);
        return [
            Constants::PAYMENT => $payment->toArray(),
            Constants::TERMINAL => $payment->terminal ? $payment->terminal : null,
            Constants::GATEWAY => $cardMandate->getMandateHub(),
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::DEBIT_AT => $debitTimeCarbon->format('Y-m-d'),
            Constants::IS_OPTIMIZER_CARD_MANDATE => true,
            Constants::NOTIFY_ACTION => Constants::NOTIFY_INIT,
        ];
    }


    protected function buildPreDebitVerifyPayload(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        return [
            Constants::PAYMENT => $payment->toArray(),
            Constants::TERMINAL => $payment->terminal ? $payment->terminal : null,
            Constants::GATEWAY => $cardMandate->getMandateHub(),
            Constants::CARD_MANDATE => $cardMandate->toArray(),
            Constants::IS_OPTIMIZER_CARD_MANDATE => true,
            Constants::NOTIFY_ACTION => Constants::NOTIFY_VERIFY,
        ];
    }

    protected function buildCreatePreDebitNotificationResponse($notificationId): Notification
    {
        // NOT handling afa status here. Customer gets afa notification. And is supposed to approve the request.
        // During subsequent payment validation we will check if afa was approved or not.
        $notificationAttributes = [
            Notification::NOTIFICATION_ID => $notificationId,
            Notification::NOTIFIED_AT => Carbon::now()->getTimestamp(),
            Notification::STATUS => CardMandate\MandateHubs\NotificationStatus::NOTIFIED,
        ];

        return (new Notification($notificationAttributes));
    }

}
