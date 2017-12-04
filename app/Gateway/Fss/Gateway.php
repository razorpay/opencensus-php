<?php

namespace RZP\Gateway\Fss;

use RZP\Constants\Entity as E;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Base;
use phpseclib\Crypt\TripleDES;

class Gateway extends Base\Gateway
{
    protected $gateway = E::FSS;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $contentArray = $this->getAuthRequestContent($input);

        $request = $this->getStandardRequestArray($contentArray, 'get', 'PURCHASE');

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray([], $method, $type);

        $request['url'] .= http_build_query($content);

        return $request;
    }

    /**
     * Gets all the required fields for making auth request.
     *
     * @param array $input
     *
     * @return array
     */
    private function getAuthRequestContent(array $input)
    {
        $requestContent = [
            Fields::CARD          => $input[E::CARD][Card\Entity::NUMBER],
            Fields::MEMBER        => $input[E::CARD][Card\Entity::NAME],
            Fields::EXPIRY_MONTH  => $input[E::CARD][Card\Entity::EXPIRY_MONTH],
            Fields::EXPIRY_YEAR   => $input[E::CARD][Card\Entity::EXPIRY_YEAR],
            Fields::CVV           => $input[E::CARD][Card\Entity::CVV],
            // Fields::TYPE          => $input[E::CARD][Card\Entity::TYPE], // Not mandatory.

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

        // Entire request content is wrapped in xml.
        $requestBuffer = Utility::createRequestXml($requestContent);

        // Encrypted request content
        $tranData = $this->getEncryptedRequestContent($requestBuffer);

        $content = [
            Fields::TRAN_DATA     => $tranData,
            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],
            Fields::TRANPORTAL_ID => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
        ];

        return $content;
    }

    protected function getEncryptedRequestContent($str)
    {
        $secretKey = $this->getSecret();

        $crypto = new TripleDESCrypto(TripleDES::MODE_ECB, $secretKey);

        return $crypto->encryptString($str);
    }

    protected function getDecryptedRequestContent($str)
    {
        $secretKey = $this->getSecret();

        $crypto = new TripleDESCrypto(TripleDES::MODE_ECB, $secretKey);

        return $crypto->decryptString($str);
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->checkErrorMessage($input['gateway']);

        $trandata = $input['gateway']['trandata'];

        $gateway = $this->getDecryptedRequestContent($trandata);
    }

    public function checkErrorMessage($input)
    {
        if (empty($input[Constants::ERROR_TEXT]) === false)
        {
            $errorCode = $this->getErrorCode($input[Constants::ERROR_TEXT]);
        }
    }

    public function getErrorCode($errorText)
    {
        return trim(current(explode('-', $errorText)));
    }
}