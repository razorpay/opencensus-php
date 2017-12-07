<?php

namespace RZP\Gateway\Fss;

use RZP\Constants\Entity as E;
use RZP\Models\Card;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Base;
use phpseclib\Crypt\TripleDES;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;

class Gateway extends Base\Gateway
{
    protected $gateway = E::FSS;

    public function authorize(array $input)
    {
        parent::action($input, Action::PURCHASE);

        $purchaseRequestFields = $this->getPurchaseRequestContentArray($input);

        $purchaseRequestContent = $this->getPurchaseRequestContent($purchaseRequestFields);

        $request = $this->getStandardRequestArray($purchaseRequestContent, 'get', Constants::PURCHASE);

        $purchaseFields = $this->getPurchaseFields($purchaseRequestFields);

        $this->createGatewayPaymentEntity($purchaseFields, $input);

        $this->traceGatewayPaymentRequest($request, $input);

        return $request;
    }

    /**
     * @param array       $content
     * @param string      $method
     * @param string|null $type
     *
     * @return array
     */
    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        $request = parent::getStandardRequestArray([], $method, $type);

        $request['url'] .= http_build_query($content);

        return $request;
    }

    /**
     * Frames fields to create gateway entity.
     * @param array $requestFields
     *
     * @return array
     */
    private function getPurchaseFields(array $requestFields)
    {
        $attributes = [
            Entity::AMOUNT      => $requestFields[Fields::AMOUNT] * 100,
            Entity::CURRENCY    => $requestFields[Fields::CURRENCY_CODE],
        ];

        return $attributes;
    }

    /**
     * Gets all the required fields for making purchase request.
     *
     * @param array $input
     *
     * @return array
     */
    private function getPurchaseRequestContentArray(array $input)
    {
        $requestContent = [
            Fields::CARD          => $input[E::CARD][Card\Entity::NUMBER],
            Fields::CVV           => $input[E::CARD][Card\Entity::CVV],
            Fields::CURRENCY_CODE => Constants::CURRENCY_CODE,
            Fields::EXPIRY_YEAR   => $input[E::CARD][Card\Entity::EXPIRY_YEAR],

            Fields::EXPIRY_MONTH  => $this->getFormattedExpMonth($input[E::CARD][Card\Entity::EXPIRY_MONTH]),
            Fields::TYPE          => $this->getFormattedCardType($input[E::CARD][Card\Entity::TYPE]),

            Fields::MEMBER        => $input[E::CARD][Card\Entity::NAME],
            Fields::AMOUNT        => $input[E::PAYMENT][Payment\Entity::AMOUNT] / 100, //use number_format

            Fields::ACTION        => Action::PURCHASE,

            Fields::TRACK_ID      => $input[E::PAYMENT][Payment\Entity::ID],
            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],
            Fields::ID            => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Fields::PASSWORD      => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD],
        ];

        return $requestContent;
    }

    /**
     * @param array $requestContent
     *
     * @return array
     */
    protected function getPurchaseRequestContent(array $requestContent)
    {
        // Entire request content is wrapped in xml.
        $requestBuffer = Utility::createRequestXml($requestContent);

        // Encrypted request content
        $tranData = $this->getEncryptedRequestContent($requestBuffer);

        $content = [
            Fields::TRAN_DATA     => $tranData,
            Fields::ERROR_URL     => $this->input['callbackUrl'],
            Fields::RESPONSE_URL  => $this->input['callbackUrl'],
            Fields::TRANPORTAL_ID => $this->input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
        ];

        return $content;
    }

    /**
     * Creates a gateway payment entry.
     * @param array $purchaseFields
     * @param array $input
     *
     * @return array
     */
    protected function createGatewayPaymentEntity(array $purchaseFields, array $input)
    {
        $gatewayPaymentEntity = $this->getNewGatewayPaymentEntity();

        $gatewayPaymentEntity->setPaymentId($input['payment']['id']);

        $gatewayPaymentEntity->setAction($this->action);

        $gatewayPaymentEntity->fill($purchaseFields);

        $this->repo->saveOrFail($gatewayPaymentEntity);

        return $gatewayPaymentEntity;
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

        $decryptedString = $crypto->decryptString($str);

        // By default decrypted comes with only fields instead of nested, to let simple xml understand the data.
        //we wrap around response.
        $decryptedString = "<response>" . $decryptedString . "</response>";

        $decryptedResult = (array) simplexml_load_string($decryptedString);

        return $decryptedResult;
    }

    /**
     * callback function for all the purchase requests.
     * @param array $input
     *
     * @return array
     */
    public function callback(array $input)
    {
        parent::callback($input);
        // Trace payment callback
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway' => $input['gateway']
            ]
        );

        $gatewayResponse = $input['gateway'];

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'],
            Action::PURCHASE);

        if (isset($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]) === true)
        {
            $gatewayPayment->setGatewayPaymentId($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]);
        }

        try
        {
            $this->checkErrorMessage($input['gateway']);

            $trandata = $input['gateway']['trandata'];

            $gateway = $this->getDecryptedRequestContent($trandata);

        }
        catch (\Exception $e)
        {

        }
        finally
        {
            $this->repo->saveOrFail($gatewayPayment);
        }

        return $gatewayPayment;
    }

    public function checkErrorMessage($input)
    {
        if (empty($input[Constants::ERROR_TEXT]) === false)
        {
            $gatewayCode = $this->getErrorCode($input[Constants::ERROR_TEXT]);

            $errorDesc = ErrorCodes::getErrorDesc($gatewayCode);

            $errorCode = ErrorCodes::getMappedCode($gatewayCode);

            throw new Exception\GatewayErrorException($errorCode, $gatewayCode, $errorDesc, $input);
        }
    }

    private function getErrorCode($errorText)
    {
        return trim(current(explode('-', $errorText)));
    }

    private function getFormattedExpMonth($expMonth)
    {
        return str_pad($expMonth, 2, '0', STR_PAD_LEFT);
    }

    /**
     * We return credit card as default type. if debit is not present.
     * because we set card type as credit when it's unknown in card entity.
     * @param $cardType
     *
     * @return string
     */
    private function getFormattedCardType($cardType)
    {
        if ($cardType === Card\Type::DEBIT)
        {
            return Constants::DEBIT_CARD_TYPE;
        }

        return Constants::CREDIT_CARD_TYPE;
    }
}