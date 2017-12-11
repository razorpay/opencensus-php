<?php

namespace RZP\Gateway\Fss;

use RZP\Constants\Entity as E;
use RZP\Error\Error;
use RZP\Error\ErrorCode;
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

            Fields::ACTION        => Constants::ACTION_PURCHASE,

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
     * @param array $attributes
     * @param array $input
     *
     * @return Entity
     */
    protected function createGatewayPaymentEntity(array $attributes, array $input)
    {
        $gatewayPaymentEntity = $this->getNewGatewayPaymentEntity();

        $gatewayPaymentEntity->setPaymentId($input['payment']['id']);

        if (empty($input['refund']['id']) === false)
        {
            $gatewayPaymentEntity->setRefundId($input['refund']['id']);
        }

        $gatewayPaymentEntity->setAction($this->action);

        $gatewayPaymentEntity->fill($attributes);

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

        if (empty($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]) === false)
        {
            $gatewayPayment->setGatewayPaymentId($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]);
        }

        try
        {
            $this->checkErrorMessage($input['gateway'], $gatewayPayment);

            $gatewayContent = $this->getDecryptedRequestContent($gatewayResponse['trandata']);

            $attributes = $this->getCallbackFields($gatewayContent);

            $gatewayPayment->fill($attributes);

            $expectedAmount = $this->getFormattedAmount($input['payment']['amount'] / 100);
            $actualAmount = $this->getFormattedAmount($gatewayContent[Fields::AMOUNT]);

            $this->assertAmount($expectedAmount, $actualAmount);

            $this->checkCapturedStatus($gatewayPayment, ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
        finally
        {
            $this->repo->saveOrFail($gatewayPayment);
        }

        $response = $this->getCallbackResponseData($input);

        return $response;
    }

    /**
     * Filters out the relevant mappings and gets the data.
     * @param array $gatewayContent
     *
     * @return array
     */
    public function getCallbackFields(array $gatewayContent)
    {
        $attributes = [
            Entity::RECEIVED => true,
        ];

        $mandatoryFields = [
            Entity::GATEWAY_PAYMENT_ID,
            Entity::GATEWAY_TRANSACTION_ID,
            Entity::STATUS,
        ];

        // Razorpay vs FSS Field mapping
        $callbackFieldMapping = [
            Entity::GATEWAY_PAYMENT_ID     => Fields::GATEWAY_CALLBACK_PAYMENT_ID,
            Entity::GATEWAY_TRANSACTION_ID => Entity::GATEWAY_TRANSACTION_ID,
            Entity::REF                    => Entity::REF,
            Entity::AUTH                   => Entity::AUTH,
            Entity::POST_DATE              => Entity::POST_DATE,
            Entity::STATUS                 => Fields::RESULT,
            Entity::AUTH_RES_CODE          => Fields::AUTH_RES_CODE,
        ];

        $missingCallbackFields = [];

        foreach ($callbackFieldMapping as $key => $value)
        {
            // Checking with empty "null" because we use simple_xml to deserialize the data
            // so null is converted to string.
            if (empty($gatewayContent[$value]) === false and
                $gatewayContent[$value] != "null")
            {
                $attributes[$key] = $gatewayContent[$value];
            }
            else
            {
                $missingCallbackFields[] = $key;
            }
        }

        // Calculate missing fields.
        $missingFields = array_intersect($mandatoryFields, $missingCallbackFields);

        if (count($missingFields) > 0)
        {
            $this->trace->error(
                TraceCode::GATEWAY_PAYMENT_MISSING_FIELD,
                [
                    'payment_id' => $this->input['payment']['id'],
                    'fields'     => $missingFields,
                    'message'    => "Mandatory Fields are missing",
                    'gateway'    => $this->gateway,
                ]
            );
        }

        return $attributes;
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $refundRequestContentArray = $this->getRefundRequestContentArray($input);

        $refundRequestContent = $this->getRefundRequestContent($refundRequestContentArray);

        $request = parent::getStandardRequestArray($refundRequestContent, 'post', Action::REFUND);

        $response = $this->postRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'refund_id' => $input['refund']['id'],
                'response'  => $response->body,
            ]
        );

        $attributes = $this->getRefundFields($response, $input);

        $errorStatus = $attributes[Entity::STATUS];

        $this->parseResponseStatus($attributes);

        $gatewayEntity = $this->createGatewayPaymentEntity($attributes, $input);

        if ($attributes[Entity::STATUS] === Constants::NOT_CAPTURED)
        {
            try
            {
                $refundContent[Constants::ERROR_TEXT] = $errorStatus;

                $this->checkErrorMessage($refundContent, $gatewayEntity);
            }
            finally
            {
                $this->repo->saveOrfail($gatewayEntity);
            }
        }

        $this->checkCapturedStatus($gatewayEntity, ErrorCode::BAD_REQUEST_REFUND_FAILED);
    }

    public function getRefundFields($response, $input)
    {
        $responseBody = $response->body;

        //we wrap around response to use simplexml.
        $refundResponse = "<response>" . trim($responseBody) . "</response>";

        $refundResponse = (array) simplexml_load_string($refundResponse);

        $refundFields = $this->getCallbackFields($refundResponse);

        $refundFields[Entity::AMOUNT] = $input[E::REFUND][Entity::AMOUNT];

        $refundFields[Entity::CURRENCY] = Constants::CURRENCY_CODE;

        return $refundFields;
    }

    /**
     * FSS sends error messages in status, so parsing the same for storing.
     * @param array $attributes
     */
    public function parseResponseStatus(array & $attributes)
    {
        $status = $attributes[Entity::STATUS];

        if (empty($status) === false and
            trim($status) !== Constants::CAPTURED)
        {
            $attributes[Entity::STATUS] = Constants::NOT_CAPTURED;
        }
    }

    public function getRefundRequestContent($requestContent)
    {
        // Entire request content is wrapped in xml.
        $requestBuffer = Utility::createRequestXml($requestContent);

        return $requestBuffer;
    }

    public function postRequest($request)
    {
        $request['options'] = $this->getRequestOptions();

        $request['headers'] = $this->getRequestHeaders();

        $response = $this->sendGatewayRequest($request);

        return $response;
    }

    protected function getRequestHeaders()
    {
        $headers = [
            'Content-Type:application/xml',
            'Cache-Control: no-cache',
        ];

        return $headers;
    }

    protected function getRequestOptions()
    {
        $options['verify'] = false;
        $options['timeout'] = 60;

        return $options;
    }

    public function getRefundRequestContentArray($input)
    {
        $requestContent = [
            Fields::CURRENCY_CODE  => Constants::CURRENCY_CODE,
            Fields::TYPE           => $this->getFormattedCardType($input[E::CARD][Card\Entity::TYPE]),
            Fields::TRANSACTION_ID => $input['payment']['id'],
            Fields::AMOUNT         => $input[E::REFUND][Entity::AMOUNT] / 100,

            Fields::ACTION         => Constants::ACTION_REFUND,

            Fields::TRACK_ID       => $input[E::REFUND][Entity::ID],
            Fields::UDF5           => Constants::TRACK_ID,
            Fields::LANGUAGE_ID    => Constants::LANGUAGE,

            Fields::ID             => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Fields::PASSWORD       => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD],
        ];

        return $requestContent;
    }

    /**
     * @param array  $input
     * @param Entity $gatewayPayment
     *
     * @throws Exception\GatewayErrorException
     */
    public function checkErrorMessage(array $input, Entity $gatewayPayment)
    {
        if (empty($input[Constants::ERROR_TEXT]) === false)
        {
            $gatewayCode = $this->getErrorCode($input[Constants::ERROR_TEXT]);

            $errorDesc = ErrorCodes::getErrorDesc($gatewayCode);

            $errorCode = ErrorCodes::getMappedCode($gatewayCode);

            $gatewayPayment->setErrorMessage($errorDesc);

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

    private function getFormattedAmount($amount)
    {
        return number_format($amount, 2,'.', '');
    }

    /**
     * Fss sends captured/success if it's a successful transaction else it will send error messages.
     *
     * @param Entity $gateway
     * @param String $errorCode
     *
     * @throws Exception\GatewayErrorException
     */
    private function checkCapturedStatus(Entity $gateway, $errorCode)
    {
        $status = $gateway->getStatus();

        if ($status !== Constants::CAPTURED)
        {
            throw new Exception\GatewayErrorException($errorCode);
        }

    }
}