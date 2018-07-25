<?php

namespace RZP\Gateway\Atom;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
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

        $request = $this->makeRequestAndGetFormData($request);

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

        if (isset($content[AuthResponseFields::AMOUNT],
                  $content[AuthResponseFields::TRANSACTION_ID],
                  $content[AuthResponseFields::STATUS_CODE]) == false)
        {
            throw Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                null,
                null,
                $input);
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

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayPaymentResponse($response->body, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $responseArray = $this->xmlToArray($response->body);

        $attributes = $this->getRefundAttributes($responseArray, $input);

        $this->createGatewayPaymentEntity($attributes);

        $this->checkRefundSuccess($responseArray);
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

        if ($response->status_code === 421)
        {
            throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL);
        }

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

    protected function sendPaymentVerifyRequest($verify)
    {
        $content = $this->getVerifyRequestData($verify);

        $request = $this->getStandardRequestArray($content, 'get');

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $request);

        $request['url'] = $this->createRedirectUrl($request['content']);

        $request['content'] = [];

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $response->body,
                'payment_id' => $verify->input['payment']['id'],
            ]);

        $responseArray = $this->verifyResponseXmlToArray($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'gateway'    => $this->gateway,
                'response'   => $responseArray,
                'payment_id' => $verify->input['payment']['id'],
            ]);

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

    public function verifyRefund(array $input)
    {
        parent::verify($input);

        $unprocessedRefunds = $this->getUnprocessedRefunds();

        $processedRefunds = $this->getProcessedRefunds();

        if (in_array($input['refund']['id'], $unprocessedRefunds) === true)
        {
            return false;
        }

        if (in_array($input['refund']['id'], $processedRefunds) === true)
        {
            return true;
        }

        throw new Exception\LogicException(
            'Verify refund not implemented',
            null,
            [
                'gateway'   => 'atom',
                'refund_id' => $input['refund']['id'],
            ]);
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

    protected function traceGatewayPaymentRequest(
        array $request,
        $input,
        $traceCode = TraceCode::GATEWAY_PAYMENT_REQUEST)
    {
        unset($request['content'][AuthRequestFields::PASSWORD]);
        unset($request['content'][RefundRequestFields::PASSWORD]);

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

    protected function getRefundAttributes(array $content)
    {
        $attributes = [
            Entity::ERROR_CODE         => $content[RefundResponseFields::STATUS_CODE],
            Entity::ERROR_DESCRIPTION  => $content[RefundResponseFields::STATUS_MESSAGE],
            Entity::GATEWAY_PAYMENT_ID => $content[RefundResponseFields::TRANSACTION_ID],
            Entity::RECEIVED           => true,
        ];

        $attributes[Entity::SUCCESS] = false;

        if ($attributes[Entity::ERROR_CODE] === Status::REFUND_SUCCESS)
        {
            $attributes[Entity::SUCCESS] = true;
        }

        return $attributes;
    }

    protected function getRefundRequestContent(Entity $gatewayPayment, array $input)
    {
        $content = [
            RefundRequestFields::MERCHANT_ID            => $this->getMerchantId(),
            RefundRequestFields::PASSWORD               => base64_encode($this->getSecureSecret()),
            RefundRequestFields::GATEWAY_TRANSACTION_ID => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
            RefundRequestFields::REFUND_AMOUNT          => $this->getFormattedAmount($input['refund']['amount']),
            RefundRequestFields::TRANSACTION_DATE       => $this->getFormattedDate($input['payment'][Payment\Entity::CREATED_AT]),
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
                ($gatewayPayment[Entity::GATEWAY_PAYMENT_ID] !== $content[VerifyResponseFields::GATEWAY_TRANSACTION_ID]))
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
                ($gatewayPayment[Entity::BANK_PAYMENT_ID] !== $content[VerifyResponseFields::BANK_TRANSACTION_ID]))
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

            $attributes[Entity::GATEWAY_PAYMENT_ID] = $content[VerifyResponseFields::GATEWAY_TRANSACTION_ID];

            $attributes[Entity::BANK_PAYMENT_ID] = $content[VerifyResponseFields::BANK_TRANSACTION_ID];
        }

        return $attributes;
    }

    protected function getVerifyRequestData(Base\Verify $verify)
    {
        $input = $verify->input;

        $data = [
            VerifyRequestFields::MERCHANT_ID      => $this->getMerchantId(),
            VerifyRequestFields::TRANSACTION_ID   => $input['payment']['id'],
            VerifyRequestFields::AMOUNT           => $this->getFormattedAmount($input['payment']['amount']),
            VerifyRequestFields::TRANSACTION_DATE => $this->getFormattedDate($input['payment']['created_at']),
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

    protected function getAuthRequestContentArray(array $input)
    {
        $payment = $input['payment'];

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

    protected function createGatewayPaymentEntity(array $attributes)
    {
        $entity = $this->getNewGatewayPaymentEntity();
        $input = $this->input;

        $entity->setPaymentId($input['payment']['id']);

        if ($this->action === Action::REFUND)
        {
            $entity->setRefundId($input['refund']['id']);

            $entity->setAmount($input['refund']['amount']);
        }
        else
        {
            $entity->setAmount($input['payment']['amount']);
        }

        $entity->setAction($this->action);

        if (($this->action === Action::AUTHORIZE) and
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
        $attributes = [
            Entity::ERROR_DESCRIPTION  => 'NA',
            Entity::GATEWAY_PAYMENT_ID => $content[AuthResponseFields::GATEWAY_PAYMENT_ID],
            Entity::BANK_PAYMENT_ID    => $content[AuthResponseFields::BANK_TRANSACTION_ID],
            Entity::STATUS             => $content[AuthResponseFields::STATUS_CODE],
            Entity::RECEIVED           => true,
            Entity::BANK_NAME          => $content[AuthResponseFields::BANK_NAME],
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
        assert ($this->mode === Mode::TEST);

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
        if ($responseArray[RefundResponseFields::STATUS_CODE] !== Status::REFUND_SUCCESS)
        {
            $responseCode = $responseArray[RefundResponseFields::STATUS_CODE];

            $desc = $responseArray[RefundResponseFields::STATUS_MESSAGE];

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }
}
