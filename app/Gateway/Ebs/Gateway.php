<?php

namespace RZP\Gateway\Ebs;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Card;
use RZP\Gateway\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Ebs\RequestConstants as Req;
use RZP\Gateway\Ebs\ResponseConstants as Resp;
use Symfony\Component\DomCrawler\Crawler;

class Gateway extends Base\Gateway
{
    const HASH_ALGO    = 'SHA512';
    const MERCHANT_ID  = 'test_merchant_id';
    const HASH_SECRET  = 'test_hash_secret';

    const API          = 'api';

    protected $gateway = 'ebs';

    protected $sortRequestContent = true;

    public function authorize(array $input)
    {
        parent::authorize($input);

        $content = $this->getAuthRequestContentArray($input);

        $attributes = $this->getAuthorizeAttributesForPaymentEntity($content);

        $payment = $this->createGatewayPaymentEntity($attributes, $input);

        $request = $this->getStandardRequestArray($content);

        $this->traceGatewayPaymentRequest($request, $input);

        if ($input['merchant']['id'] === '4izmfM9TFCAgFN')
        {
            if ($input['payment']['method'] === Payment\Method::NETBANKING)
            {
                $request = $this->makeRequestAndGetBankUrl($request);
            }
        }

        return $request;
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndAction(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        assert(($gatewayPayment[Entity::ERROR_CODE] === null) or
               ($gatewayPayment[Entity::ERROR_CODE] === '0'));
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_CALLBACK,
            ['gateway' => $input['gateway']]);

        $this->validateCallbackGetSecureHash($input['gateway'], $input['terminal']);

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndActionOrFail(
            $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attributes = $this->getGatewayEntityDataFromResponse($input);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        if (isset($input['gateway'][Resp::RESPONSE_CODE]) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR);
        }
        else if ($input['gateway'][Resp::RESPONSE_CODE] !== Status::SUCCESS)
        {
            //
            // Payment fails, throw exception
            //
            $responseCode = $input['gateway'][Resp::RESPONSE_CODE];

            $desc = '';

            if (isset(ResponseCode::$codes[$responseCode]))
            {
                $desc = ResponseCode::$codes[$responseCode];
            }

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $gatewayPayment = $this->getRepo()->findByPaymentIdAndAction(
                                $input['payment'][Payment\Entity::ID], Action::AUTHORIZE);

        $attributes = $this->sendRefundGatewayRequest($gatewayPayment, $input);

        $refundEntity = $this->createGatewayPaymentEntity($attributes, $input);

        if ((isset($refundEntity[Entity::ERROR_CODE]) and
            ($refundEntity[Entity::ERROR_CODE] !== '0')))
        {
            $responseCode = $refundEntity[Entity::ERROR_CODE];

            $desc = '';

            if (isset(ResponseCode::$codes[$responseCode]))
            {
                $desc = ResponseCode::$codes[$responseCode];
            }

            throw new Exception\GatewayErrorException(
                ResponseCode::getMappedCode($responseCode),
                $responseCode,
                $desc);
        }
    }

    public function getPaymentIdFromServerCallback($input)
    {
        $msg = $input['msg'];

        return $msg[Resp::MERCHANT_REF_NO];
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function getRequest($location, $method, $content)
    {
        $request = [
            'url'       => $location,
            'method'    => $method,
            'content'   => $content,
        ];

        return $request;
    }
    protected function getRequestFromResponse302($response)
    {
        $cookies = [];

        foreach ($response->cookies as $cookie)
        {
            $cookies[$cookie->name] = $cookie->value;
        }

        $location = $response->headers->getValues('location')[0];

        if ($location === null)
        {
            throw new Exception\GatewayTimeoutException('Gateway Timed Out', null, true);
        }

        $request = $this->getRequest($location, 'get', '');

        $request['options']['cookies'] = $cookies;

        $this->setRequestHeaderAndOption($request);

        return $request;
    }

    protected function getRequestFromFormPostResponse($request, $response)
    {
        $crawler = new Crawler($response->body, $request['url']);

        $formCrawler = $crawler->filter('form');

        if ($formCrawler->count() === 0)
        {
            throw new Exception\GatewayTimeoutException('Gateway Timed Out', null, true);
        }

        $form = $formCrawler->form();

        $method = $form->getMethod();

        $request = [
            'url' => $form->getUri(),
            'method' => strtolower($method),
            'content' => $form->getValues(),
        ];

        $this->setRequestHeaderAndOption($request);

        return $request;
    }

    protected function setRequestHeaderAndOption(& $request)
    {
        $request['options']['follow_redirects'] = false;

        $request['headers']['Referer'] = 'https://api.razorpay.com/v1/payments';
    }

    protected function sendFirstGatewayRequestForEbsAuthorize($request)
    {
        return $this->sendGatewayRequest($request);
    }

    protected function sendSecondGatewayRequestForEbsAuthorize($request)
    {
        return $this->sendGatewayRequest($request);
    }

    protected function sendThirdGatewayRequestForEbsAuthorize($request)
    {
        return $this->sendGatewayRequest($request);
    }

    protected function makeRequestAndGetBankUrl($request)
    {
        $this->setRequestHeaderAndOption($request);

        // This is the first redirect (302). We receive headers and cookies in this response
        // which needs to be sent to the second redirect request.
        $response302 = $this->sendFirstGatewayRequestForEbsAuthorize($request);

        $secondRedirectRequest = $this->getRequestFromResponse302($response302);

        // This is the second redirect (form post). The response of this is passed on to the third redirect request.
        $secondRedirectResponse = $this->sendSecondGatewayRequestForEbsAuthorize($secondRedirectRequest);

        $lastRedirectRequest = $this->getRequestFromFormPostResponse($secondRedirectRequest, $secondRedirectResponse);

        // Makes the last redirect request before the request to bank's ACS url is made by the checkout.
        $lastRedirectResponse = $this->sendThirdGatewayRequestForEbsAuthorize($lastRedirectRequest);

        $authorizeRequest = $this->getAuthorizeRequestFromLastRedirectResponse(
            $lastRedirectRequest, $lastRedirectResponse);

        return $authorizeRequest;
    }

    protected function getAuthorizeRequestFromLastRedirectResponse($request, $response)
    {
        //
        // If location is set, then we should redirect to Bank page
        // Else we should crawl the page to get form post
        //
        $loc = $response->headers->getValues('location');

        if (empty($loc) === false)
        {
            $authorizeRequest = $this->getRequestFromResponse302($response);
        }
        else
        {
            $authorizeRequest = $this->getRequestFromFormPostResponse($request, $response);
        }

        return $authorizeRequest;
    }

    protected function sendRefundGatewayRequest($gatewayPayment, $input)
    {
        $content = $this->getPaymentRefundRequestContent($gatewayPayment, $input);

        $request = $this->getStandardRequestArray($content);

        $response = $this->sendGatewayRequest($request);
        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [$response->body]);

        $refundResponse = $this->parseResponseXml($response->body);

        return $this->getRefundContent($refundResponse, $input);
    }

    protected function verifyPayment($verify)
    {
        $gatewayPayment = $verify->payment;
        $content = $verify->verifyResponseContent;
        $input = $verify->input;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->gatewaySuccess = $this->getVerifyGatewayStatus($content);

        $verify->apiSuccess = $this->getVerifyApiStatus($gatewayPayment, $input);

        if ($verify->apiSuccess !== $verify->gatewaySuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContentIfNeeded($gatewayPayment, $content);

        return $verify->status;
    }

    protected function getVerifyGatewayStatus($content)
    {
        if ((isset($content[Resp::ERROR_CODE]) and
            ($content[Resp::ERROR_CODE] !== '0')))
        {
            $gatewayStatus = false;
        }
        else if (isset($content[Resp::API_TRANSACTION_TYPE]))
        {
            if ($content[Resp::API_TRANSACTION_TYPE] === Status::API_AUTHORIZED)
            {
                $gatewayStatus = true;
            }
            else if (($content[Resp::API_TRANSACTION_TYPE] === Status::API_AUTHORIZE_FAILED) or
                     ($content[Resp::API_TRANSACTION_TYPE] === Status::API_AUTHORIZE_INCOMPLETE))
            {
                $gatewayStatus = false;
            }
            else
            {
                $gatewayStatus = false;

                $this->trace->warning(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    $content);
            }
        }
        else
        {
            $gatewayStatus = false;

            $this->trace->warning(
                TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                $content);
        }

        return $gatewayStatus;
    }

    protected function getVerifyApiStatus($gatewayPayment, $input)
    {
        if (($input['payment']['status'] === 'failed') or
            ($input['payment']['status'] === 'created'))
        {
            $apiStatus = false;

            if (($gatewayPayment['received'] === true) or
                ($gatewayPayment['status'] === Status::AUTHORIZED))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'gateway_payment'   => $gatewayPayment,
                        'payment'           => $input['payment']
                    ]);
            }
        }
        else
        {
            $apiStatus = true;

            if (($gatewayPayment['received'] === false) or
                ($gatewayPayment['status'] !== Status::AUTHORIZED))
            {
                $this->trace->info(
                    TraceCode::GATEWAY_PAYMENT_VERIFY_UNEXPECTED,
                    [
                        'gateway_payment'   => $gatewayPayment,
                        'payment'           => $input['payment']
                    ]);
            }
        }

