<?php

namespace RZP\Gateway\Fss;

use RZP\Constants\Entity as E;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Base;

class Gateway extends Base\Gateway
{
    protected $gateway = E::FSS;

    public function authorize(array $input)
    {
        parent::authorize($input);
        sd($input);

        $contentArray = $this->getAuthRequestContentArray($input);
    }

    /**
     * Gets all the required fields for making auth request.
     *
     * @param array $input
     */
    private function getAuthRequestContentArray(array $input)
    {
        $content = [
            Fields::CARD          => $input[E::CARD][Card\Entity::NUMBER],
            Fields::MEMBER        => $input[E::CARD][Card\Entity::NAME],
            Fields::EXPIRY_MONTH  => $input[E::CARD][Card\Entity::EXPIRY_MONTH],
            Fields::EXPIRY_YEAR   => $input[E::CARD][Card\Entity::EXPIRY_YEAR],
            Fields::CVV           => $input[E::CARD][Card\Entity::CVV],
//            Fields::TYPE          => $input[E::CARD][Card\Entity::TYPE], // Not mandatory.

            Fields::AMOUNT        => $input[E::PAYMENT][Payment\Entity::AMOUNT],
            Fields::CURRENCY_CODE => Constants::CURRENCY_CODE,
            Fields::ACTION        => Action::PURCHASE,

            Fields::TRACK_ID      => $input[E::PAYMENT][Payment\Entity::ID],
            Fields::UDF5          => Constants::PAYMENT_ID,

            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],

            Fields::ID            => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Fields::PASSWORD      => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD],
        ];

        $requestBuffer = Utility::createRequestXml($content);
    }
}