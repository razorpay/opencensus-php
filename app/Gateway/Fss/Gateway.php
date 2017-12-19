<?php

namespace RZP\Gateway\Fss;

use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Exception;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Gateway\Base;
use RZP\Gateway\Base\VerifyResult;
use phpseclib\Crypt\TripleDES;
use RZP\Trace\TraceCode;

class Gateway extends Base\Gateway
{
    protected $gateway = E::FSS;

    /**
     * Fss Gateway has purchase model so framing the request here
     * after persisting the gateway entity.
     *
     * @param array $input
     *
     * @return array
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        $purchaseRequestFields = $this->getPurchaseRequestContentArray($input);

        $purchaseRequestContent = $this->getPurchaseRequestContent($purchaseRequestFields, $input);

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
    protected function getPurchaseFields(array $requestFields): array
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
    protected function getPurchaseRequestContentArray(array $input)
    {
        $requestContent = [
            Fields::CARD          => $input[E::CARD][Card\Entity::NUMBER],
            Fields::CVV           => $input[E::CARD][Card\Entity::CVV],
            Fields::CURRENCY_CODE => Currency::getIsoCode(Currency::INR),
            Fields::EXPIRY_YEAR   => $input[E::CARD][Card\Entity::EXPIRY_YEAR],
            Fields::EXPIRY_MONTH  => $this->getFormattedExpMonth($input[E::CARD][Card\Entity::EXPIRY_MONTH]),
            Fields::TYPE          => $this->getCardType($input[E::CARD][Card\Entity::TYPE]),
            Fields::MEMBER        => $input[E::CARD][Card\Entity::NAME],
            Fields::AMOUNT        => $input[E::PAYMENT][Payment\Entity::AMOUNT] / 100, //use number_format
            Fields::ACTION        => Constants::ACTION_PURCHASE,
            Fields::TRACK_ID      => $input[E::PAYMENT][Payment\Entity::ID],
            Fields::ERROR_URL     => $input['callbackUrl'],
            Fields::RESPONSE_URL  => $input['callbackUrl'],
            Fields::ID            => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Fields::PASSWORD      => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD],
        ];

        // Trace the payment request after removing the sensitive fields.
        $traceData = $this->removeSensitiveRequestFields($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request_data' => $traceData,
            ]
        );

        return $requestContent;
    }

    /**
     * @param array $requestContent
     * @array array $input
     *
     * @return array
     */
    protected function getPurchaseRequestContent(array $requestContent, array $input): array
    {
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

    /**
     * Creates a gateway payment entry.
     * @param array $attributes
     * @param array $input
     *
     * @return Entity
     */
    protected function createGatewayPaymentEntity(array $attributes, array $input): Entity
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

    /**
     * Encrypts the string as per the encryption rules of the gateway provider.
     * @param string $str
     *
     * @return string
     */
    protected function getEncryptedRequestContent(string $str): string
    {
        $secretKey = $this->getSecret();

        $crypto = new TripleDESCrypto(TripleDES::MODE_ECB, $secretKey, true);

        return $crypto->encryptString($str);
    }

    /**
     * @param string $str
     *
     * @return array
     */
    protected function getDecryptedRequestContent(string $str): array
    {
        $secretKey = $this->getSecret();

        $crypto = new TripleDESCrypto(TripleDES::MODE_ECB, $secretKey, false);

        $decryptedString = $crypto->decryptString($str);

        $decryptedResult = Utility::createResponseArray($decryptedString);

        return $decryptedResult;
    }

    /**
     * callback function for all the purchase requests.
     * @param array $input
     *
     * @return array
     */
    public function callback(array $input): array
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
            Action::AUTHORIZE);

