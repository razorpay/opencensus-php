<?php

namespace RZP\Models\CardMandate\MandateHubs\RupaySIHub;

use Carbon\Carbon;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\CardMandate;
use RZP\Models\Plan\Subscription;
use RZP\Models\CardMandate\MandateHubs\BaseHub;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\CardMandate\MandateHubs\NotificationStatus;

class RupaySIHub extends BaseHub
{
    private $gateway = MandateHubs::RUPAY_SIHUB;

    public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        $card = $payment->card->toArray();
        $token = $payment->localToken;

        $mandateAttributes = [
            Mandate::MANDATE_CARD_ID            => $card[Card\Entity::ID] ?? null,
            Mandate::MANDATE_CARD_NAME          => $card[Card\Entity::NAME] ?? null,
            Mandate::MANDATE_CARD_LAST4         => $card[Card\Entity::LAST4] ?? null,
            Mandate::MANDATE_CARD_NETWORK       => MandateHubs::RUPAY_SIHUB,
            Mandate::MANDATE_CARD_TYPE          => $card[Card\Entity::TYPE] ?? null,
            Mandate::MANDATE_CARD_ISSUER        => $card[Card\Entity::ISSUER] ?? null,
            Mandate::MANDATE_CARD_INTERNATIONAL => $card[Card\Entity::INTERNATIONAL] ?? null,
            Mandate::DEBIT_TYPE                 => Constants::DEBIT_TYPE_VARIABLE_AMOUNT,
            Mandate::CURRENCY                   => $payment->getCurrency() ?? null,
            Mandate::MAX_AMOUNT                 => $token->getMaxAmount() ?? Constants::MAX_AMOUNT_DEFAULT,
            Mandate::AMOUNT                     => $payment->getAmount() ?? null,
            Mandate::START_AT                   => $token->getStartTime() ?? Carbon::now()->getTimestamp(),
            Mandate::END_AT                     => $token->getExpiredAt() ?? $token->card->getExpiryTimestamp(),
            Mandate::TOTAL_CYCLES               => 0,
            Mandate::FREQUENCY                  => Constants::FREQUENCY_AS_PRESENTED,
        ];
        if ($payment->getSubscriptionId() !== null)
        {
            $subscription = $this->app['module']
                                 ->subscription
                                 ->fetchSubscriptionInfo(
                                    [
                                        Payment\Entity::AMOUNT          => $payment->getAmount(),
                                        Payment\Entity::SUBSCRIPTION_ID => Subscription\Entity::getSignedId($payment->getSubscriptionId()),
                                        Payment\Entity::METHOD          => $payment->getMethod(),
                                    ],
                                    $payment->merchant,
                                    $callback = true);

            $mandateAttributes[Mandate::START_AT] = $subscription->getStartAt() ?? Carbon::now()->addDay()->getTimestamp();
            $mandateAttributes[Mandate::END_AT] = $subscription->getEndAt() ?? $token->card->getExpiryTimestamp();
            $mandateAttributes[Mandate::TOTAL_CYCLES] = $subscription->getTotalCount() ?? null;
        }
        return (new Mandate(MandateHubs::RUPAY_SIHUB, $mandateAttributes));
    }

    public function CancelMandate(CardMandate\Entity $cardMandate): Mandate
    {
        // TODO: Implement CancelMandate() method.
    }

    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        if ($payment->isFailed() === true)
        {
            return;
        }
        $authorizationData = (new Payment\Service)->getAuthorizationEntity($payment->getPublicId());

        $notes = $authorizationData['notes'];
        $data = json_decode($notes, true);
        $cardMandate->setMandateId($data['si_registration_id']);
    }

    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        return null;
    }

    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification
    {
        $preDebitNotificationInput = $this->getCreatePreDebitNotificationInput($payment, $cardMandate, $input);

        $response = $this->app['gateway']->call($this->gateway,
                                                Payment\Action::CARD_MANDATE_PRE_DEBIT_NOTIFY,
                                                $preDebitNotificationInput, $this->mode);

        return $this->getNotificationFromRupayResponse($response['formatted'], $input);
    }

    protected function getCreatePreDebitNotificationInput(Payment\Entity $payment, CardMandate\Entity $cardMandate, $input)
    {
        $cardData = $payment->card->toArray();

        $token = $payment->localToken;

        if ($token->card->isRuPay() === true && $token->card->isRzpSavedCard() === false)
        {
            $cryptogram = (new Card\CardVault)->fetchCryptogramForPayment($token->card->getVaultToken(), $this->merchant);

            $cardData[Constants::CARD_NO] = $cryptogram['token_number'] ?? $cryptogram['card']['number'];
            $cardData[Constants::CRYPTOGRAM_VALUE] = $cryptogram['cryptogram_value'] ?? null;
        }

        $cardMandateData             = $cardMandate->toArray();
        $cardMandateData['debit_at'] = $input['debit_at'];

        return [
            Constants::PAYMENT      => $payment->toArray(),
            Constants::TERMINAL     => $payment->terminal ? $payment->terminal->toArray() : null,
            Constants::GATEWAY      => Payment\Gateway::PAYSECURE,
            Constants::CARD         => $cardData,
            Constants::CARD_MANDATE => $cardMandateData,
        ];
    }

    protected function getNotificationFromRupayResponse($response, $input)
    {
        $notificationAttributes = [
            Notification::STATUS           => $response['status'],
            Notification::NOTIFICATION_ID  => $response['id'] ?? '',
            Notification::NOTIFIED_AT      => Carbon::now()->getTimestamp(),
            Notification::AFA_STATUS       => $response['afa_status'] ?? '',
            Notification::AMOUNT           => $input['amount'] ?? 0,
        ];

        return (new Notification($notificationAttributes));

    }

    protected function getCreateCancelMandateInput(CardMandate\Entity $cardMandate)
    {
        return [
            Constants::GATEWAY      => $this->gateway,
            Constants::TERMINAL     => $cardMandate->terminal ? $cardMandate->terminal->toArray() : null,
            Constants::CARD_MANDATE => $cardMandate->toArray(),
        ];
    }

    public function validatePayment($mandateId, $input)
    {
        return null;
    }

    public function getRedirectResponseIfApplicable(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {
        return null;
    }

    public function getValidationBeforeSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = [], $forceCard = true)
    {
        // TODO: Implement getValidationBeforeSubsequentPayment() method.
    }

    public function updateTokenisedCardTokenInMandate(CardMandate\Entity $cardMandate, $input)
    {
        // TODO: Implement updateTokenisedCardTokenInMandate() method.
    }
}
