<?php


namespace RZP\Models\CardMandate\MandateHubs\PayuHub;


use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\CardMandate;
use RZP\Models\CardMandate\MandateHubs\Mandate;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Models\CardMandate\MandateHubs\Notification;
use RZP\Models\Payment;
use RZP\Services\CardPaymentService;
use RZP\Trace\TraceCode;

class PayuHub extends \RZP\Models\CardMandate\MandateHubs\BaseHub
{

    public function RegisterMandate(CardMandate\Entity $cardMandate, Payment\Entity $payment, $input = []): Mandate
    {
        // No API call to Payu. Store mandate entity with empty mandate ID

        $token = $payment->localToken;

        $card = $payment->card;

        $maxAmount = $token->getMaxAmount();

        if ($maxAmount === null)
        {
            $maxAmount = Constants::MAX_AMOUNT_DEFAULT;
        }

        $startTime = $token->getStartTime();
        if ($startTime === null)
        {
            $startTime = Carbon::now()->addDay()->getTimestamp();
        }

        $endTime = $token->getExpiredAt();
        if ($endTime === null)
        {
            $endTime = $token->card->getExpiryTimestamp();
        }

        $frequency = Constants::FREQUENCY_AS_PRESENTED;
        if (empty($input['frequency']) === false)
        {
            $frequency = $input['frequency'];
        }

        $debitType = Constants::DEBIT_TYPE_VARIABLE_AMOUNT;
        if (empty($input['debit_type']) === false)
        {
            $debitType = $input['debit_type'];
        }

        $mandateAttributes = [
            Mandate::MANDATE_ID                 => null,
            Mandate::MANDATE_CARD_ID            => $card ? $card[Constants::CARD_ID] : null,
            Mandate::MANDATE_CARD_NAME          => $card ? $card[Constants::CARD_NAME] : null,
            Mandate::MANDATE_CARD_LAST4         => $card ? $card[Constants::CARD_LAST4] : null,
            Mandate::MANDATE_CARD_NETWORK       => $card ? $card[Constants::CARD_NETWORK] : null,
            Mandate::MANDATE_CARD_TYPE          => $card ? $card[Constants::CARD_TYPE] : null,
            Mandate::MANDATE_CARD_ISSUER        => $card ? $card[Constants::CARD_ISSUER] : null,
            Mandate::MANDATE_CARD_INTERNATIONAL => $card ? $card[Constants::CARD_INTERNATIONAL] : null,
            Mandate::DEBIT_TYPE                 => $debitType,
            Mandate::CURRENCY                   => $payment->getCurrency() ?? null,
            Mandate::MAX_AMOUNT                 => $maxAmount,
            Mandate::AMOUNT                     => $payment->getAmount() ?? null,
            Mandate::START_AT                   => $startTime,
            Mandate::END_AT                     => $endTime,
//            Mandate::TOTAL_CYCLES               => $response[Constants::TOTAL_CYCLES] ?? null,
//            Mandate::MANDATE_INTERVAL           => $response[Constants::INTERVAL] ?? null,
            Mandate::FREQUENCY                  => $frequency,
        ];

        return (new Mandate(MandateHubs::PAYU_HUB, $mandateAttributes));
    }

    public function CancelMandate(CardMandate\Entity $cardMandate): ?Mandate
    {
        // TODO: Implement CancelMandate() method.
    }

    public function ReportInitialPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
    {

        if ($payment->isFailed() === false)
        {

            $paymentId = $payment->getId();

            $request = [
                'fields'        => ['gateway_reference_id1'],
                'payment_ids'   => [$paymentId],
            ];

            $authorizationData = (new CardPaymentService())->fetchAuthorizationData($request);

            $cardMandate->setMandateId($authorizationData[$paymentId]['gateway_reference_id1']);

            $cardMandate->saveOrFail();

        }


        $token = $payment->localToken;

        $input =[];

        if ($token->card->isRzpSavedCard() == false)
        {
            try {
                $tokenInput = $token->card->buildTokenisedTokenForMandateHub();
                $networkToken = $tokenInput['token'];
                $input[Constants::TOKEN] = $networkToken;

            } catch (Exception $e) {

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKEN_CRYPTOGRAM_EXCEPTION);
            }
        }
        (new CardMandate\Core())->storeVaultTokenPan($cardMandate, $input);
    }

    public function reportSubsequentPayment(CardMandate\Entity $cardMandate, Payment\Entity $payment)
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

    public function CreatePreDebitNotification(CardMandate\Entity $cardMandate, ?Payment\Entity $payment, $input): Notification
    {
        // TODO: Implement CreatePreDebitNotification() method.
    }

    public function updateTokenisedCardTokenInMandate(CardMandate\Entity $cardMandate, $input)
    {
        // TODO: Implement updateTokenisedCardTokenInMandate() method.

    }
}