        return $apiStatus;
    }

    protected function saveVerifyContentIfNeeded($gatewayPayment, $response)
    {
        if (isset($response[Resp::API_TRANSACTION_ID]))
        {
            $attributes = $this->getVerifyContents($response);

            if ($gatewayPayment['received'] === false)
            {
                $gatewayPayment->fill($attributes);
                $this->repo->saveOrFail($gatewayPayment);
            }
        }

        $this->action = Action::VERIFY;

        return $gatewayPayment;
    }

    protected function getVerifyContents($content)
    {
        $isFlagged = false;

        if ((isset($content[Resp::API_IS_FLAGGED])) and
            (strtolower($content[Resp::API_IS_FLAGGED]) === 'yes'))
        {
            $isFlagged = true;
        }

        $content = [
            Entity::RECEIVED            => true,
            Entity::IS_FLAGGED          => $isFlagged,
            Entity::TRANSACTION_ID      => $content[Resp::API_TRANSACTION_ID],
            Entity::GATEWAY_PAYMENT_ID  => $content[Resp::API_REFERENCE_ID],
        ];

        return $content;
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $content = $this->getPaymentVerifyRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $response = $this->sendGatewayRequest($request);

        $verifyResponse = $this->parseResponseXml($response->body);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [$response->body]);

        $verify->verifyResponse = $response;
        $verify->verifyResponseBody = $response->body;
        $verify->verifyResponseContent = $verifyResponse;

        return $verifyResponse;
    }

    protected function getGatewayEntityDataFromResponse($input)
    {
        $content = $input['gateway'];

        $entityContent = [
            Entity::GATEWAY_PAYMENT_ID  => $content[Resp::GATEWAY_PAYMENT_ID],
            Entity::REQUEST_ID          => $content[Resp::REQUEST_ID],
            Entity::TRANSACTION_ID      => $content[Resp::TRANSACTION_ID],
            Entity::IS_FLAGGED          => false,
            Entity::RECEIVED            => true,
        ];

        if ((isset($input['gateway'][Resp::IS_FLAGGED]) === true) and
            (strtolower($input['gateway'][Resp::IS_FLAGGED]) === 'yes'))
        {
            $content[Entity::IS_FLAGGED] = true;
        }

        if ((isset($input['gateway'][Resp::RESPONSE_CODE]) === true))
        {
            $content[Entity::ERROR_CODE] = $input['gateway'][Resp::RESPONSE_CODE];
        }

        if ((isset($input['gateway'][Resp::RESPONSE_MESSAGE]) === true))
        {
            $content[Entity::ERROR_DESCRIPTION] = $input['gateway'][Resp::RESPONSE_MESSAGE];
        }

        $content = array_merge($content, $entityContent);

        $content = $this->unsetExtraResponseData($content);

        return $content;
    }

    protected function unsetExtraResponseData($content)
    {
        unset($content[Resp::GATEWAY_PAYMENT_ID]);

        unset($content[Resp::REQUEST_ID]);

        unset($content[Resp::TRANSACTION_ID]);

        unset($content[Resp::IS_FLAGGED]);

        unset($content[Resp::MERCHANT_REF_NO]);

        return $content;
    }

    protected function getPaymentVerifyRequestContent($input)
    {
        $content = [
            Req::API_ACTION         => 'statusByRef',
            Req::API_REFERENCE_NO   => $input['payment'][Payment\Entity::ID],
        ];

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST, $content);

        $content[Req::API_ACCOUNT_ID] = $this->getAccountId($input['terminal']);
        $content[Req::API_SECRET_KEY] = $this->getSecretKey($input['terminal']);

        return $content;
    }

    protected function getPaymentRefundRequestContent($gatewayPayment, $input)
    {
        $refundAmount = $input['refund']['amount']/100;

        $content = [
            Req::API_ACTION         => 'refund',
            Req::API_AMOUNT         => $refundAmount,
            Req::API_PAYMENT_ID     => $gatewayPayment[Entity::GATEWAY_PAYMENT_ID],
        ];

        $this->trace->info(TraceCode::GATEWAY_REFUND_REQUEST, $content);

        $content[Req::API_ACCOUNT_ID] = $this->getAccountId($input['terminal']);
        $content[Req::API_SECRET_KEY] = $this->getSecretKey($input['terminal']);

        return $content;
    }

    protected function getAccountId($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::MERCHANT_ID];
        }

        return $terminal['gateway_merchant_id'];
    }

    protected function getSecretKey($terminal)
    {
        if ($this->mode === Mode::TEST)
        {
            return $this->config[self::HASH_SECRET];
        }

        return $terminal['gateway_secure_secret'];
    }

    protected function createGatewayPaymentEntity($attributes, $input)
    {
        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $gatewayPayment->setPaymentId($input['payment'][Payment\Entity::ID]);

        $gatewayPayment->fill($attributes);

        $gatewayPayment->setAction($this->action);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function getDefaultRequestContent()
    {
        $content = [
            Req::NAME          => 'Razorpay',
            Req::ADDRESS       => 'Razorpay',
            Req::CITY          => 'Bangalore',
            Req::COUNTRY       => 'IND',
            Req::POSTAL_CODE   => '560001',
            Req::PHONE         => '9876543210',
            Req::EMAIL         => 'helpdesk@razorpay.com',
            Req::DESCRIPTION   => 'NA',
            Req::CURRENCY      => 'INR',
        ];

        return $content;
    }

    protected function getAuthRequestContentArray($input)
    {
        $amount = $input['payment']['amount']/100;

        $defaultContent = $this->getDefaultRequestContent();

        $content = [
            Req::ACCOUNT_ID    => $this->getAccountId($input['terminal']),
            Req::REFERENCE_NO  => $input['payment'][Payment\Entity::ID],
            Req::AMOUNT        => $amount,
            Req::CALLBACK      => $input['callbackUrl'],
            Req::MODE          => strtoupper($this->mode),
            Req::PAYMENT_MODE  => $this->getPaymentMode($input),
        ];

        $content = array_merge($content, $defaultContent);

        if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $this->setAuthRequestContentForNetBanking($content, $input);
        }
        else if ($input['payment']['method'] === Payment\Method::CARD)
        {
            $this->setAuthRequestContentForCard($content, $input);
        }

        $content[Req::SECURE_HASH] = $this->getHashOfArray($content);

        return $content;
    }

    protected function setAuthRequestContentForCard(&$content, $input)
    {
        $content[Req::CHANNEL]       = Channel::CARD;
        $content[Req::NAME_ON_CARD]  = $input['card']['name'];
        $content[Req::CARD_NUMBER]   = $input['card']['number'];
        $content[Req::CARD_EXPIRY]   = $this->getCardExpiry($input);
        $content[Req::CARD_CVV]      = $input['card']['cvv'];
        $content[Req::CARD_NETWORK]  = CardNetwork::map($input['card']['network_code']);
    }

    protected function setAuthRequestContentForNetBanking(&$content, $input)
    {
        $content[Req::CHANNEL]        = Channel::NETBANKING;
        $content[Req::PAYMENT_OPTION] = BankCodes::getMappedCode($input['payment']['bank']);
    }

    protected function getCardExpiry($input)
    {
        $month = $input['card']['expiry_month'];
        $year = $input['card']['expiry_year'];

        return Carbon::createFromDate($year, $month)->format('my');
    }

    protected function getPaymentMode($input)
    {
        $paymentMode = null;

        if ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $paymentMode = PaymentMode::NETBANKING;
        }
        else if ($input['payment']['method'] === Payment\Method::CARD)
        {
            if ($input['card']['type'] === Card\Type::DEBIT)
            {
                $paymentMode = PaymentMode::DEBIT;
            }
            else if ($input['card']['type'] === Card\Type::CREDIT)
            {
                $paymentMode = PaymentMode::CREDIT;
            }
        }

        if (empty($paymentMode) === true)
        {
            throw new Exception\LogicException(
                'Invalid payment mode');
        }

        return $paymentMode;
    }

    protected function getUrlDomain()
    {
        $apiDomainActionList = [
            Action::CAPTURE,
            Action::REFUND,
            Action::VERIFY
        ];

        if (in_array($this->action, $apiDomainActionList))
        {
            $this->domainType = self::API;
        }

        return parent::getUrlDomain();
    }

    protected function validateCallbackGetSecureHash(array $content, $terminal)
    {
        $hash = $content[Resp::SECURE_HASH];

        // Remove secureHash Value to calculate Expected Hash Value
        unset($content[Resp::SECURE_HASH]);

        $expectedHash = $this->getHashOfArray($content);

        if ($hash !== $expectedHash)
        {
            throw new Exception\LogicException(
                'Checksum verification failed');
        }
    }

    protected function getAuthorizeAttributesForPaymentEntity($content)
    {
        $attributes = [Entity::AMOUNT => $content[Req::AMOUNT]];

        return $attributes;
    }

    protected function getRefundContent($response, $input)
    {
        $refundAmount = $input['refund']['amount'] / 100;

        $attributes = [
            Entity::REFUND_ID   => $input['refund'][Payment\Entity::ID],
            Entity::AMOUNT      => $refundAmount,
            Entity::RECEIVED    => true,
        ];

        if (isset($response[Resp::TRANSACTION_ID]))
        {
            $attributes[Entity::TRANSACTION_ID] = $response[Resp::API_TRANSACTION_ID];
        }

        if ((isset($response[Entity::IS_FLAGGED]) === true) and
            (strtolower($response[Entity::IS_FLAGGED]) === 'yes'))
        {
            $attributes[Entity::IS_FLAGGED] = true;
        }

        if ((isset($response[Resp::STATUS]) === false) or
            ($response[Resp::STATUS] !== Status::API_PROCESSING))
        {
            $attributes[Entity::ERROR_CODE]        = $response[Resp::ERROR_CODE];
            $attributes[Entity::ERROR_DESCRIPTION] = $response[Resp::ERROR];
        }

        return $attributes;
    }

    protected function parseResponseXml($response)
    {
        $arrayResponse = (array) simplexml_load_string($response);

        return $arrayResponse['@attributes'];
    }

    protected function getStringToHash($content, $glue = '|')
    {
        $hashArray = [];

        foreach ($content as $key => $value)
        {
            if (strlen($value) > 0)
            {
                $hashArray[] = $value;
            }
        }

        return implode($glue, $hashArray);
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        $str = $secret . '|' . $str;

        return strtoupper(hash(self::HASH_ALGO, $str));
    }
}
