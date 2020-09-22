<?php

namespace RZP\Gateway\Wallet\Mpesa;

use SoapFault;
use SoapClient;
use SoapHeader;
use Carbon\Carbon;
use RZP\Exception;
use SimpleXMLElement;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Utility;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Wallet\Base;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\ScroogeResponse;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Payment\Gateway as PaymentGateway;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'wallet_mpesa';

    const DATE_FORMAT = 'dmY';

    protected $map = [
        RequestFields::MERCHANT_CODE    => Base\Entity::GATEWAY_MERCHANT_ID,
        RequestFields::AMOUNT           => Base\Entity::AMOUNT,
        RequestFields::TRANSACTION_DATE => Base\Entity::DATE,
    ];

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthorizeRequestData($input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        $contentToSave = $this->getGatewayParamArray($input);

        $this->createGatewayPaymentEntity($contentToSave);

        return $request;
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_CALLBACK);

        $this->assertPaymentId($input['payment']['id'],
                               $content[ResponseFields::TRANSACTION_REFERENCE]);

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount = number_format($input['gateway']['txnAmt'], 2, '.', '');
        $this->assertAmount($expectedAmount, $actualAmount);

        $wallet = $this->repo->findByPaymentIdAndAction(
                    $input['payment']['id'],
                    Action::AUTHORIZE);

        $this->saveCallbackResponse($content, $wallet);

        $this->checkGatewayResponse($content[ResponseFields::STATUS_CODE]);

        $this->verifyCallback($input, $wallet);

        return $this->getCallbackResponseData($input);
    }

    /**
     * Verifying the payment after callback response is saved to
     * prevent user tampering with the data while making a payment.
     *
     * @param array $input
     * @param Base\Entity $wallet
     * @throws Exception\GatewayErrorException
     */
    protected function verifyCallback(array $input, Base\Entity $wallet)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verify->payment = $wallet;

        $this->sendPaymentVerifyRequest($verify);

        $this->checkGatewaySuccess($verify);

        //
        // If verify returns false, we throw an error as
        // authorize request / response has been tampered with
        //
        if ($verify->gatewaySuccess === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR);
        }
    }
    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $data = $this->getRefundData($input);

        $response = $this->sendSoapRequest($data,
                                           SoapAction::REFUND_API,
                                           SoapMethod::REFUND_PAYMENT);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'gateway'     => $this->gateway,
                'response'    => $response,
                'payment_id'  => $input['payment']['id'],
                'refund_id'   => $input['refund']['id'],
                'terminal_id' => $input['terminal']['id']
            ]);

        $content = $response[ResponseFields::UCF_RESPONSE];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        $attributes = $this->getRefundAttributes($content, $input);

        $this->createGatewayRefundEntity($attributes);

        $gatewayData = [
            PaymentGateway::GATEWAY_RESPONSE => json_encode($response),
            PaymentGateway::GATEWAY_KEYS     => $this->getGatewayData($content),
        ];

        // response will contain status 100 or 101
        $this->checkGatewayResponse($status, $gatewayData);

        return $gatewayData;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new ScroogeResponse();

        return $scroogeResponse->setSuccess(false)
                               ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                               ->toArray();
    }

    protected function sendPaymentVerifyRequest(Verify $verify)
    {
        $data = $this->getVerifyRequestData($verify);

        $input = $verify->input;

        try
        {
            $verify->verifyResponse = $this->sendSoapRequest($data,
                                                             SoapAction::QUERY_API,
                                                             SoapMethod::QUERY_PAYMENT_TRANSACTION);
        }
        catch (\Exception $e)
        {
            //
            // When soap faults or soap error's happen during verify, we must retry the verification call
            //

            $data = [
                'payment_id'  => $input['payment']['id'],
                'gateway'     => $this->gateway,
                'terminal_id' => $input['terminal']['id'],
            ];

            $this->trace->traceException($e, Logger::INFO, TraceCode::GATEWAY_VERIFY_ERROR, $data);

            $data['error_message'] = $e->getMessage();

            throw new Exception\PaymentVerificationException($data, $verify, VerifyAction::RETRY);
        }

        $this->traceGatewayPaymentResponse($verify->verifyResponse, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $verify->verifyResponse[ResponseFields::UCF_RESPONSE];
    }

    protected function verifyPayment(Verify $verify)
    {
        $status = $this->getVerifyStatus($verify);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->saveVerifyContent($verify);
    }

    protected function getVerifyStatus(Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        return $status;
    }

    protected function checkGatewaySuccess(Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // content will contain status 100 or 101
        if ($status === StatusCode::SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function validateCustomer(array $input)
    {
        $this->action($input, Action::VALIDATE_CUSTOMER);

        $data = $this->getValidateCustomerData($input);

        $response = $this->sendSoapRequest($data,
                                           SoapAction::CUSTOMER_API,
                                           SoapMethod::VALIDATE_CUSTOMER);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_VALIDATE_CUSTOMER_RESPONSE);

        $content = $response[ResponseFields::VALIDATE_CUSTOMER];

        $status = $content[ResponseFields::S2S_STATUS_CODE];

        // response will contain status 100 or 101
        $this->checkGatewayResponse($status);
    }

    protected function getAuthorizeRequestData(array $input)
    {
        $xml = $this->getGatewayRequestArray($input);

        $data = [
            RequestFields::GATEWAY_PARAM => $xml,
            RequestFields::CHECKSUM      => $this->getHashOfString($xml),
        ];

        return $data;
    }

    protected function getHashOfString($str)
    {
        return hash_hmac(HashAlgo::SHA256, $str, $this->getSecret());
    }

    protected function getGatewayRequestArray(array $input)
    {
        $array = $this->getGatewayParamArray($input);

        $xmlRoot = "<PaymentGatewayRequest />";

        //
        // Simple XML Element takes the values of the associate array
        // as the XML elements. Therefore, we need to flip the array
        // to ensure that the keys are selected instead.
        //
        $gatewayParam = array_flip($array);

        $gatewayParamXml = new SimpleXMLElement($xmlRoot);

        //
        // Recursively walks through the array and adds each entry in $gatewayParam
        // into $gatewayParamXml as an XML child of the origin XML root.
        //
        array_walk_recursive($gatewayParam, [$gatewayParamXml, 'addChild']);

        return trim(explode('?>', $gatewayParamXml->asXML())[1]);
    }

    protected function getGatewayParamArray(array $input)
    {
        $amount = $input['payment']['amount'] / 100;

        $gatewayParam = [
            // This is to maintain the backward compatibility
            // In old terminals, `merchant_id2` will be empty, hence assigning `gateway_merchant_id`
            RequestFields::MERCHANT_CODE         => $this->getMerchantId2() ?: $this->getMerchantId(),
            RequestFields::TRANSACTION_DATE      => $this->getFormattedDate(),
            RequestFields::TRANSACTION_REFERENCE => $input['payment']['id'],
            RequestFields::TRANSACTION_TYPE      => Constants::WALLET,
            RequestFields::AMOUNT                => (string) $amount,
            RequestFields::RETURN_URL            => $input['callbackUrl'],
            RequestFields::NARRATION             => Constants::NARRATION
        ];

        // This is to maintain the backward compatibility
        // Current terminals have only `gateway_merchant_id` assigned
        // New terminals will have `gateway_merchant_id` and `gateway_merchant_id2`
        // with values swapped. If it's an old terminal then `merchant_id2` will be empty
        // and filler3 should not be sent in that case.
        if (empty($this->getMerchantId2()) === false)
        {
            $gatewayParam[RequestFields::FILLER3] = $this->getMerchantId();
        }

        $this->trace->info(TraceCode::MPESA_GATEWAY_PARAM_ARRAY, $gatewayParam);

        return $gatewayParam;
    }

    protected function getVerifyRequestData(Verify $verify)
    {
        $input = $verify->input;

        $wallet = $verify->payment;

        $gatewayPaymentId = $wallet->getGatewayPaymentId() ?? "";

        $paymentId = $input['payment']['id'];

        $amount = $input['payment']['amount'] / 100;

        $queryData = [
            // This is to maintain the backward compatibility
            RequestFields::MERCHANT_CODE             => $this->getMerchantId2() ?: $this->getMerchantId(),
            RequestFields::QUERY_TRANSACTION_DATE    => $this->getFormattedDate(),
            RequestFields::COM_TRANSACTION_ID        => $gatewayPaymentId,
            RequestFields::QUERY_TRANSACTION_REF     => $paymentId,
            RequestFields::PMT_TRANSACTION_REFERENCE => $paymentId,
            RequestFields::AMOUNT                    => $amount,
        ];

        //
        // We save the gateway payment id 2 only during the
        // otp_generate flow. Therefore, this a good measure
        // of whether the CMDID field needs to be sent
        //
        if (empty($wallet->getGatewayPaymentId2()) === false)
        {
            $queryData[RequestFields::CMDID] = Constants::CMDID;
        }

        return $queryData;
    }

    protected function getValidateCustomerData(array $input)
    {
        $contact = $input['payment']['contact'];

        $data = [
            RequestFields::CHANNEL_ID    => Constants::CHANNEL_ID,
            RequestFields::REQUEST_ID    => uniqid(),
            RequestFields::MOBILE_NUMBER => $this->getFormattedContact($contact)
        ];

        return [RequestFields::COMMON_SERVICE_DATA => $data];
    }

    protected function getRefundData(array $input)
    {
        $wallet = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE);

        $gatewayPaymentId = $wallet->getGatewayPaymentId();

        $amount = $input['refund']['amount'] / 100;

        $data = [
            // This is to maintain the backward compatibility
            RequestFields::MERCHANT_CODE         => $this->getMerchantId2() ?: $this->getMerchantId(),
            RequestFields::COM_TRANSACTION_ID    => $gatewayPaymentId ?? "",
            RequestFields::QUERY_TRANSACTION_REF => $input['payment']['id'],
            RequestFields::S2S_AMOUNT            => $amount,
            RequestFields::REFUND_NARRATION      => Constants::REFUND_NARRATION,
            RequestFields::REVERSAL_TYPE         => $this->getReversalType($input)
        ];

        return $data;
    }

    protected function getRefundAttributes(array $content, array $input)
    {
        $attributes = [
            Base\Entity::RECEIVED             => true,
            Base\Entity::PAYMENT_ID           => $input['payment']['id'],
            Base\Entity::WALLET               => Wallet::MPESA,
            Base\Entity::AMOUNT               => $input['refund']['amount'],
            Base\Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::S2S_TRANS_ID],
            Base\Entity::RESPONSE_CODE        => $content[ResponseFields::S2S_STATUS_CODE],
            Base\Entity::REFUND_ID            => $input['refund']['id'],
            Base\Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON] ?? ''
        ];

        return $attributes;
    }

    /**
     * If refund amount is less than payment amount, it is a partial refund
     *
     * @return string $reversalType
     */
    protected function getReversalType(array $input)
    {
        if ($input['payment']['amount'] === $input['refund']['amount'])
        {
            return Constants::FULL_REVERSAL;
        }

        return Constants::PARTIAL_REVERSAL;
    }

    protected function sendSoapRequest(array $data, string $soapRoot, string $method)
    {
        $context = [
            'payment_id'  => $this->input['payment']['id'],
            'gateway'     => $this->gateway,
            'soap_method' => $method,
            'request'     => [
                'soap_root' => $soapRoot,
                'data'      => $data
            ],
        ];

        $this->traceGatewayPaymentRequest($context, $this->input, TraceCode::GATEWAY_SOAP_REQUEST);

        try
        {
            $client = $this->getSoapClientObject();

            $response = $client->__soapCall($method, [$soapRoot => $data]);
        }
        catch (SoapFault $e)
        {
            // Handle soapfaults gracefully
            if (isset($client) === true)
            {
                $context['soap_response'] = $client->__getLastResponse();
            }

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::GATEWAY_SOAP_FAULT,
                $context);

            $this->handleSoapFault($e, $method);
        }
        catch (\Exception $e)
        {
            $context['error_message'] = $e->getMessage();
            $context['terminal_id'] = $this->input['terminal']['id'];
            //
            // Non SoapFaults can be handled differently
            // We simply trace this at a warning level
            //
            $this->trace->warning(TraceCode::GATEWAY_SOAP_ERROR, $context);
        }

        return json_decode(json_encode($response), true);
    }

    protected function handleSoapFault(SoapFault $e, string $method)
    {
        if (Utility::checkSoapTimeout($e) === true)
        {
            throw new Exception\GatewayTimeoutException($e->getMessage(), $e);
        }

        $errorMessage = SoapMethod::getErrorMessage($method);

        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_SOAP_ERROR, null, $errorMessage, [], $e);
    }

    protected function getSoapHeaders()
    {
        $wsseNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

        $headers = [
            new SoapHeader($wsseNs, Constants::USER_ID, $this->getSoapUserId()),
            new SoapHeader($wsseNs, Constants::PASSWORD, $this->getSoapPassword())
        ];

        return $headers;
    }

    protected function checkGatewayResponse(string $status, array $gatewayData = [])
    {
        if (StatusCode::isStatusSuccess($status) === false)
        {
            throw new Exception\GatewayErrorException(
                StatusCode::getErrorCode($status),
                $status,
                StatusCode::getErrorMessage($status),
                $gatewayData
            );
        }
    }

    protected function saveCallbackResponse(array $content, Base\Entity $wallet)
    {
        $contentToSave = [
            Base\Entity::RECEIVED             => true,
            Base\Entity::GATEWAY_PAYMENT_ID   => $content[ResponseFields::COM_TRANSACTION_ID],
            Base\Entity::RESPONSE_CODE        => $content[ResponseFields::STATUS_CODE],
            Base\Entity::RESPONSE_DESCRIPTION => $content[ResponseFields::REASON],
        ];

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function saveVerifyContent(Verify $verify)
    {
        $wallet = $verify->payment;

        $content = $verify->verifyResponseContent;

        $errorMessage = StatusCode::getErrorMessage($content[ResponseFields::S2S_STATUS_CODE]);

        $contentToSave = [
            Base\Entity::RESPONSE_CODE        => $content[ResponseFields::S2S_STATUS_CODE],
            Base\Entity::RESPONSE_DESCRIPTION => $errorMessage
        ];

        if ((empty($wallet[Base\Entity::GATEWAY_PAYMENT_ID]) === true) and
            (isset($content[ResponseFields::S2S_TRANS_ID]) === true))
        {
            $contentToSave[Base\Entity::GATEWAY_PAYMENT_ID] = $content[ResponseFields::S2S_TRANS_ID];
        }

        $this->updateGatewayPaymentEntity($wallet, $contentToSave, false);
    }

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        $this->trace->info($traceCode,
            [
                'request'     => $request,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function traceGatewayPaymentResponse(
        $response,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_RESPONSE)
    {
        $this->trace->info($traceCode,
            [
                'response'    => $response,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);
    }

    protected function getSoapClientObject()
    {
        $file = $this->getWsdlFile();

        $soapClient = new SoapClient($file, ['trace' => 1]);

        $headers = $this->getSoapHeaders();

        $soapClient->__setSoapHeaders($headers);

        return $soapClient;
    }

    protected function getWsdlFile()
    {
        $file = __DIR__ . '/Wsdl/mpesalive.wsdl.xml';

        if ($this->mode === Mode::TEST)
        {
            $file = __DIR__ . '/Wsdl/mpesatest.wsdl.xml';
        }

        return $file;
    }

    protected function getMappedAttributes($attributes)
    {
        return parent::getMappedAttributes($attributes);
    }

    protected function getFormattedDate()
    {
        return Carbon::now(Timezone::IST)->format(self::DATE_FORMAT);
    }

    protected function getMerchantId()
    {
        $merchantId = $this->terminal['gateway_merchant_id'];

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['test_merchant_id'];
        }

        return $merchantId;
    }

    protected function getMerchantId2()
    {
        $merchantId = $this->terminal['gateway_merchant_id2'];

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['test_merchant_id2'];
        }

        return $merchantId;
    }

    protected function getSoapUserId()
    {
        $userId = $this->config['live_user_id'];

        if ($this->mode === Mode::TEST)
        {
            $userId = $this->config['test_user_id'];
        }

        return $userId;
    }

    protected function getSoapPassword()
    {
        $password = $this->config['live_password'];

        if ($this->mode === Mode::TEST)
        {
            $password = $this->config['test_password'];
        }

        return $password;
    }

    /**
     * We are picking up the live secret from the config variable
     * @return mixed
     */
    protected function getLiveSecret()
    {
        return $this->config['live_hash_secret'];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                ResponseFields::S2S_TRANS_ID    => $response[ResponseFields::S2S_TRANS_ID] ?? null,
                ResponseFields::DESCRIPTION     => $response[ResponseFields::DESCRIPTION] ?? null,
                ResponseFields::RESPONSE_ID     => $response[ResponseFields::RESPONSE_ID] ?? null,
                ResponseFields::S2S_STATUS_CODE => $response[ResponseFields::S2S_STATUS_CODE] ?? null,
            ];
        }

        return [];
    }
}