        if (empty($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]) === false)
        {
            $gatewayPayment->setGatewayPaymentId($gatewayResponse[Fields::GATEWAY_PAYMENT_ID]);

            if (empty($gatewayResponse[Fields::GATEWAY_ERROR_TEXT]) === false)
            {
                $gatewayPayment->setErrorMessage($gatewayResponse[Fields::GATEWAY_ERROR_TEXT]);
            }

             $this->repo->saveOrFail($gatewayPayment);
        }

        if (empty($gatewayResponse[Fields::TRANDATA]) === false)
        {
            $gatewayContent = $this->getDecryptedRequestContent($gatewayResponse[Fields::TRANDATA]);

            $attributes = $this->getCallbackFields($gatewayContent);

            $gatewayPayment->fill($attributes);

            $this->repo->saveOrFail($gatewayPayment);

            $this->checkErrorMessage($gatewayPayment, $gatewayContent);

            $expectedAmount = $this->getFormattedAmount($input['payment']['amount'] / 100);

            $actualAmount = $this->getFormattedAmount($gatewayContent[Fields::AMOUNT]);

            $this->assertAmount($expectedAmount, $actualAmount);

            $this->assertPaymentId($input['payment']['id'], $gatewayContent[Fields::TRACK_ID]);
        }

        $this->checkErrorMessage($gatewayPayment, $gatewayResponse);

        $this->checkCapturedStatus($gatewayPayment, ErrorCode::BAD_REQUEST_PAYMENT_FAILED);

        $response = $this->getCallbackResponseData($input);

        return $response;
    }

    /**
     * Filters out the relevant mappings and gets the data.
     * @param array $gatewayContent
     *
     * @return array
     */
    protected function getCallbackFields(array $gatewayContent): array
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
            Entity::GATEWAY_PAYMENT_ID     => Fields::PAY_ID,
            Entity::GATEWAY_TRANSACTION_ID => Fields::TRAN_ID,
            Entity::REF                    => Fields::REF,
            Entity::AUTH                   => Fields::AUTH,
            Entity::POST_DATE              => Fields::POST_DATE,
            Entity::STATUS                 => Fields::RESULT,
            Entity::AUTH_RES_CODE          => Fields::AUTH_RES_CODE,
            Entity::ERROR_MESSAGE          => Fields::ERROR_TEXT,
        ];

        $missingCallbackFields = [];

        foreach ($callbackFieldMapping as $key => $value)
        {
            // Checking with empty "null" because we use simple_xml to deserialize the data
            // so null is converted to string.
            if (empty($gatewayContent[$value]) === false and
                ($gatewayContent[$value] !== "null"))
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

        // When error message is present mandatory fields are not required.
        if (count($missingFields) > 0 and empty($attributes[Entity::ERROR_MESSAGE]) === true)
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

    /**
     * Refund Method for gateway Entity
     *
     * @param array $input
     */
    public function refund(array $input)
    {
        parent::refund($input);

        $refundRequestContentArray = $this->getGatewayRequestContentArray($input);

        $refundRequestContent = $this->getGatewayRequestContent($refundRequestContentArray);

        $request = parent::getStandardRequestArray($refundRequestContent, 'post', Action::REFUND);

        $response = $this->postRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'refund_id' => $input['refund']['id'],
                'response'  => $response->body,
            ]
        );

        $responseFields = $this->getResponseFields($response);

        $attributes = $this->getRefundFields($responseFields, $input);

        $this->parseResponseStatus($attributes);

        $gatewayEntity = $this->createGatewayPaymentEntity($attributes, $input);

        // Doing Additional check with the result because error messages are sent in result.
        if ($responseFields[Fields::RESULT] !== Status::CAPTURED and
            substr($responseFields[Fields::RESULT], 0, 4) === Constants::ERROR_MESSAGE_START)
        {
            $this->checkErrorMessage($gatewayEntity, $responseFields);
        }

        $this->checkCapturedStatus($gatewayEntity, ErrorCode::BAD_REQUEST_REFUND_FAILED);
    }

    /**
     * @param $response
     *
     * @return array
     */
    protected function getResponseFields($response)
    {
        $responseBody = $response->body;

        $responseFields = Utility::createResponseArray($responseBody);

        return $responseFields;
    }
    /**
     * Refund Fields to set the gateway entity.
     * @param $refundResponse
     * @param $input
     *
     * @return array
     */
    protected function getRefundFields($refundResponse, $input)
    {
        $refundFields = $this->getCallbackFields($refundResponse);

        $refundFields[Entity::AMOUNT] = $input[E::REFUND][Entity::AMOUNT];

        $refundFields[Entity::CURRENCY] = Currency::getIsoCode(Currency::INR);

        return $refundFields;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        $verify = new Base\Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        return $this->verifyRefundResponse($verify);
    }

    /**
     * parsing the verify refund Request Response a
     * @param Base\Verify $verify
     *
     * @return bool
     */
    protected function verifyRefundResponse(Base\Verify $verify)
    {
        $verifyResponse = $verify->verifyResponseContent;

        if (empty($verifyResponse[Fields::RESULT]) === false and
            $verifyResponse[Fields::RESULT] === Status::SUCCESS)
        {
            return true;
        }

        return false;
    }

    /**
     * Capture is empty here becuase this gateway is purchase model.
     *
     * @param array $input
     */
    public function capture(array $input)
    {
        parent::capture($input);
    }

    /**
     * @param Base\Verify $verify
     *
     * @throws Exception\GatewayErrorException
     */
    protected function verifyPayment(Base\Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $verifyResponse = $verify->verifyResponseContent;

        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        if (empty($verifyResponse[Fields::RESULT]) === true)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }

        $status = $verifyResponse[Fields::RESULT];

        $verify->gatewaySuccess = ($status === Status::SUCCESS);

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input['payment']);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $this->verifyAmountMismatch($verify, $input, $verifyResponse, E::PAYMENT);

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;
    }

    /**
     * @param Base\Verify $verify
     * @param array       $input
     * @param array       $response
     * @param string      $entity       Payment|Refund
     */
    protected function verifyAmountMismatch(Base\Verify $verify, array $input, array $response, string $entity)
    {
        $expectedAmount = $this->getFormattedAmount($input[$entity]['amount'] / 100);
        $actualAmount = $this->getFormattedAmount($response[Fields::AMOUNT]);

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    /**
     * @param Base\Entity $gatewayPayment
     * @param array       $payment
     *
     * @return bool
     */
    protected function getVerifyApiStatus(Base\Entity $gatewayPayment, array $payment)
    {
        if (($payment['status'] === 'failed') or
            ($payment['status'] === 'created'))
        {
            $apiStatus = false;

            if ($gatewayPayment['status'] === Status::CAPTURED)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }
        else
        {
            $apiStatus = true;

            if ($gatewayPayment['status'] !== Status::CAPTURED)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'payment_id'                => $payment['id'],
                        'api_payment_status'        => $payment['status'],
                        'gateway_payment_status'    => $gatewayPayment['status'],
                    ]);
            }
        }

        return $apiStatus;
    }

    /**
     * Verify payment Request
     * @param Base\Verify $verify
     */
    protected function sendPaymentVerifyRequest(Base\Verify $verify)
    {
        $input = $verify->input;

        $requestContentArray = $this->getGatewayRequestContentArray($input);

        $requestContent = $this->getGatewayRequestContent($requestContentArray);

        $request = parent::getStandardRequestArray($requestContent, 'post', Action::VERIFY);

        $this->traceGatewayVerifyRequest($requestContentArray);

        $response = $this->postRequest($request);

        $response = $this->parseVerifyResponse($response);

        $verify->setVerifyResponseContent($response);
    }

    /**
     * @param $response
     *
     * @return array|string
     */
    protected function parseVerifyResponse($response)
    {
        $responseBody = $response->body;

        $response = Utility::createResponseArray($responseBody);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE_CONTENT,
            [
                'body' => $response
            ]
        );

        return $response;
    }

    /**
     * Traces verify request for the gateway becuase we don't persist the trackId.
     *
     * @param $requestContent
     */
    protected function traceGatewayVerifyRequest($requestContent)
    {
        $requestContent = $this->removeSensitiveRequestFields($requestContent);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'verify_content' => $requestContent
            ]
        );
    }

    protected function removeSensitiveRequestFields(array $requestContent)
    {
        $sensitiveKeys = [
            Fields::ID,
            Fields::PASSWORD,
            Fields::CARD,
            Fields::CVV,
            Fields::EXPIRY_MONTH,
            Fields::EXPIRY_YEAR,
        ];

        return array_diff_key($requestContent, array_flip($sensitiveKeys));
    }

    protected function getGatewayRequestContentArray($input)
    {
        $traceCode = '';

        $requestContent = [
            Fields::CURRENCY_CODE  => Currency::getIsoCode(Currency::INR),
            Fields::TYPE           => $this->getCardType($input[E::CARD][Card\Entity::TYPE]),

            Fields::UDF5           => Constants::TRACK_ID,
            Fields::LANGUAGE_ID    => Constants::LANGUAGE_USA,

            Fields::ID             => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_ID],
            Fields::PASSWORD       => $input[E::TERMINAL][Terminal\Entity::GATEWAY_TERMINAL_PASSWORD],
        ];

        switch ($this->action)
        {
            case Action::VERIFY:
                // In verify also fss needs a trackId.
                $requestContent[Fields::TRANSACTION_ID] =  $input['payment']['id'];
                $requestContent[Fields::ACTION]         = Constants::ACTION_INQUIRY;
                $requestContent[Fields::TRACK_ID]       = Entity::generateUniqueId();
                $requestContent[Fields::AMOUNT]         = $input[E::PAYMENT][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST;
                break;
            case Action::VERIFY_REFUND:
                $requestContent[Fields::TRANSACTION_ID] =  $input['refund']['id'];
                $requestContent[Fields::ACTION]         = Constants::ACTION_INQUIRY;
                $requestContent[Fields::TRACK_ID]       = Entity::generateUniqueId();
                $requestContent[Fields::AMOUNT]         = $input[E::REFUND][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_REFUND_VERIFY_REQUEST;
                break;
            case Action::REFUND:
                $requestContent[Fields::TRANSACTION_ID] = $input['payment']['id'];
                $requestContent[Fields::ACTION]         = Constants::ACTION_REFUND;
                $requestContent[Fields::TRACK_ID]       = $input[E::REFUND][Entity::ID];
                $requestContent[Fields::AMOUNT]         = $input[E::REFUND][Entity::AMOUNT] / 100;

                $traceCode = TraceCode::GATEWAY_REFUND_REQUEST;
                break;
        }

        $traceData = $this->removeSensitiveRequestFields($requestContent);

        $this->trace->info(
            $traceCode,
            [
                'request_data' => $traceData,
            ]
        );

        return $requestContent;
    }

    /**
     * FSS sends error messages in status, so parsing the same for storing.
     * @param array $attributes
     */
    protected function parseResponseStatus(array & $attributes)
    {
        $status = $attributes[Entity::STATUS];

        if (empty($status) === false and
            trim($status) !== Status::CAPTURED)
        {
            // Error message is sent as status.
            if (substr($status, 0, 4) === Constants::ERROR_MESSAGE_START)
            {
                $attributes[Entity::ERROR_MESSAGE] = $status;

                $attributes[Entity::STATUS] = Status::NOT_CAPTURED;
            }
        }
    }

    /**
     * @param array $requestContent
     *
     * @return string
     */
    protected function getGatewayRequestContent(array $requestContent): string
    {
        // Entire request content is wrapped in xml.
        $requestBuffer = Utility::createRequestXml($requestContent);

        return $requestBuffer;
    }

    /**
     * @param $request
     *
     * @return \Requests_Response
     */
    protected function postRequest($request)
    {
        $request['options'] = $this->getRequestOptions();

        $request['headers'] = $this->getRequestHeaders();

        $response = $this->sendGatewayRequest($request);

        return $response;
    }

    /**
     * Headers for the s2s call.
     * @return array
     */
    protected function getRequestHeaders()
    {
        $headers = [
            'Content-Type:application/xml',
            'Cache-Control: no-cache',
        ];

        return $headers;
    }

    /**
     * Verify of ssl certs should be false.
     * @return mixed
     */
    protected function getRequestOptions()
    {
        $options['verify'] = false;
        $options['timeout'] = 60;

        return $options;
    }

    /**
     * @param Entity $gatewayPayment
     * @param array  $gatewayContent
     *
     * @throws Exception\GatewayErrorException
     */
    protected function checkErrorMessage($gatewayPayment, $gatewayContent)
    {
        if (empty($gatewayPayment->getErrorMessage()) === false)
        {
            $gatewayCode = $this->getErrorCode($gatewayPayment->getErrorMessage());

            $errorDesc = ErrorCodes::getErrorDesc($gatewayCode);

            $errorCode = ErrorCodes::getMappedCode($gatewayCode);

            throw new Exception\GatewayErrorException($errorCode, $gatewayCode, $errorDesc, $gatewayContent);
        }
    }

    /**
     * Util function to split errorcodes
     * @param $errorText
     *
     * @return string
     */
    private function getErrorCode($errorText)
    {
        return trim(current(explode('-', $errorText)));
    }

    /**
     * Formats expmonth for the gateways desire.
     * @param $expMonth
     *
     * @return string
     */
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
    private function getCardType($cardType)
    {
        if ($cardType === Card\Type::DEBIT)
        {
            return Constants::DEBIT_CARD_TYPE;
        }

        return Constants::CREDIT_CARD_TYPE;
    }

    /**
     * @param $amount
     *
     * @return string
     */
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
    protected function checkCapturedStatus(Entity $gateway, $errorCode)
    {
        $status = $gateway->getStatus();

        if ($status !== Status::CAPTURED)
        {
            throw new Exception\GatewayErrorException($errorCode);
        }

    }
}
