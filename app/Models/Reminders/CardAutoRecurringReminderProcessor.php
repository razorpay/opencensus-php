<?php

namespace RZP\Models\Reminders;

use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\CardMandate\CardMandateNotification;

class CardAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $payment = (new Payment\Core)->retrievePaymentById($id);

        $notification = (new CardMandateNotification\Core)->verifyNotification($payment);

        $processor = (new Payment\Processor\Processor($payment->merchant));
        $processor->setPayment($payment);

        $gatewayInput = $this->getGatewayInputForPayment($payment, $processor);

        if ((!$notification->isAfaRequired() and $notification->getStatus() !== CardMandateNotification\Status::NOTIFIED) ||
            ($notification->isAfaRequired() and $notification->getAfaStatus() !== CardMandateNotification\AfaStatus::APPROVED) ||
            $notification->cardMandate->isActive() === false)
        {
            $processor->failNotificationVerifyFailedCardAutoRecurringPayment($payment);
        }
        else
        {
            $processor->gatewayRelatedProcessing($payment, [], $gatewayInput);
        }

        return [];
    }

    public function getGatewayInputForPayment(Payment\Entity $payment, Payment\Processor\Processor $processor)
    {
        $token = $payment->localToken;

        $card = $this->repo->card->fetchForToken($token);

        $iin = $this->app['repo']->iin->find($card['iin']);

        if ($card->isRzpSavedCard() === false)
        {
            $cardInput = $processor->createCardForNetworkTokenCardMandate($card, []);
        }
        else
        {
            $cardNumber = (new Card\CardVault)->getCardNumber($card->getVaultToken());

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
}
