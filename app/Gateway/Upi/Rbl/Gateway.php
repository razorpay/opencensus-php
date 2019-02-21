<?php

namespace RZP\Gateway\Upi\Rbl;

use Carbon\Carbon;
use phpseclib\Crypt\AES;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Base;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Verify;
use RZP\Gateway\Base\AESCrypto;
use RZP\Gateway\Upi\Base\Entity;
use RZP\Gateway\Base\VerifyResult;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\GatewayErrorException;

class Gateway extends Base\Gateway
{
    use RequestTrait;

    protected $gateway = 'upi_rbl';

    const ACQUIRER = 'rbl';

    const CAPABILITY = '100';

    const CERTIFICATE_DIRECTORY_NAME = 'cert_dir_name';

    const UPI_COLLECT_EXPIRY = 1440;

    protected $map = [
        Fields::STATUS                  => Entity::STATUS_CODE,
        Fields::GATEWAY_TRANSACTION_ID  => Entity::GATEWAY_PAYMENT_ID,
        Fields::UPI_TRANSACTION_ID      => Entity::NPCI_TXN_ID,
        Fields::CUSTOMER_REF            => Entity::NPCI_REFERENCE_ID,
        Fields::TRANSACTION_STATUS      => Entity::STATUS_CODE,
        Fields::PAYEE_MOBILE            => Entity::CONTACT,
        Fields::PAYER_VPA               => Entity::VPA,
        Fields::PAYER_VERIFIED_NAME     => Entity::NAME,
        Fields::NPCI_ERROR_CODE         => Entity::STATUS_CODE,
        Fields::PAYER_ACCOUNT_NUMBER    => Entity::ACCOUNT_NUMBER,
        Fields::PAYER_IFSC              => Entity::IFSC,

        Entity::EXPIRY_TIME            => Entity::EXPIRY_TIME,
        Entity::VPA                    => Entity::VPA,
        Entity::RECEIVED               => Entity::RECEIVED
    ];

    public function authorize(array $input)
    {
        if ((isset($input['upi']['flow']) === true) and
            ($input['upi']['flow'] === 'intent'))
        {
            parent::authorize($input);

            return $this->authorizeIntent($input);
        }

        $sessionToken = $this->getSessionToken($input);

        $authToken = $this->generateAuthToken($input, $sessionToken);

        $transactionId = $this->getTransactionId($sessionToken, $authToken, $input);

        $attributes = $this->getGatewayEntityAttributes($input);

        $attributes[Fields::GATEWAY_TRANSACTION_ID] = $transactionId;

        parent::authorize($input);

        $gatewayPayment = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getAuthorizeRequestArray($input, $sessionToken, $authToken, $transactionId);

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body, $input);

        $responseArray = $this->xmlToArray($response->body);

        $this->checkCollectResponse($responseArray);

        $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

