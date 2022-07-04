<?php

namespace RZP\Models\Reminders;

use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\CardMandate\CardMandateNotification;

class CardAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $payment = (new Payment\Core)->retrievePaymentById($id);

        $processor = (new Payment\Processor\Processor($payment->merchant));

        $processor->setPayment($payment);

        $verified = false;

        try
        {
            (new CardMandateNotification\Core)->verifyNotification($payment);

            $verified = true;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e,
                null,
                TraceCode::CARD_MANDATE_NOTIFICATION_PAYMENT_VERIFY_FAILED,
                ["payment_id" => $id]);

            $processor->failNotificationVerifyFailedCardAutoRecurringPayment($payment);
        }

        if ($verified === false)
        {
            return [];
        }

        $gatewayInput = $this->getGatewayInputForPayment($payment, $processor);

        $processor->gatewayRelatedProcessing($payment, [], $gatewayInput);

        return [];
    }

    public function getGatewayInputForPayment(Payment\Entity $payment, Payment\Processor\Processor $processor)
    {
        $token = $payment->localToken;

        $card = $this->repo->card->fetchForToken($token);

        $iin = $this->app['repo']->iin->find($card['iin']);

        if (($card->isRzpSavedCard() === false) and
            ($this->isExperimentEnabledForTokenisedCard($token->getMerchantId()) === true) and
            ($this->shouldRecurringAutoPaymentGoThroughTokenisedCard($card) === true))
        {
            $cardInput = $processor->createCardForNetworkTokenCardMandate($card, []);
        }
        else
        {
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

    protected function isExperimentEnabledForTokenisedCard($merchantId): bool
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

    protected function shouldRecurringAutoPaymentGoThroughTokenisedCard(Card\Entity $card): bool
    {
        try
        {
            $experimentKey = implode('_', [
                $card->getNetworkCode(),
                $card->getIssuer()
            ]);

            $variant = $this->app['razorx']->getTreatment(
                $experimentKey,
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
}
