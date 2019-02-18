<?php

namespace RZP\Models\Gateway\Settlement;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Constants\Timezone;

class Service extends Base\Service
{
    const FROM = 'from';
    const TO   = 'to';

    protected $processors = [];

    public function processSettlements(string $gateway, array $input)
    {
        (new Validator)->validateInput('settlement', $input);

        $this->setInput($input);

        $payments = $this->getProcessor($gateway)->getPayments($input);

        foreach ($payments as $payment)
        {
            $this->getProcessor($gateway)->process($payment);
        }
    }

    protected function getProcessor($gateway)
    {
        $driver = $this->getProcessorDriver($gateway);

        if (isset($this->processors[$driver]) === true)
        {
            return $this->processors[$driver];
        }

        $processor = new $driver;

        $this->processors[$driver] = $processor;

        return $this->processors[$driver];
    }

    protected function getProcessorDriver($gateway)
    {
        $baseNamespace = 'RZP\\Models\\Gateway\\Settlement\\Processor\\';

        return $baseNamespace . studly_case($gateway);
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
