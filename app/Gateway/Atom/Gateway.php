<?php

namespace RZP\Gateway\Atom;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Terminal;
use RZP\Constants\Timezone;
use RZP\Constants\HashAlgo;
use RZP\Gateway\Base\Action;
use RZP\Constants\Entity as E;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Base\AuthorizeFailed;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed;

    protected $gateway = 'atom';

    const CHECKSUM_ATTRIBUTE = AuthResponseFields::SIGNATURE;

    const TIME_WINDOW = 600;

    const REFUND_DATE_ERROR_MESSAGE = 'Invalid transaction date';

    public function authorize(array $input)
    {
        parent::authorize($input);

        $attributes = $this->getAuthorizeAttributes($input);

        $this->createGatewayPaymentEntity($attributes);

        $content = $this->getAuthRequestContentArray($input);

        $request = $this->getStandardRequestArray($content, 'get');

        $this->traceGatewayPaymentRequest($request, $input);

        $request['url'] = $this->createRedirectUrl($request['content']);
        $request['content'] = [];

        $request['options'] = $this->getRequestOptions();

        $request = $this->makeRequestAndGetFormData($request);

        $this->traceGatewayPaymentRequest($request, $input);

        $this->updateUrlInCacheAndPushMetric($input, $request['url']);

        return $request;
    }

    /**
     * We recieve callback from atom after bank net-banking transaction
     * is complete
     * @param  array    $input
     */
    public function callback(array $input)
    {
        parent::callback($input);

        $content = $input['gateway'];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'content' => $content,
            ]);

        if (isset($content[AuthResponseFields::AMOUNT],
                  $content[AuthResponseFields::TRANSACTION_ID],
                  $content[AuthResponseFields::STATUS_CODE]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                null,
                ['response' => $input]);
        }

        if ($content[AuthResponseFields::STATUS_CODE] !== Status::SUCCESS)
        {
            $message = 'Payment Failed during callback';

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                $content[AuthResponseFields::STATUS_CODE],
                $message);
        }

        $expectedAmount = number_format($input['payment']['amount'] / 100, 2, '.', '');
        $actualAmount   = number_format($content[AuthResponseFields::AMOUNT], 2, '.', '');

        $this->assertPaymentId($input['payment']['id'], $content[AuthResponseFields::TRANSACTION_ID]);

        $this->assertAmount($expectedAmount, $actualAmount);

        $this->verifySecureHash($content);

        $gatewayPayment = $this->saveCallbackContent($input, $content);

        $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

        return $this->getCallbackResponseData($input, $acquirerData);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $content = $this->getRefundRequestContent($gatewayPayment, $input);

        $responseContent = $this->getRefundResponse($content, $input);

        $responseArray = $this->xmlToArray($responseContent);

        if ($this->checkRefundDateError($responseArray) == true)
        {
            $content[RefundRequestFields::TRANSACTION_DATE] = $this->getPreviousDate(
                                                                    $content[RefundRequestFields::TRANSACTION_DATE]);

            $responseContent = $this->getRefundResponse($content, $input);

            $responseArray = $this->xmlToArray($responseContent);
        }

        $date = $content[RefundRequestFields::TRANSACTION_DATE];

        $dateTimestamp = Carbon::createFromFormat('Y-m-d', $date , Timezone::IST)->timestamp;

        $attributes = $this->getRefundAttributes($responseArray, $dateTimestamp);

        $this->createGatewayPaymentEntity($attributes);

        $this->checkRefundSuccess($responseArray);

        $scroogeGatewayResponse = (is_string($responseContent) === false) ?
                                   json_encode($responseContent) :
                                   $responseContent;

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => $scroogeGatewayResponse,
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($responseArray)
        ];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function makeRequestAndGetFormData(array $request): array
    {
        $response = $this->sendGatewayRequest($request);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_RESPONSE, [$response->body]);

        $crawler = new Crawler($response->body, $request['url']);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_HEADLESS_PARSING_FAILED,
                null,
                null,
                [
                    'gateway' => $this->gateway,
                ]
            );
        }

        $form = $formCrawler->form();

        $request = [
            'url'     => $form->getUri(),
            'method'  => strtolower($form->getMethod()),
            'content' => $form->getValues(),
        ];

        return $request;
    }

    public function getRequestOptions()
    {
        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', [$this, 'setCurlOpts']);

        $options['hooks'] = $hooks;

        return $options;
    }

    public function setCurlOpts($curl)
    {
        curl_setopt($curl, CURLOPT_REFERER, null);
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $responseArray = $this->getVerifyResponse($content, $verify);

        $paymentCreatedAt = $verify->input['payment'][Payment\Entity::CREATED_AT];

        $gatewayPayment = $verify->payment;

        if ($this->checkVerifyDateError($responseArray, $paymentCreatedAt))
        {
            $originalDate = $content[VerifyRequestFields::TRANSACTION_DATE];

            $content[VerifyRequestFields::TRANSACTION_DATE] = $this->getPreviousDate($originalDate);

            $responseArray = $this->getVerifyResponse($content, $verify);
        }

        if ($responseArray['VERIFIED'] !== 'NODATA')
        {
            $date = $content[VerifyRequestFields::TRANSACTION_DATE];

            $dateTimestamp = Carbon::createFromFormat('Y-m-d', $date , Timezone::IST)->timestamp;

            $data = [
                Entity::DATE => $dateTimestamp,
            ];

            $verify->payment->fill($data);

            $verify->payment->saveOrFail();
        }

        $verify->verifyResponseContent = $responseArray;
    }

    protected function verifyPayment(Base\Verify $verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $input = $verify->input;

        $verifyResponse = $verify->verifyResponseContent;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        $this->verifyAmountMismatch($verify, $input, $verifyResponse, E::PAYMENT);

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContent($verify);
    }

    public function processVerifyRefundResponse(array $verifyRefundResponseArray, $refundId)
    {
        if ($verifyRefundResponseArray[VerifyRefundFields::ERRORCODE] !== Status::VERIFY_REFUND_SUCCESS)
        {
            return false;
        }

        $refundArray = $verifyRefundResponseArray[VerifyRefundFields::DETAILS][VerifyRefundFields::REFUND];

        if (isset($refundArray[VerifyRefundFields::MEREFUNDREF]) === true)
        {
            if ($refundArray[VerifyRefundFields::MEREFUNDREF] !== $refundId)
            {
                return false;
            }
        }
        else
        {
            $refundFound = false;

            foreach ($refundArray as $refund)
            {
                if ($refund[VerifyRefundFields::MEREFUNDREF] === $refundId)
                {
                    $refundFound = true;

                    $refundArray = $refund;

                    break;
                }
            }

            if ($refundFound === false)
            {
                return false;
            }
        }

        $success = $received = true;

        $gatewayEntity = $this->repo->findByRefundId($refundId);

        if ($gatewayEntity !== null)
        {
            $gatewayEntity->setSuccess($success);

            $gatewayEntity->setReceived($received);

            $gatewayEntity->setStatus(Status::SUCCESS);

            $this->repo->saveOrFail($gatewayEntity);
        }
        else
        {
            $attributes = $this->getRefundAttributesFromVerify($verifyRefundResponseArray);

            $this->createGatewayPaymentEntity($attributes,'refund');
        }

        $refundArray[VerifyRefundFields::MESSAGE] = $verifyRefundResponseArray[VerifyRefundFields::MESSAGE];

        $refundArray[VerifyRefundFields::ERRORCODE] = $verifyRefundResponseArray[VerifyRefundFields::ERRORCODE];

        return $refundArray;
    }

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $scroogeResponse = new Base\ScroogeResponse();

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                   ->toArray();
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return $scroogeResponse->setSuccess(true)
                                   ->toArray();
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndAction($input['payment']['id'], Action::AUTHORIZE);

        $content = $this->getVerifyRefundRequestData($input, $gatewayPayment);

        $request = $this->getStandardRequestArray($content, 'get','verify_refund');

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'     => $request,
                'payment_id'  => $input['payment']['id'],
                'refund_id'   => $input['refund']['id'],
                'gateway'     => $this->gateway,
                'terminal_id' => $input['terminal']['id'],
            ]);

        $response = $this->sendGatewayRequest($request);

        //
        // Adding this check because gateway is sending 421 in the verify refund responses
        // for refunds of certain terminals, if this is passed to scrooge, we hit the refund API once
        // On subsequent receipt of this status, scrooge marks the refund as a hard failure which needs
        // manual intervention
        //
        if ($response->status_code == 421)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                                   ->toArray();
        }

        $crypto = $this->getResponseDecryptor();

        $decryptedResponse = $crypto->decryptString($response->body);

        $this->checkDecryptionFailure($decryptedResponse, $response->body);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE,
            [
                'response'    => $decryptedResponse,
                'gateway'     => $this->gateway,
                'payment_id'  => $input['payment']['id'],
                'refund_id'   => $input['refund']['id'],
                'terminal_id' => $input['terminal']['id'],
            ]);

        $xmlResponse = (array) simplexml_load_string(trim($decryptedResponse));

        $verifyRefundResponseArray = json_decode(json_encode($xmlResponse), true);

        $scroogeResponse->setGatewayVerifyResponse($decryptedResponse);

        $processedVerifyResponse = $this->processVerifyRefundResponse($verifyRefundResponseArray, $input['refund']['id']);

        if ($processedVerifyResponse === false)
        {
            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(true)
                               ->setGatewayKeys($this->getGatewayVerifyRefundData($processedVerifyResponse))
                               ->toArray();
    }

    protected function verifyAmountMismatch(Base\Verify $verify, array $input, array $response, string $entity)
    {
        $expectedAmount = $this->getFormattedAmount($input[$entity]['amount']);
        $actualAmount   = $this->getFormattedAmount($response[VerifyResponseFields::AMOUNT] * 100);

        $verify->amountMismatch = ($expectedAmount !== $actualAmount);
    }

    protected function checkGatewaySuccess(Base\Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ($content[VerifyResponseFields::STATUS] === Status::VERIFY_SUCCESS)
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function saveVerifyContent(Base\Verify $verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $attributes = $this->getVerifyAttributes($content, $gatewayPayment);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function traceGatewayPaymentRequest( // nosemgrep : razorpay:sbb_101
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        unset($request['content'][AuthRequestFields::PASSWORD]);
        unset($request['content'][RefundRequestFields::PASSWORD]);
        unset($request['content'][AuthRequestFields::CUSTOMER_ACCOUNT]);

        parent::traceGatewayPaymentRequest($request, $input, $traceCode);
    }

    protected function getAcquirerData($input, $gatewayPayment)
    {
        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $gatewayPayment->getBankPaymentId()
            ]
        ];
    }

    protected function getRefundAttributes(array $content, $dateTimestamp)
    {
        $attributes = [
            Entity::ERROR_CODE         => $content[RefundResponseFields::STATUS_CODE],
            Entity::ERROR_DESCRIPTION  => $content[RefundResponseFields::STATUS_MESSAGE],
            Entity::GATEWAY_PAYMENT_ID => $content[RefundResponseFields::TRANSACTION_ID],
            Entity::RECEIVED           => true,
            Entity::DATE               => $dateTimestamp,
        ];

        $attributes[Entity::SUCCESS] = false;

        if (($attributes[Entity::ERROR_CODE] === Status::FULL_REFUND_SUCCESS) or
            ($attributes[Entity::ERROR_CODE] === Status::PARTIAL_REFUND_SUCCESS))
        {
            $attributes[Entity::SUCCESS] = true;
        }

        return $attributes;
    }

    protected function getRefundAttributesFromVerify(array $content)
    {
        $attributes = [
            Entity::ERROR_CODE            => $content[VerifyRefundFields::ERRORCODE],
            Entity::ERROR_DESCRIPTION     => $content[VerifyRefundFields::MESSAGE],
            Entity::GATEWAY_PAYMENT_ID    => $content[VerifyRefundFields::DETAILS][VerifyRefundFields::REFUND]
                                                     [VerifyRefundFields::TXN_ID],
            Entity::RECEIVED              => true,
            Entity::SUCCESS               => true,
            Entity::STATUS                => Status::SUCCESS,
        ];

        return $attributes;
    }

    protected function getVerifyRefundRequestData($input, $gatewayPayment)
    {
        $data = [
            VerifyRefundFields::LOGIN      => $this->getMerchantId(),
            VerifyRefundFields::ENC_DATA   => $this->getVerifyRefundEncryptData($gatewayPayment),
            VerifyRefundFields::REFUND_ID  => $input['refund']['id']
        ];

        return $data;
    }

    protected function getVerifyRefundEncryptData($gatewayPayment)
    {
        $data = [
            VerifyRefundFields::TRANSACTION_ID   => $gatewayPayment['gateway_payment_id'],
            VerifyRefundFields::MERCHANT_ID      => $this->getMerchantId(),
            VerifyRefundFields::PRODUCT          => $this->getAccessCode(),
        ];

        $data = $this->getStringToEncrypt($data);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_VERIFY_REQUEST_CONTENT,
            [
                'content'   => $data,
                'gateway'   => $this->gateway,
            ]);

        $crypto = $this->getRequestEncryptor();

        $encdata = $crypto->encryptString($data);

        $encdata = strtoupper($encdata);

        return $encdata;
    }

    /**
     * This is because the string for encyption is different for UAT and production environment
     */
    protected function getStringToEncrypt($data)
    {
        $separator = '&';

        if ($this->getMode() === Mode::TEST)
        {
            $separator = '|';
        }

        $data = http_build_query($data, null, $separator);

        return $data;
    }

    public function getRequestEncryptor()
    {
        $masterKey = $this->getRequestEncryptionKey();

        $salt = $this->getMerchantId();

        return new AESCrypto($masterKey, $salt);
    }

    public function getResponseDecryptor()
    {
        $masterKey = $this->getResponseDecryptionKey();

        $salt = $this->getMerchantId();

        return new AESCrypto($masterKey, $salt);
    }

    protected function checkDecryptionFailure($decryptedResponse, $encryptedString)
    {
        if ($decryptedResponse === false)
        {
            $this->trace->error(
                TraceCode::PAYMENT_VERIFY_REFUND_FAILURE,
                [
                    'encrypted_string' => $encryptedString,
                    'gateway'          => $this->gateway,
                ]);

            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
                '',
                ErrorCode::GATEWAY_ERROR_DECRYPTION_FAILED,
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => '',
                    Payment\Gateway::GATEWAY_KEYS      => [
                        'gateway' => $this->gateway,
                    ]
                ]);
        }
    }

    protected function getRefundRequestContent(Entity $gatewayPayment, array $input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $content = [
            RefundRequestFields::MERCHANT_ID            => $this->getMerchantId(),
            RefundRequestFields::PASSWORD               => base64_encode($this->getSecureSecret()),
            RefundRequestFields::GATEWAY_TRANSACTION_ID => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
            RefundRequestFields::REFUND_AMOUNT          => $this->getFormattedAmount($input['refund']['amount']),
            RefundRequestFields::TRANSACTION_DATE       => $this->getFormattedDate($input['payment']['created_at']),
            RefundRequestFields::REFUND_ID              => $input['refund']['id'],
        ];

        return $content;
    }

    protected function getVerifyAttributes(array $content, Entity $gatewayPayment)
    {
        $attributes = [
            Entity::STATUS => Status::FAILURE,
        ];

        if ($content[VerifyResponseFields::STATUS] === Status::VERIFY_SUCCESS)
        {
            $attributes[Entity::STATUS] = Status::SUCCESS;

            if ((empty($gatewayPayment[Entity::GATEWAY_PAYMENT_ID]) === false) and
                (trim($gatewayPayment[Entity::GATEWAY_PAYMENT_ID]) !== trim($content[VerifyResponseFields::GATEWAY_TRANSACTION_ID])))
            {
                throw new Exception\LogicException(
                    'Gateway Payment ID Mismatch',
                    ErrorCode::SERVER_ERROR_GATEWAY_FIELD_MISMATCH,
                    [
                        'payment_id'         => $gatewayPayment[Entity::PAYMENT_ID],
                        'gateway_payment_id' => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
                        'atomtxnId'          => $content[VerifyResponseFields::GATEWAY_TRANSACTION_ID],
                        'gateway'            => $this->gateway,
                    ]
                );
            }

            if ((empty($gatewayPayment[Entity::BANK_PAYMENT_ID]) === false) and
                (trim($gatewayPayment[Entity::BANK_PAYMENT_ID]) !== trim($content[VerifyResponseFields::BANK_TRANSACTION_ID])))
            {
                throw new Exception\LogicException(
                    'Bank Payment ID Mismatch',
                    ErrorCode::SERVER_ERROR_GATEWAY_FIELD_MISMATCH,
                    [
                        'payment_id'      => $gatewayPayment[Entity::PAYMENT_ID],
                        'bank_payment_id' => $gatewayPayment[Entity::BANK_PAYMENT_ID],
                        'bid'             => $content[VerifyResponseFields::BANK_TRANSACTION_ID],
                        'gateway'         => $this->gateway,
                    ]
                );
            }

            $attributes[Entity::GATEWAY_PAYMENT_ID] = trim($content[VerifyResponseFields::GATEWAY_TRANSACTION_ID]);

            $attributes[Entity::BANK_PAYMENT_ID] = trim($content[VerifyResponseFields::BANK_TRANSACTION_ID]);
        }

        return $attributes;
    }

    protected function getVerifyRequestData(Base\Verify $verify)
    {
        $input = $verify->input;

        $gatewayPayment = $verify->payment;

        $date = $input['payment']['created_at'];

        $data = [
            VerifyRequestFields::MERCHANT_ID      => $this->getMerchantId(),
            VerifyRequestFields::TRANSACTION_ID   => $input['payment']['id'],
            VerifyRequestFields::AMOUNT           => $this->getFormattedAmount($input['payment']['amount']),
            VerifyRequestFields::TRANSACTION_DATE => $this->getFormattedDate($date),
        ];

        return $data;
    }

    protected function getFormattedAmount(float $amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    public function getMerchantId()
    {
        $merchantId = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID];

        if ($this->mode === Mode::TEST)
        {
            $merchantId = $this->config['test_merchant_id'];
        }

        return $merchantId;
    }

    public function getSecureSecret()
    {
        $secureSecret = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET];

        if ($this->mode === Mode::TEST)
        {
            $secureSecret = $this->config['test_secure_password'];
        }

        return $secureSecret;
    }

    public function getAccessCode()
    {
        $accessCode = $this->terminal[Terminal\Entity::GATEWAY_ACCESS_CODE];

        if ($this->mode === Mode::TEST)
        {
            $accessCode = $this->config['test_access_code'];
        }

        return $accessCode;
    }

    public function getRequestEncryptionKey()
    {
        if ($this->getMode() === Mode::TEST)
        {
            $encryptionKey = $this->config['test_request_encryption_key'];
        }
        else
        {
            $key = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET2];

            $keyArray = explode('|', $key);

            $encryptionKey = $keyArray[0];
        }

        return $encryptionKey;
    }

    public function getResponseDecryptionKey()
    {
        if ($this->getMode() === Mode::TEST)
        {
            $decryptionKey = $this->config['test_response_encryption_key'];
        }
        else
        {
            $key = $this->terminal[Terminal\Entity::GATEWAY_SECURE_SECRET2];

            $keyArray = explode('|', $key);

            $decryptionKey = $keyArray[1];
        }

        return $decryptionKey;
    }

    protected function getAuthRequestContentArray(array $input)
    {
        $payment = $input['payment'];

        $udf9 = $input['merchant']->getFilteredDba() . "|" . $input['merchant']->getCategory();

        $content = [
            AuthRequestFields::LOGIN                      => $this->getMerchantId(),
            AuthRequestFields::PASSWORD                   => $this->getSecureSecret(),
            AuthRequestFields::TRANSACTION_TYPE           => Constants::NETBANKING_FUND_TRANSFER,
            AuthRequestFields::PRODUCT_ID                 => $this->getAccessCode(),
            AuthRequestFields::AMOUNT                     => $this->getFormattedAmount($payment['amount']),
            AuthRequestFields::TRANSACTION_CURRENCY       => Currency::INR,
            AuthRequestFields::TRANSACTION_SERVICE_CHARGE => Constants::SERVICE_CHARGE,
            AuthRequestFields::CLIENT_CODE                => Constants::CONSTANT_CLIENT_CODE,
            AuthRequestFields::TRANSACTION_ID             => $payment[Payment\Entity::ID],
            AuthRequestFields::DATE                       => $this->getFormattedDate($payment[Payment\Entity::CREATED_AT]),
            AuthRequestFields::CUSTOMER_ACCOUNT           => Constants::CUST_ACC_NO,
            AuthRequestFields::RETURN_URL                 => $input['callbackUrl'],
            AuthRequestFields::BANK_ID                    => $this->getBankId($payment['bank']),
            AuthRequestFields::UDF9                       => $udf9,
        ];

        $this->checkTpv($input, $content);

        $content[AuthRequestFields::SIGNATURE] = $this->getHashOfArray($content);

        return $content;
    }

    protected function checkTpv($input, &$content)
    {
        if ($input['merchant']->isTPVRequired() === true)
        {
            if (isset($input['order']['account_number']) === false)
            {
                throw new Exception\LogicException(
                    'Bank account number should have been present');
            }

            $content[AuthRequestFields::CUSTOMER_ACCOUNT] = $input['order']['account_number'];
        }
    }

    protected function getBankId(string $bankIfsc)
    {
        $bankId = Bank::getCode($bankIfsc);

        return ($this->mode === Mode::TEST) ? Bank::ATOM : $bankId;
    }

    protected function saveCallbackContent(array $input, array $content)
    {
        $attributes = $this->getCallbackAttributes($content);

        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'], Action::AUTHORIZE);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->saveOrFail();

        return $gatewayPayment;
    }

    protected function createGatewayPaymentEntity(array $attributes, $action = null)
    {
        $entity = $this->getNewGatewayPaymentEntity();
        $input = $this->input;

        $action = $action ?: $this->action;

        $entity->setPaymentId($input['payment']['id']);

        if ($action === Action::REFUND)
        {
            $entity->setRefundId($input['refund']['id']);

            $entity->setAmount($input['refund']['amount']);
        }
        else
        {
            $entity->setAmount($input['payment']['amount']);
        }

        $entity->setAction($action);

        if (($action === Action::AUTHORIZE) and
            ($input['merchant']->isTPVRequired()))
        {
            $entity->setAccountNumber($input['order']['account_number']);
        }

        $entity->fill($attributes);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function getAuthorizeAttributes(array $input)
    {
        $payment = $input['payment'];

        $attributes = [
            Entity::BANK_CODE  => Bank::getCode($payment['bank']),
        ];

        return $attributes;
    }

    protected function getCallbackAttributes(array $content)
    {
        $content['date'] = Carbon::parse($content['date'])->format('d-m-Y');

        $timestamp = Carbon::createFromFormat('d-m-Y', $content['date'], Timezone::IST)->timestamp;

        $attributes = [
            Entity::ERROR_DESCRIPTION  => 'NA',
            Entity::GATEWAY_PAYMENT_ID => $content[AuthResponseFields::GATEWAY_PAYMENT_ID],
            Entity::BANK_PAYMENT_ID    => $content[AuthResponseFields::BANK_TRANSACTION_ID],
            Entity::STATUS             => $content[AuthResponseFields::STATUS_CODE],
            Entity::RECEIVED           => true,
            Entity::BANK_NAME          => $content[AuthResponseFields::BANK_NAME],
            Entity::DATE               => $timestamp,
        ];

        if ($attributes[Entity::STATUS] === Status::SUCCESS)
        {
            $attributes[Entity::SUCCESS] = true;
        }

        return $attributes;
    }

    /*
     * Overrides the default method contained in Base/Gateway
     */
    public function getHashOfString($string)
    {
        $secret = $this->getSecret();

        return hash_hmac(HashAlgo::SHA512, $string, $secret);
    }

    public function getTestSecret()
    {
        assertTrue ($this->mode === Mode::TEST);

        if ($this->action === Action::AUTHORIZE)
        {
            $secret = $this->config['test_authorize_hash_secret'];
        }
        else if ($this->action === Action::CALLBACK)
        {
            $secret = $this->config['test_callback_hash_secret'];
        }

        return $secret;
    }

    public function getLiveSecret()
    {
        if ($this->action === Action::AUTHORIZE)
        {
            $secret = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];
        }
        else if ($this->action === Action::CALLBACK)
        {
            $secret = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD2];
        }

        return $secret;
    }

    /**
     * Overrides the default method contained in Base/Gateway
     */
    protected function getStringToHash($content, $glue = '')
    {
        if ($this->action === Action::AUTHORIZE)
        {
            $content = $this->getAuthorizeRequestHashArray($content);
        }
        else
        {
            $content = $this->getCallbackResponseHashArray($content);
        }

        return implode($glue, $content);
    }

    protected function getAuthorizeRequestHashArray(array $content)
    {
        $hashArray = [
            $content[AuthRequestFields::LOGIN],
            $content[AuthRequestFields::PASSWORD],
            $content[AuthRequestFields::TRANSACTION_TYPE],
            $content[AuthRequestFields::PRODUCT_ID],
            $content[AuthRequestFields::TRANSACTION_ID],
            $content[AuthRequestFields::AMOUNT],
            $content[AuthRequestFields::TRANSACTION_CURRENCY],
        ];

        return $hashArray;
    }

    protected function getCallbackResponseHashArray(array $content)
    {
        $hashArray = [
            $content[AuthResponseFields::GATEWAY_PAYMENT_ID],
            $content[AuthResponseFields::TRANSACTION_ID],
            $content[AuthResponseFields::STATUS_CODE],
            $content[AuthResponseFields::PRODUCT_ID],
            $content[AuthResponseFields::DISCRIMINATOR],
            $content[AuthResponseFields::AMOUNT],
            $content[AuthResponseFields::BANK_TRANSACTION_ID],
        ];

        return $hashArray;
    }

    protected function getFormattedDate($timestamp)
    {
        $format = DateFormat::ACTION_MAP[$this->action];

        $date = Carbon::createFromTimestamp($timestamp, Timezone::IST)->format($format);

        return $date;
    }

    protected function verifyResponseXmlToArray(string $response): array
    {
        $array = (array) simplexml_load_string(trim($response));

        return $array['@attributes'];
    }

    protected function createRedirectUrl(array $data)
    {
        // Cannot use http_build_query php function because
        // params contain '%' sign which gets messed up by that function
        $query = $this->httpBuildQuery($data);

        $url = $this->getUrl() . '?' . $query;

        // This is the url to which the customer is redirected.
        // Here, on atom's provided url, the bank choice is auto-submitted
        // and bank login page comes. When customer logins and bank txn is complete,
        // it's redirected to atom's site and then redirected back to our callbackUrl
        // we provided earlier via 'ru' field.
        // Courtesy :- SHK _/\_
        return $url;
    }

    protected function httpBuildQuery(array $data)
    {
        foreach ($data as $key => $value)
        {
            $arr[] = $key . '=' . $value;
        }

        return implode('&', $arr);
    }

    protected function checkRefundSuccess(array $responseArray)
    {
        if (($responseArray[RefundResponseFields::STATUS_CODE] !== Status::FULL_REFUND_SUCCESS) and
            ($responseArray[RefundResponseFields::STATUS_CODE] !== Status::PARTIAL_REFUND_SUCCESS))
        {
            $responseCode = $responseArray[RefundResponseFields::STATUS_CODE];

            $desc = $responseArray[RefundResponseFields::STATUS_MESSAGE];

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }

    protected function isEarlyDayTransaction($paymentCreatedAt) : bool
    {
        $paymentTime = Carbon::createFromTimestamp($paymentCreatedAt, Timezone::IST);

        return ($paymentTime->secondsSinceMidnight() <= self::TIME_WINDOW);
    }

    protected function getPreviousDate($originalDate)
    {
        return date('Y-m-d', strtotime('-1 day',strtotime($originalDate)));
    }

    /**
     * Overrides getRelativeUrl of base class as urls for test and live mode in verify refund are different.
     */
    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        if ($type === 'VERIFY_REFUND')
        {
            $mode = strtoupper($this->mode);

            return constant($ns. '\Url::' . $type . '_' . $mode);
        }

        return constant($ns . '\Url::' . $type);
    }

    protected function getRefundResponse(array $content, $input)
    {
        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        return $response->body;
    }

    protected function getVerifyResponse(array $content, $verify)
    {
        $request = $this->getStandardRequestArray($content, 'get');

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'gateway'     => $this->gateway,
                'payment_id'  => $verify->input['payment']['id'],
                'request'     => $request,
            ]);

        $request['url'] = $this->createRedirectUrl($request['content']);

        $request['content'] = [];

        $response = $this->sendGatewayRequest($request);

        $responseArray = $this->verifyResponseXmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $responseArray,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        return $responseArray;
    }

    protected function checkRefundDateError($responseArray)
    {
        $originalDate = $this->input['payment']['created_at'];

        if (isset($responseArray[RefundResponseFields::STATUS_MESSAGE]) and
            ($responseArray[RefundResponseFields::STATUS_MESSAGE] === self::REFUND_DATE_ERROR_MESSAGE) and
            ($this->isEarlyDayTransaction($originalDate) === true))
        {
            return true;
        }

        return false;
    }

    protected function checkVerifyDateError($responseArray, $paymentCreatedAt)
    {
        if (($responseArray['VERIFIED'] === 'NODATA') and
            ($this->isEarlyDayTransaction($paymentCreatedAt) === true))
        {
            return true;
        }

        return false;
    }

    protected function getGatewayData(array $refundFields = [])
    {
        if (empty($refundFields) === false)
        {
            return [
                RefundResponseFields::MERCHANT_ID    => $refundFields[RefundResponseFields::MERCHANT_ID] ?? null,
                RefundResponseFields::STATUS_CODE    => $refundFields[RefundResponseFields::STATUS_CODE] ?? null,
                RefundResponseFields::TRANSACTION_ID => $refundFields[RefundResponseFields::TRANSACTION_ID] ?? null,
                RefundResponseFields::STATUS_MESSAGE => $refundFields[RefundResponseFields::STATUS_MESSAGE] ?? null,
            ];
        }
        return [];
    }

    protected function getGatewayVerifyRefundData(array $verifyRefundFields = [])
    {
        if (empty($verifyRefundFields) === false)
        {
            return [
                VerifyRefundFields::ERRORCODE            => $verifyRefundFields[VerifyRefundFields::ERRORCODE] ?? null,
                VerifyRefundFields::MESSAGE              => $verifyRefundFields[VerifyRefundFields::MESSAGE] ?? null,
                VerifyRefundFields::TXN_ID               => $verifyRefundFields[VerifyRefundFields::TXN_ID] ?? null,
                VerifyRefundFields::PRODUCT              => $verifyRefundFields[VerifyRefundFields::PRODUCT] ?? null,
                VerifyRefundFields::REFUND_INITIATE_DATE =>
                    $verifyRefundFields[VerifyRefundFields::REFUND_INITIATE_DATE] ?? null,
                VerifyRefundFields::REFUNDPROCESSDATE    =>
                    $verifyRefundFields[VerifyRefundFields::REFUNDPROCESSDATE] ?? null,
                VerifyRefundFields::REMARKS              => $verifyRefundFields[VerifyRefundFields::REMARKS] ?? null,
                VerifyRefundFields::MEREFUNDREF          => $verifyRefundFields[VerifyRefundFields::MEREFUNDREF] ?? null,
            ];
        }
        return [];
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
                          $input['payment']['id'],
                          Payment\Action::AUTHORIZE);

        // If it's already authorized on gateway side, We just return back.
        if (($gatewayPayment->getReceived() === true) and
            ($gatewayPayment->getStatus() === Status::SUCCESS))
        {
            return true;
        }

        if (empty($input['gateway']['acquirer']['reference1']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $attributes = [
            Entity::STATUS             => Status::SUCCESS,
            Entity::GATEWAY_PAYMENT_ID => $input['gateway']['gateway_payment_id'],
            Entity::BANK_PAYMENT_ID    => $input['gateway']['acquirer']['reference1'],
        ];

        $gatewayPayment->fill($attributes);
        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }
}
