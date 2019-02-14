<?php

namespace RZP\Models\Gateway\Settlement;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment\Action;
use RZP\Models\Payment\Gateway;
use RZP\Constants\Timezone;

class Service extends Base\Service
{
    const FROM = 'from';
    const TO   = 'to';

    public function processPaysecureSettlements(array $input)
    {
        (new Validator)->validateInput('paysecure_settlement', $input);

        $this->setInput($input);

        $payments = $this->repo->payment->fetchPaysecureAuthorizedAndCapturedPaymentsBetweenTimestampsToSettle(
            $input[self::FROM],
            $input[self::TO]
        );

        foreach ($payments as $payment)
        {
            $gatewayInput = [
                'payment'  => $payment->toArray(),
                'merchant' => $payment->merchant,
                'card'     => $this->getCardDetails($payment),
            ];
            sd($gatewayInput);

            $this->app['gateway']->call(
                Gateway::HITACHI,
                Action::AUTHORIZE,
                $gatewayInput,
                $this->mode
            );
        }
    }

    protected function getCardDetails($payment)
    {
        $card = $payment->card;

        if ($card->globalCard !== null)
        {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        if (empty($cardToken) === true)
        {
            $this->trace->critical(
                TraceCode::PAYMENT_CARD_NOT_SAVED,
                [
                    'payment_id' => $payment->id,
                ]
            );
        }

        $cardNumber = (new Card\Tokenex)->getCardNumber($cardToken);

        $response = $card->toArray();

        $response['number'] = $cardNumber;

        return $response;
    }

    protected function setInput(array &$input)
    {
        if ((isset($input[self::FROM]) === false) or
            (isset($input[self::TO]) === false))
        {
            $input[self::FROM] = Carbon::now(Timezone::IST)->subHour()->getTimestamp();
            $input[self::TO] = Carbon::now(Timezone::IST)->getTimestamp();
        }
    }
}