        return [
            'data'   => [
                'vpa'   => $this->getMerchantVpa()
            ]
        ];
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK, [
            'gateway'           => $this->gateway,
            'payment_id'        => $input['payment']['id'],
            'terminal_id'       => $input['terminal']['id'],
            'content'           => $content,
        ]);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $this->updateGatewayPaymentResponse($gatewayPayment, $content);

        $this->assertPaymentId($input['payment']['id'], $content[Fields::REFID]);

        $this->assertAmount($this->formatAmount($input['payment']['amount'] / 100),
            $this->formatAmount($content[Fields::AMOUNT]));

        $this->checkCallbackResponse($content);

        return [
            'acquirer' => [
                'vpa' => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16 => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function postProcessServerCallback($input, $exception = null)
    {
        $gatewayInput = $input['gateway'];

        if ($exception === null)
        {
            return $this->callbackResponseArray($gatewayInput, Status::CALLBACK_SUCCESS);
        }

        return $this->callbackResponseArray($gatewayInput, Status::CALLBACK_FAILED);
    }

    public function getSecret()
    {
        return $this->config['aes_encryption_key'];
    }

    public function getPaymentIdFromServerCallback($input)
    {
        if (isset($input[Fields::REFID]) === true)
        {
            return $input[Fields::REFID];
        }

        throw new Exception\GatewayErrorException(
            ErrorCode::GATEWAY_ERROR_CALLBACK_EMPTY_INPUT,
            null,
            null,
            ['input' => $input]);
    }

    public function getEncryptedString($string)
    {
        $masterKey = hex2bin($this->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return base64_encode($aes->encryptString($string));
    }

    public function getTransactionId($sessionToken, $authToken, $input)
    {
        parent::action($input, Action::GET_TRANSACTION_ID);

        $content = [
            Fields::HMAC => $authToken,
            Fields::ID => $input['payment']['id'],
        ];

        $request[Fields::GET_TRANSACTION_ID_REQUEST] = array_merge($content,
                                                        $this->getCustomerAndOtherDetails($sessionToken));

        $requestXml = $this->arrayToXml($request);

        $request = $this->getStandardRequestArray($requestXml);

        $this->traceGatewayPaymentRequest($request, $input);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body, $input);

        $responseArray = $this->xmlToArray($response->body);

        $this->checkTokenStatus($responseArray);

        $transactionId = $responseArray[Fields::DESCRIPTION];

        return $transactionId;
    }

    public function preProcessServerCallback($response) : array
    {
        $content = $this->parseGatewayResponse($response);

        return $content;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $sessionToken = $this->getSessionToken($input);

        $authToken = $this->generateAuthToken($input, $sessionToken);

        $request = $this->getVerifyRequest($sessionToken, $authToken, $input);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $content = $this->xmlToArray($response->body);

        $this->traceGatewayPaymentResponse($content, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $content;
    }

    protected function verifyPayment($verify)
    {
        $content = $verify->verifyResponseContent;

        $this->updateGatewayPaymentEntity($verify->payment, $content);

        // we check if the api call to rbl failed, in case of session token expired,
        // we will check whether status is 0 or 1
        $this->checkTokenStatus($content);

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        $status = VerifyResult::STATUS_MATCH;

        // If both don't match we have a status mis match
        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $input = $verify->input;

        if ($verify->gatewaySuccess === true)
        {
            $paymentAmount = $this->formatAmount($input['payment']['amount'] / 100);

            $actualAmount = $this->formatAmount($content[Fields::AMOUNT]);

            $verify->amountMismatch = ($paymentAmount !== $actualAmount);
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);
    }

    protected function checkGatewaySuccess($verify)
    {
        $content = $verify->verifyResponseContent;

        $verify->gatewaySuccess = false;

        if ((isset($content[Fields::DESCRIPTION]) === true) and
            ($content[Fields::DESCRIPTION] === Status::SUCCESS))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function getVerifyRequest($sessionToken, $authToken, $input)
    {
        parent::action($input, Action::VERIFY);

        $content = [
            Fields::ORG_TXN_ID_OR_REF_ID => $input['payment']['id'],
            Fields::FLAG                 => 1,
            Fields::HMAC                 => $authToken,
            Fields::ID                   => $input['payment']['id']
        ];

        $request[Fields::SEARCH_REQUEST] = array_merge($content, $this->getCustomerAndOtherDetails($sessionToken));

        $requestXml = $this->arrayToXml($request);

        $request = $this->getStandardRequestArray($requestXml, 'post');

        return $request;
    }

    protected function authorizeIntent(array $input)
    {
        $attributes = [
            Entity::TYPE                => Base\Type::PAY,
            Entity::GATEWAY_MERCHANT_ID => $this->getMerchantId(),
        ];

        $payment = $this->createGatewayPaymentEntity($attributes);

        $request = $this->getIntentRequest($input);

        return $request;
    }

    protected function getIntentRequest($input)
    {
        $content = [
            Base\IntentParams::PAYEE_ADDRESS => $input['terminal']->getGatewayMerchantId2(),
            Base\IntentParams::PAYEE_NAME    => preg_replace('/\s+/', '',
                                                             $input['merchant']->getFilteredDba()),
            Base\IntentParams::TXN_REF_ID    => $input['payment']['id'],
            Base\IntentParams::TXN_NOTE      => $this->getPaymentRemark($input),
            Base\IntentParams::TXN_AMOUNT    => $input['payment']['amount'] / 100,
            Base\IntentParams::TXN_CURRENCY  => $input['payment']['currency'],
            Base\IntentParams::MCC           => $input['merchant']['category'],
        ];

        $query = str_replace(' ', '', urldecode(http_build_query($content)));

        return ['data' => ['intent_url' => 'upi://pay?' . $query]];
    }

    protected function parseGatewayResponse(string $response): array
    {
        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [
            'body'      => $response,
            'encrypted' => true,
            'gateway'   => $this->gateway
        ]);

        $responseArray = $this->xmlToArray($response);

        $response = base64_decode($responseArray[0], true);

        try
        {
            $content = $this->getDecryptedString($response);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        return $this->xmlToArray($content);
    }

    protected function checkCollectResponse($response)
    {
        if (($response[Fields::STATUS] !== Status::TOKEN_SUCCESS) or
            ($response[Fields::RESULT] !== Status::SUCCESS))
        {
            $result = $response[Fields::RESULT];

            $this->setResponseCodeAndThrowError($result);
        }
    }

    protected function getMerchantVpa()
    {
        return $this->terminal->getGatewayMerchantId2();
    }

    protected function formatAmount(int $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    protected function setResponseCodeAndThrowError($response)
    {
        // rbl sends error code and description in one tag, separated by :
        // or can send the error description/code directly,
        // egs => MRER005:txn:refId:MIN_LENGTH_REQUIRED or
        // Your Session has been Expired.Please Relogin the Application
        // or ERR00071

        if (str_contains($response, ':') === true)
        {
            // case -> MRER005:txn:refId:MIN_LENGTH_REQUIRED
            $messageArray = explode(':' , $response);

            if (isset($messageArray[0]) === true)
            {
                $responseCode = $messageArray[0];

                $errorCode = ResponseCodes::getApiResponseCode($responseCode);

                $message = implode(' ', array_slice($messageArray, 1));

                $responseMessage = empty($message) ? ResponseCodes::getResponseMessage($responseCode) : $message;
            }
        }
        else
        {
            // case -> ERR00071 or Your Session has been Expired.Please Relogin the Application
            $responseCode = $response;

            $errorCode = ResponseCodes::getApiResponseCode($responseCode);

            $responseMessage = ResponseCodes::getResponseMessage($responseCode);
        }

            throw new Exception\GatewayErrorException(
            $errorCode,
            $responseCode,
            $responseMessage,
            [
                'response' => $response,
                'gateway'  => $this->gateway,
            ]);
    }

    protected function getDecryptedString($string)
    {
        $masterKey = hex2bin($this->getSecret());

        $aes = new AESCrypto(AES::MODE_ECB, $masterKey);

        return $aes->decryptString($string);
    }

    protected function checkCallbackResponse($response)
    {
        if ((isset($response[Fields::TRANSACTION_STATUS]) === true) and
            ($response[Fields::TRANSACTION_STATUS] !== Status::SUCCESS))
        {
            $result = $response[Fields::NPCI_ERROR_CODE];

            $errorCode = ErrorCodes\ErrorCodes::getErrorCode($response);

            $errorMessage = ErrorCodes\ErrorCodeDescriptions::getGatewayErrorDescription($response);

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[Fields::NPCI_ERROR_CODE],
                $errorMessage,
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                ]);
        }
    }

    protected function getSessionToken($input)
    {
        parent::action($input, Action::SESSION_TOKEN);

        $content = [
            Fields::CHANNEL_PARTNER_LOGIN_REQUEST => [
                Fields::USER_NAME   => $this->getPartnerUsername(),
                Fields::PASSWORD    => $this->getPartnerPassword(),
                Fields::BC_AGENT    => $this->getPartnerBcAgent(),
            ]
        ];

        $requestXml = $this->arrayToXml($content);

        $this->traceGatewayPaymentRequest($content, $input, TraceCode::GATEWAY_TOKEN_REQUEST);

        $request = $this->getStandardRequestArray($requestXml);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input, TraceCode::GATEWAY_TOKEN_RESPONSE);

        $responseArray = $this->xmlToArray($response->body);

        if ((isset($responseArray[Fields::STATUS]) === false) or
            ($responseArray[Fields::STATUS] !== Status::TOKEN_SUCCESS))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                null,
                null,
                [
                    'gateway'  => $this->gateway,
                    'response' => $response,
                ]
            );
        }

        $sessionToken = $responseArray[Fields::SESSION_TOKEN];

        return $sessionToken;
    }


    protected function callbackResponseArray($input, $code)
    {
        $data = [
            Fields::UPI_PUSH_RESPONSE => [
                Fields::STATUS_CODE => $code,
                Fields::DESCRIPTION => Status::ACKNOWLEDGE
            ]
        ];

        return $this->arrayToXml($data);
    }

    protected function getAuthorizeRequestArray(array $input, $sessionToken, $authToken, $transactionId)
    {
        $expiryTime = self::UPI_COLLECT_EXPIRY;

        $collectByTimestamp = Carbon::now(Timezone::IST)->addMinutes($expiryTime)->format('Y-m-d h:i:s');

        $content = [
            Fields::PAYEE_ADDRESS            => $input['payment']['vpa'],
            Fields::PAYEE_NAME               => preg_replace('/\s+/', '',
                                                                $input['merchant']->getFilteredDba()),
            Fields::PAYER_ADDRESS            => $this->getMerchantVpa(),
            Fields::VALID_UPTO               => $collectByTimestamp,
            Fields::ORG_TRANSACTION_ID       => $input['payment']['id'],
            Fields::AMOUNT                   => $this->formatAmount($input['payment']['amount'] / 100),
            Fields::HMAC                     => $authToken,
            Fields::GATEWAY_TRANSACTION_ID   => $transactionId,
            Fields::REF_ID                   => $input['payment']['id'],
            Fields::NOTE                     => $this->getDynamicMerchantName($input['merchant']),
            Fields::REF_URL                  => 'https://razorpay.com',
            Fields::PAYER_NAME               => 'Razorpay Customer',
            Fields::ID                       => $input['payment']['id'],
        ];

        $request[Fields::COLLECT_REQUEST] = array_merge($content, $this->getCustomerAndOtherDetails($sessionToken));

        $requestXml = $this->arrayToXml($request);

        $request = $this->getStandardRequestArray($requestXml);

        return $request;
    }

    protected function getGatewayEntityAttributes(array $input, string $action = Action::AUTHORIZE)
    {
        if ($action === Action::AUTHORIZE)
        {
            $attributes = [
                Entity::VPA         => $input['payment']['vpa'],
                Entity::TYPE        => Base\Type::COLLECT,
                Entity::EXPIRY_TIME => $input['upi']['expiry_time'],
                Entity::ACTION      => Action::AUTHORIZE,
            ];
        }

        return $attributes;
    }

    protected function arrayToXml(array $array, string $wrap = null)
    {
        // set initial value for XML string
        $xml = '';

        foreach ($array as $key => $value)
        {
            if (is_array($value) === true)
            {
                $xml .= $this->arrayToXml($value, $key);
            }
            else
            {
                $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
            }
        }

        // wrap XML with $wrap TAG
        if ($wrap !== null)
        {
            $xml = "<$wrap>".$xml."</$wrap>";
        }

        return $xml;
    }

    protected function getPartnerUserName()
    {
        return $this->config['channel_partner_username'];
    }

    protected function getPartnerPassword()
    {
        return $this->config['channel_partner_password'];
    }

    protected function getPartnerBcAgent()
    {
        return $this->config['channel_partner_bc_agent'];
    }

    protected function checkTokenStatus($input)
    {
        if ((isset($input[Fields::STATUS]) === false) or
            ($input[Fields::STATUS] !== Status::TOKEN_SUCCESS))
        {
            $result = $input[Fields::DESCRIPTION];

            $this->setResponseCodeAndThrowError($result);
        }
    }

    protected function generateAuthToken($input, $sessionToken)
    {
        parent::action($input, Action::GENERATE_AUTH_TOKEN);

        $content = [
            Fields::ID          => $input['payment']['id'],
            Fields::NOTE        => 'Razorpay Payment',
            Fields::REF_ID      => $input['payment']['id'],
            Fields::HMAC        => $this->getHmac(),
            Fields::REF_URL     => 'https://razorpay.com',
        ];

        $request[Fields::GENERATE_AUTH_TOKEN_REQUEST] = array_merge($content,
                                                                $this->getCustomerAndOtherDetails($sessionToken));

        $requestXml = $this->arrayToXml($request);

        $this->traceGatewayPaymentRequest($request, $input);

        $request = $this->getStandardRequestArray($requestXml);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response, $input);

        $responseArray = $this->xmlToArray($response->body);

        $this->checkTokenStatus($responseArray);

        return $responseArray[Fields::TOKEN];
    }

    protected function gatewayMerchantId()
    {
        return $this->terminal->getGatewayMerchantId();
    }

    protected function getAggregatorId()
    {
        return $this->config['aggregator_id'];
    }

    protected function getUpiMobileNumber()
    {
        return $this->config['customer_mobile_number'];
    }

    protected function getUpiIp()
    {
        return $this->config['customer_ip'];
    }

    protected function getUpiGeoCode()
    {
        return $this->config['customer_geo_code'];
    }

    protected function getUpiApp()
    {
        return $this->config['customer_app'];
    }

    protected function getUpiOs()
    {
        return $this->config['customer_os'];
    }

    protected function getUpiLocation()
    {
        return $this->config['customer_location'];
    }

    protected function getHmac()
    {
        $attr = [
            $this->getAggregatorId(),
            $this->getMerchantId(),
        ];

        $msg = $this->getStringToHash($attr, '|');

        $encryptedMessage = $this->getEncryptedString($msg);

        return $encryptedMessage;
    }

    protected function getCustomerAndOtherDetails($sessionToken)
    {
        $content = [
            Fields::HEADER => [
                Fields::SESSION_TOKEN           => $sessionToken,
                Fields::BC_AGENT                => $this->getPartnerBcAgent(),
            ],
            Fields::MOBILE                      => $this->getUpiMobileNumber(),
            Fields::GEO_CODE                    => $this->getUpiGeoCode(),
            Fields::LOCATION                    => $this->getUpiLocation(),
            Fields::IP                          => $this->getUpiIp(),
            Fields::TYPE                        => 'MOB',
            Fields::OS                          => $this->getUpiOs(),
            Fields::APP                         => $this->getUpiApp(),
            Fields::CAPABILITY                  => self::CAPABILITY,
            Fields::MERCHANT_ORGANIZATION_ID    => $this->gatewayMerchantId(),
            Fields::AGGREGATOR_ID               => $this->getAggregatorId(),
        ];

        return $content;
    }

    protected function getMerchantId()
    {
        return $this->terminal->getGatewayMerchantId();
    }

    protected function updateGatewayPaymentResponse($payment, array $response)
    {
        $attributes = $this->getMappedAttributes($response);

        // To mark that we have received a response for this request
        $attributes[Entity::RECEIVED] = 1;

        $payment->fill($attributes);

        $payment->generatePspData($attributes);

        $this->repo->saveOrFail($payment);
    }
}
