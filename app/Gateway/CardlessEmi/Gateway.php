<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;
use RZP\Gateway\Sharp;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\HashAlgo;
use RZP\Models\Payment\Refund;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\CardlessEmi;

class Gateway extends Base\Gateway
{
    use ErrorCodes;
    use Base\AuthorizeFailed;

    protected $gateway = Payment\Gateway::CARDLESS_EMI;

    protected $provider;

    const EMI_PLAN_CACHE_KEY = 'gateway:emi_plans_%s';

    const LOAN_URL_CACHE_KEY = 'gateway:loan_url_%s';

    const REDIRECT_URL_CACHE_KEY = 'gateway:redirect_url_%s';

    const BRANDING_URL_CACHE_KEY = 'gateway:branding_url_%s';

    const SUCCESS_RESPONSE = 'success';

    const GET = 'get';

    protected $map = [
        ResponseFields::PROVIDER_PAYMENT_ID   => Entity::GATEWAY_REFERENCE_ID,
        RequestFields::PAYMENT_ID             => Entity::PAYMENT_ID,
        RequestFields::EMI_DURATION           => Entity::GATEWAY_PLAN_ID,
        ResponseFields::ERROR_CODE            => Entity::ERROR_CODE,
        ResponseFields::ERROR_DESCRIPTION     => Entity::ERROR_DESCRIPTION,
        RequestFields::AMOUNT                 => Entity::AMOUNT,
        ResponseFields::PROVIDER_REFUND_ID    => Entity::GATEWAY_REFERENCE_ID,
        ResponseFields::STATUS                => Entity::STATUS,
    ];

    protected $tokenRequiredProviders = [
        Payment\Method::CARDLESS_EMI => [
            CardlessEmi::ZESTMONEY,
            CardlessEmi::EARLYSALARY,
        ],
        Payment\Method::PAYLATER => [
            PayLater::EPAYLATER,
        ]
    ];

    protected $nonVerifyRefundProviders = [CardlessEmi::EARLYSALARY];

    public function setGatewayParams($input, $mode, $terminal)
    {
        parent::setGatewayParams($input, $mode, $terminal);

        $this->gateway = $terminal['gateway'];
    }

    protected function getAxisWrapperUrl(string $type, string $urlDomain )
    {
        return $this->externalMockDomain . '/' . $this->gateway . $this->getRelativeUrl($type);
    }

    /**
     * Checks customer's account with provider and sends otp for authentication. This function is called from
     * fetchGlobalCustomerStatus function in customer service.The EMI Plans and loan URL received in the response will
     * be stored in cache so that they can be shown to the customer only after OTP Authentication.
     */
    public function checkAccount($input)
    {
        $this->action($input, Action::CHECK_ACCOUNT);

        if($this->terminal['gateway'] === Payment\Gateway::SHARP)
        {
            return $this->mockCheckAccountResponseForSharpTerminal($input);
        }

        $this->provider = strtoupper($this->terminal['gateway_acquirer']);

        $checkAccountContent = $this->getCheckAccountRequestContent($input);

        $request = $this->getStandardRequestArray($checkAccountContent);

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::CHECK_ACCOUNT_REQUEST,
            [
                'request'  => $traceRequest,
                'gateway'  => $this->gateway,
                'provider' => $this->provider,
                'contact'  => $input['contact'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::CHECK_ACCOUNT_RESPONSE,
            [
                'response' => $response->body,
                'gateway'  => $this->gateway,
                'provider' => $this->provider,
                'contact'  => $input['contact'],
            ]);

        $responseArray = $this->jsonToArray($response->body);

        /**
         * If user does not exist exception is thrown here, customer will be asked to choose an alternate mode
         * of payment
         */
        try
        {
            $this->checkAccountExists($responseArray);
        }
        catch (Exception\GatewayErrorException $exception)
        {
            if(!$this->isEarlySalaryRedirectApplicable($input))
            {
                throw $exception;
            }
        }

        // in case of method: paylater, there are no emi plans.
        if ($this->gateway === Payment\Gateway::PAYLATER)
        {
            $gatewayAcquirer = $this->provider;

            if (in_array(strtolower($gatewayAcquirer), Payment\Gateway::$redirectFlowProvider) === true)
            {
                $this->addCacheData($input, $responseArray);
            }

            return;
        }
        elseif ($this->isEarlySalaryRedirectApplicable($input))
        {
            if(isset($responseArray[ResponseFields::REDIRECT_URL_EARLYSALARY]) === false)
            {
                throw new Exception\GatewayErrorException(
                    ErrorCode::GATEWAY_ERROR_PAYMENT_FLOW_MISMATCH);
            }

            $this->addCacheData($input, $responseArray);

            return;
        }

        $this->checkEmiPlansExists($responseArray);

        $this->addCacheData($input, $responseArray);

        if (isset($input['payment_id']) === true)
        {
            unset($input['payment_id']);

            $this->addCacheData($input, $responseArray);

        }

        if (in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true)
        {
            return [
                'emi_plans'             => $responseArray[ResponseFields::EMI_PLANS],
                'lender_branding_url'   => $responseArray[ResponseFields::EXTRA],
                'success'               => 1
            ];
        }

        return;
    }

    protected function addCacheData($input, $responseArray)
    {
        $emiPlans = $responseArray[ResponseFields::EMI_PLANS] ?? null;

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $contact = $input['contact'];

        $merchantId = $this->terminal[Terminal\Entity::MERCHANT_ID];

        $paymentIdString = '';

        if (isset($input['payment_id']) === true)
        {
            $paymentIdString = '_' . $input['payment_id'];
        }

        $cacheKey = $this->provider . '_' . $contact . '_' . $input['merchant_id'] . $paymentIdString;

        $emiPlanKey = sprintf(self::EMI_PLAN_CACHE_KEY, $cacheKey);

        if (in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true)
        {
            $url = $responseArray[ResponseFields::REDIRECT_URL];

            $key = sprintf(self::REDIRECT_URL_CACHE_KEY, $cacheKey);

            $brandingCacheKey = sprintf(self::BRANDING_URL_CACHE_KEY, $cacheKey);

            $brandingUrl = $responseArray[ResponseFields::EXTRA];

            $this->createCacheData($brandingCacheKey, $brandingUrl);
        }
        elseif ($this->isEarlySalaryRedirectApplicable($input))
        {
            $url = $responseArray[ResponseFields::REDIRECT_URL_EARLYSALARY];

            $key = sprintf(self::REDIRECT_URL_CACHE_KEY, $cacheKey);
        }
        else
        {
            $url = isset($responseArray[ResponseFields::LOAN_URL]) ? $responseArray[ResponseFields::LOAN_URL] : null;

            $key = sprintf(self::LOAN_URL_CACHE_KEY, $cacheKey);
        }

        // EMI plans will not be there for paylater gateways
        if ($emiPlans != null)
        {
            $this->createCacheData($emiPlanKey, $emiPlans);
        }

        $this->createCacheData($key, $url);
    }

    protected function createCacheData($key, $value, $ttl = self::CARD_CACHE_TTL)
    {
        // Multiplying by 60 since cache put() expect ttl in seconds
        $this->app['cache']->put($key, $value, $ttl*60);
    }


    //-----------------------------methods called by Payment Processor Begin--------------------------
    /**
     * The Authorization flow consists of two steps : fetch token for customer and then authorize the payment
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        //TODO: Handle case when we already have the token for a customer. Will be implementing this in a later version.
        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        if ((strtolower($this->provider) === CardlessEmi::EARLYSALARY) and
            ($input['merchant']->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY)))
        {
            $content = [];

            $this->createGatewayPaymentEntity($content);

            $request = $this->getStandardRequestArrayForEarlysalary([],"get");

            return $this->getRedirectRequestData($input, $request);
        }

        $token = null;

        if ($this->isTokenRequired() === true)
        {
            $this->action = 'fetch_token';

            $token = $this->call(camel_case(Action::FETCH_TOKEN), $input);

            $this->action = 'authorize';
        }

        $content = $this->getAuthorizeAttributes($input, $token);

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content);

        if ((in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true))
        {
            return $this->getRedirectRequestData($input, $request);
        }

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'response'    => $response->body,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $this->updateGatewayPaymentEntity($gatewayPayment, $responseArray);

        $this->checkAuthorizationSuccess($responseArray);
    }

    public function isTokenRequired()
    {
        return in_array(strtolower($this->provider), $this->tokenRequiredProviders[$this->gateway]);
    }

    public function getRedirectRequestData($input, $request)
    {
        $contact = $input['payment']['contact'];

        if(strtolower($this->provider) === CardlessEmi::EARLYSALARY)
        {
            $cacheKey = $this->provider . '_' . $contact . '_' . $input['payment']['merchant_id']. '_' .$input['payment']['public_id'];
        }
        else
        {
            $cacheKey = $this->provider . '_' . $contact . '_' . $input['payment']['merchant_id'];
        }

        $redirectUrlKey = sprintf(self::REDIRECT_URL_CACHE_KEY, $cacheKey);

        $url = $this->app['cache']->get($redirectUrlKey);

        // If the redirection URL is not fetched from the cache then fail the payment
        if($url === null)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR);
        }

        $request['url'] = $url;

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        return $request;
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $token = null;

        if ($this->isTokenRequired() === true)
        {
            $this->action = 'fetch_token';

            $token = $this->fetchToken($input);

            $this->action = 'capture';
        }

        $content = $this->getCaptureRequestContent($input, $token);

        $request = $this->getStandardRequestArray($content);

        $traceRequest = $this->stripSensitiveHeader($request);

        unset($traceRequest['email'], $traceRequest['password']);

        $this->trace->info(
            TraceCode::PAYMENT_CAPTURE_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_CAPTURE_RESPONSE,
            [
                'response'    => $response->body,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $this->createGatewayPaymentEntity($responseArray);

        $this->checkCaptureSuccess($responseArray);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $verify = new Base\Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function refund(array $input)
    {
        parent::refund($input);

        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $content = $this->getRefundRequestContent($input);

        $request = $this->getStandardRequestArray($content);

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $response = $this->sendGatewayRequest($request);

        $responseContent = $response->body;

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response'    => $responseContent,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $responseArray = $this->jsonToArray($responseContent);

        $this->createGatewayPaymentEntity($responseArray);

        $this->checkRefundSuccess($responseArray);

        $scroogeGatewayResponse = (is_string($responseContent) === false) ?
            json_encode($responseContent) :
            $responseContent;

        return [
            Payment\Gateway::GATEWAY_RESPONSE  => $scroogeGatewayResponse,
            Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($responseArray)
        ];
    }

    protected function getGatewayData(array $response = [])
    {
        if (empty($response) === false)
        {
            return [
                Refund\Entity::RRN => $response[ResponseFields::PROVIDER_REFUND_ID] ?? null
            ];
        }

        return [];
    }

    public function reverse(array $input)
    {
        parent::action($input, Action::REVERSE);

        return $this->refund($input);
    }

/*-------------------------------------------------HELPER FUNCTIONS--------------------------------------------------*/

    protected function getCheckAccountRequestContent($input)
    {
        $content = [
            RequestFields::AMOUNT                   => $input['amount'],
            RequestFields::MERCHANT_ID              => $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID],
        ];

        return $this->modifyRequestContent($input, $content);
    }

    protected function modifyRequestContent($input, $content)
    {
        switch (strtolower($this->provider))
        {
            case CardlessEmi::EARLYSALARY:
                $content[RequestFields::MOBILE_NUMBER] = $input['contact'];
                $content[RequestFields::MERCHANT_CATEGORY_CODE] = $this->terminal[Terminal\Entity::CATEGORY];
                $content[RequestFields::BILLING_LABEL] = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
                $content[RequestFields::MERCHANT_NAME] = $input['merchant_name'] ?? '';
                $content[RequestFields::MERCHANT_WEBSITE] = $input['merchant_website'] ?? '';
                $content[RequestFields::MERCHANT_MCC] = $input['merchant_mcc'] ?? '';

                if ($this->isEarlySalaryRedirectApplicable($input))
                {
                    $content[RequestFields::REDIRECT_URL] = $input['callbackUrl'];

                    $content[RequestFields::PAYMENT_ID] = explode("_",$input['payment_id'])[1];

                    $receipt = explode("_",$input['payment_id'])[1];

                    if (isset($input['order']['receipt']))
                    {
                        $receipt = $input['order']['receipt'];
                    }

                    $content[RequestFields::RECEIPT] = $receipt;
                }

                break;
            case CardlessEmi::ZESTMONEY:
                $content[RequestFields::MOBILE_NUMBER] = $input['contact'];
                $content[RequestFields::MERCHANT_CATEGORY_CODE] = $this->terminal[Terminal\Entity::CATEGORY];
                $content[RequestFields::BILLING_LABEL] = $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2];
                break;
            case CardlessEmi::FLEXMONEY:
                $content[RequestFields::CONTACT] = $input['contact'];
                $content = $this->addBankCodeAndTransactionType($content, $input['provider']);
                break;
            case PayLater::EPAYLATER:
                $content[RequestFields::AMOUNT] = (string) ($input['amount']);
                $content[RequestFields::CONTACT] = substr($input['contact'], -10, 10);
            default:
                break;
        }

        return $content;
    }

    protected function addBankCodeAndTransactionType($content, $provider)
    {
        if (($this->gateway === Payment\Gateway::PAYLATER))
        {
            $content[RequestFields::TRANSACTION_TYPE] = 'PAY_LATER';
            $content[RequestFields::BANK_CODE]        = BankCodes::getBankCode($provider);
        }

        if (($this->gateway === Payment\Gateway::CARDLESS_EMI) and (CardlessEmi::getProviderForBank($provider) != null))
        {
            $content[RequestFields::TRANSACTION_TYPE] = 'EMI';
            $content[RequestFields::BANK_CODE]        = BankCodes::getBankCodeForCardlessEmiMultiLender($provider);
        }

        return $content;
    }

    protected function checkAccountExists($response)
    {
        $response = $this->modifyResponseErrorFieldForEpayLater($response);

        if ((isset($response[ResponseFields::ERROR_CODE]) === true) and
            ($response[ResponseFields::ERROR_CODE] !== 'OK'))
        {
            $defaultErrorCode = ErrorCode::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST;

            if ($this->gateway === Payment\Gateway::PAYLATER)
            {
                    $defaultErrorCode = ErrorCode::BAD_REQUEST_PAYLATER_USER_DOES_NOT_EXIST;
            }

            $errorCode = $this->getInternalErrorCode($response[ResponseFields::ERROR_CODE], $defaultErrorCode);

            $data = [
                'order_id'   => $this->input['order_id'] ?? null,
            ];

            $exception = new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::ERROR_CODE],
                null,
                $data
            );

            $exception->getError()->setMetadata($data);

            throw $exception;
        }
    }

    protected function fetchToken($input)
    {
        $fetchTokenContent = $this->getFetchTokenRequestContent($input);

        $fetchTokenContent = $this->modifyFetchTokenRequest($fetchTokenContent, $input);

        $request = $this->getStandardRequestArray($fetchTokenContent);

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::FETCH_TOKEN_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::FETCH_TOKEN_RESPONSE,
            [
                'response'    => $response->body,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID]
            ]);

        $responseArray = $this->jsonToArray($response->body);

        if ($response->status_code !== 200)
        {
            throw new Exception\GatewayErrorException(
                $this->getInternalErrorCode($responseArray['errors'] ?? '',
                    ErrorCode::GATEWAY_ERROR_INTERNAL_SERVER_ERROR));
        }

        $this->checkTokenExists($responseArray);

        return $responseArray[ResponseFields::TOKEN];
    }

    public function forceAuthorizeFailed($input)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $input['payment']['id'],
            Action::AUTHORIZE);

        // Return true if already authorized on gateway
        if (($gatewayPayment->getGatewayReferenceId() !== null) and
            ($gatewayPayment->getStatus() === 'authorized'))
        {
            return true;
        }

        if (empty($input['gateway']['provider_payment_id']) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_AUTH_DATA_MISSING,
                null,
                $input);
        }

        $contentToSave = [
            Entity::GATEWAY_REFERENCE_ID => $input['gateway']['provider_payment_id'],
            Entity::STATUS               => 'authorized',
        ];

        $gatewayPayment->fill($contentToSave);

        $this->repo->saveOrFail($gatewayPayment);

        return true;
    }

    protected function checkEmiPlansExists($response)
    {
        if (empty($response[ResponseFields::EMI_PLANS]) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_EMI_PLANS_DO_NOT_EXIST);
        }
    }

    public function getFetchTokenRequestContent($input)
    {
        return [
            RequestFields::MOBILE_NUMBER  => $input[Constants\Entity::PAYMENT][Payment\Entity::CONTACT],
            RequestFields::MERCHANT_ID    => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
            RequestFields::BILLING_LABEL  => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2]
        ];
    }

    protected function modifyFetchTokenRequest($request, $input)
    {
        switch ($input[Constants\Entity::PAYMENT][Payment\Entity::METHOD])
        {
            case Payment\Method::PAYLATER:
                switch ($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER])
                {
                    case PayLater::EPAYLATER:
                        $request[RequestFields::CONTACT] = substr($request[RequestFields::MOBILE_NUMBER], -10, 10);
                        unset ($request[RequestFields::MOBILE_NUMBER]);
                        break;
                }
        }

        return $request;
    }

    protected function getAuthorizeAttributes($input, $token)
    {
        $content = [
            RequestFields::AMOUNT         => $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT],
            RequestFields::PAYMENT_ID     => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
            RequestFields::CURRENCY       => $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY],
            RequestFields::ACTION         => Base\Action::AUTHORIZE,
        ];

        return $this->modifyAuthorizeRequestContent($input, $content, $token);
    }

    protected function modifyAuthorizeRequestContent($input, $content, $token)
    {
        switch ($input['payment']['method'])
        {
            case Payment\Method::CARDLESS_EMI:
                $content[RequestFields::EMI_DURATION] = $input['gateway']['emi_duration'];
                break;
        }

        switch (strtolower($this->provider))
        {
            case CardlessEmi::FLEXMONEY:
                $content[RequestFields::CALLBACK_URL] = $input['callbackUrl'];
                $content[RequestFields::CONTACT] = $input[Constants\Entity::PAYMENT][Payment\Entity::CONTACT];

                $content[RequestFields::MERCHANT_ID] =
                    $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID];
                $content[RequestFields::BILLING_LABEL] =
                    $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2];

                $checksum = $this->getCheckSumString($content);

                $content[RequestFields::CHECKSUM] = $checksum;

                $content = $this->addBankCodeAndTransactionType($content, $input['payment']['wallet']);

                break;
            case CardlessEmi::EARLYSALARY:
                $content[RequestFields::MERCHANT] = [
                    RequestFields::MERCHANT_ID   =>
                        $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                    RequestFields::BILLING_LABEL =>
                        $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2],
                ];

                $content[RequestFields::TOKEN] = $token;
                $content[RequestFields::USER_IP] = $input[
                Constants\Entity::PAYMENT_ANALYTICS][Payment\Analytics\Entity::IP];

                $content[RequestFields::MERCHANT_NAME] = $input[Constants\Entity::MERCHANT][Merchant\Entity::NAME] ?? '';
                $content[RequestFields::MERCHANT_WEBSITE] = $input[Constants\Entity::MERCHANT][Merchant\Entity::WEBSITE] ?? '';
                $content[RequestFields::MERCHANT_MCC] = $input[Constants\Entity::MERCHANT][Merchant\Entity::CATEGORY] ?? '';
                break;

            default:
                $content[RequestFields::MERCHANT] = [
                    RequestFields::MERCHANT_ID   =>
                        $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                    RequestFields::BILLING_LABEL =>
                        $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2],
                ];

                $content[RequestFields::TOKEN] = $token;
                $content[RequestFields::USER_IP] = $input[
                    Constants\Entity::PAYMENT_ANALYTICS][Payment\Analytics\Entity::IP];

                break;
        }

        return $content;
    }

    protected function getCaptureRequestContent($input, $token)
    {
        $content = [
            RequestFields::ACTION         => Base\Action::CAPTURE,
            RequestFields::AMOUNT         => $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT],
            RequestFields::CURRENCY       => $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY],
            RequestFields::PAYMENT_ID     => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
            RequestFields::MERCHANT       => [
                RequestFields::MERCHANT_ID   =>
                    $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                RequestFields::BILLING_LABEL =>
                    $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2],
            ]
        ];

        return $this->modifyCaptureRequestContent($input, $content, $token);
    }

    protected function modifyCaptureRequestContent($input, $content, $token)
    {
        switch (strtolower($this->provider))
        {
            case CardlessEmi::ZESTMONEY:
                $content[RequestFields::TOKEN] = $token;
                break;
            case CardlessEmi::EARLYSALARY:
                $content[RequestFields::TOKEN] = $token;
                break;
            case PayLater::EPAYLATER:
                $content[RequestFields::TOKEN] = $token;
                break;
            default:
                break;
        }

        return $content;
    }

    protected function getRefundRequestContent($input)
    {
        return [
            RequestFields::AMOUNT     => $input[Constants\Entity::REFUND][Payment\Entity::AMOUNT],
            RequestFields::PAYMENT_ID => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
            RequestFields::CURRENCY   => $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY],
            RequestFields::REFUND_ID  => $input[Constants\Entity::REFUND][Refund\Entity::ID],
        ];
    }

    protected function sendPaymentVerifyRequest($verify)
    {
        $request = $this->getVerifyRequest($verify->input);

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
            [
                'request'     => $traceRequest,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE,
            [
                'response'    => $response->body,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $verify->verifyResponseContent = $responseArray;
    }

    protected function getVerifyRequest($input)
    {
        if ($this->isProviderEpayLater() === true)
        {
            $request = $this->getStandardRequestArray([], 'GET');

            return $request;
        }

        $content = $this->getVerifyRequestContent($input);

        return $this->getStandardRequestArray($content);
    }

    protected function verifyPayment($verify)
    {
        $status = VerifyResult::STATUS_MATCH;

        $input = $verify->input;

        $this->checkApiSuccess($verify);

        $this->checkGatewaySuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }
        if ($verify->gatewaySuccess === true)
        {
            $this->verifyAmountMismatch($verify, Constants\Entity::PAYMENT);

        }

        $verify->status = $status;

        $verify->match = ($status === VerifyResult::STATUS_MATCH) ? true : false;

        $verify->payment = $this->saveVerifyContent($verify);
    }

    protected function getVerifyRequestContent($input)
    {
        return [
            RequestFields::PAYMENT_ID => $input['payment']['id'],
        ];
    }

    protected function getStandardRequestArray($content = [], $method = 'post', $type = null)
    {
        if ($this->shouldJsonEncode($content) === true)
        {
                $content = json_encode($content);
        }

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = $this->getRequestHeaders();

        $replacePairs = [
            '{id}' => $this->input['payment']['id'] ?? null,
        ];

        $request['url'] = strtr($request['url'], $replacePairs);

        return $request;
    }

    protected function getStandardRequestArrayForEarlysalary($content = [], $method = 'post', $type = null)
    {

        $request = parent::getStandardRequestArray($content, $method, $type);

        if($method !== self::GET)
        {
            $request['headers'] = $this->getRequestHeaders();
        }
        $replacePairs = [
            '{id}' => $this->input['payment']['id'] ?? null,
        ];

        $request['url'] = strtr($request['url'], $replacePairs);

        return $request;
    }

    protected function shouldJsonEncode($content)
    {
        if (($this->isGetByIdRequest() === false) and
            (((in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === false) or
            ((in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true) and
                ($this->action !== Action::AUTHORIZE)))))
        {
            return true;
        }
        return false;
    }

    protected function isGetByIdRequest()
    {
        if (($this->action === Action::VERIFY) or ($this->action === Action::VERIFY_REFUND))
        {
            switch ($this->gateway)
            {
                case Payment\Gateway::PAYLATER:
                    switch ($this->terminal[Terminal\Entity::GATEWAY_ACQUIRER])
                    {
                        case PayLater::EPAYLATER:
                            return true;
                    }
            }
        }

        return false;
    }

    protected function getRequestHeaders()
    {
        $token = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        $headers = [
            'Content-Type'   => 'application/json',
        ];

        $tokenType = 'Basic';

        if ($this->isProviderEpayLater() === true)
        {
            $tokenType = 'Bearer';
        }

        $headers['Authorization'] = $tokenType . ' ' . $token;

        return $headers;
    }

    public function getEmiPlans($input)
    {
        $provider = strtoupper($input['provider']);

        if (in_array($input['provider'], CardlessEmi::getCardlessEmiDirectAquirers()) === false)
        {
            $provider = strtoupper(CardlessEmi::getProviderForBank($input['provider']));
        }

        $input['provider'] = $provider;

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $contact = $input['contact'];

        $paymentIdString = '';

        if (isset($input['payment_id']) === true)
        {
            $paymentIdString = '_' . $input['payment_id'];
        }

        $cacheKey = $input['provider'] . '_' . $contact . '_' . $input['merchant_id'] . $paymentIdString;

        $emiPlanKey = sprintf(self::EMI_PLAN_CACHE_KEY, $cacheKey );

        $loanUrlKey = sprintf(self::LOAN_URL_CACHE_KEY, $cacheKey);

        $emiPlans = $this->app['cache']->get($emiPlanKey);

        $loanUrl = $this->app['cache']->get($loanUrlKey);

        if ($emiPlans === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_EMI_PLANS_DO_NOT_EXIST,
                null,
                [
                    'key' => $emiPlanKey,
                ],
                'Emi plans do not exist'
            );
        }

        return [$emiPlans, $loanUrl];
    }

    /**
     * Override the getUrlDomain and getRelativeUrl function of base gateway as we will get the domain for
     * a particular provider
     */
    protected function getUrlDomain()
    {
        $urlClass = $this->getGatewayNamespace() . '\Url';

        $domainType = $this->domainType ?? $this->mode;

        $domainConstantName = strtoupper($domainType) . '_DOMAIN_' . $this->provider;

        return constant($urlClass . '::' . $domainConstantName);
    }

    protected function getRelativeUrl($type)
    {
        $ns = $this->getGatewayNamespace();

        return constant($ns . '\Url::' . $type . '_' . $this->provider);
    }

    protected function checkAuthorizationSuccess($response)
    {
        $response = $this->modifyResponseErrorFieldForEpayLater($response);

        if ((isset($response[ResponseFields::STATUS]) === false) or
             ($response[ResponseFields::STATUS] !== 'authorized') or
            (isset($response[ResponseFields::ERROR_CODE]) === true))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function checkGatewaySuccess(Base\Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        $content = $this->modifyResponseErrorFieldForEpayLater($content);

        if ((isset($content[ResponseFields::ERROR_CODE]) !== true) or
            ($content[ResponseFields::ERROR_CODE] === 'OK'))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function checkCaptureSuccess($response)
    {
        $response = $this->modifyResponseErrorFieldForEpayLater($response);

        if ((isset($response[ResponseFields::ERROR_CODE]) === true) or
            (isset($response[ResponseFields::STATUS]) === false) or
                ($response[ResponseFields::STATUS] !== 'captured'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED);
        }
    }

    protected function checkRefundSuccess($response)
    {
        $response = $this->modifyResponseErrorFieldForEpayLater($response);

        if (((isset($response[ResponseFields::ERROR_CODE]) === true) and
             ($response[ResponseFields::ERROR_CODE] !== 'OK')) or
            (isset($response[ResponseFields::STATUS]) === false) or
             (strtolower($response[ResponseFields::STATUS]) !== self::SUCCESS_RESPONSE))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
                '',
                '',
                [
                    Payment\Gateway::GATEWAY_RESPONSE  => json_encode($response),
                    Payment\Gateway::GATEWAY_KEYS      => $this->getGatewayData($response)
                ]);
        }
    }

    protected function checkTokenExists($content)
    {
        $content = $this->modifyResponseErrorFieldForEpayLater($content);

        if ((isset($content[ResponseFields::ERROR_CODE]) === true) and
            ($content[ResponseFields::ERROR_CODE] !== 'OK'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_TOKEN_NOT_FOUND,
                $content[ResponseFields::ERROR_CODE],
                $content[ResponseFields::ERROR_DESCRIPTION]);
        }
    }

    protected function verifyAmountMismatch($verify, $entity)
    {
        $input = $verify->input;
        $content = $verify->verifyResponseContent;

        $verify->amountMismatch = (floatval($input[$entity]['amount']) !== floatval($content[ResponseFields::AMOUNT]));
    }

    protected function createGatewayPaymentEntity($attributes)
    {
        $entity = $this->getNewGatewayPaymentEntity();

        $input = $this->input;

        $entity->setPaymentId($input[Constants\Entity::PAYMENT][Payment\Entity::ID]);

        $entity->setGateway($this->gateway);

        $entity->setContact($input[Constants\Entity::PAYMENT][Payment\Entity::CONTACT]);

        $entity->setProvider($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $entity->setCurrency($input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY]);

        if ($this->action === Action::REFUND)
        {
            $entity->setRefundId($input[Constants\Entity::REFUND]['id']);
            $entity->setAmount($input[Constants\Entity::REFUND]['amount']);

            if (isset($attributes[ResponseFields::PROVIDER_REFUND_ID]) === true)
            {
                $entity->setGatewayReferenceId($attributes[ResponseFields::PROVIDER_REFUND_ID]);
            }
        }
        else
        {
            $entity->setAmount($input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT]);

            if (isset($attributes[ResponseFields::PROVIDER_PAYMENT_ID]) === true)
            {
                $entity->setGatewayReferenceId($attributes[ResponseFields::PROVIDER_PAYMENT_ID]);
            }
        }

        $entity->setAction($this->action);

        $entity->fill($attributes);

        $this->repo->saveOrFail($entity);

        return $entity;
    }

    protected function stripSensitiveHeader($request)
    {
        unset($request['headers']['Authorization']);

        return $request;
    }

    protected function saveVerifyContent($verify)
    {
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseContent;

        $gatewayPayment->fill($content);

        $this->repo->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    public function pushDimensions($action, $input, $status, $excData = null, $statusCode = null)
    {
        $gatewayMetric = new Metric();

        $gatewayMetric->pushGatewayDimensions($action, $input, $status, $this->gateway);

        $gatewayMetric->pushOptimiserGatewayDimensions($action,$input,$status,$this->gateway);
    }

    public function verifyRefund(array $input)
    {
        parent::action($input, Action::VERIFY_REFUND);

        $scroogeResponse = new Base\ScroogeResponse();

        $this->provider = $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER];

        if (in_array($this->provider, $this->nonVerifyRefundProviders, true) === true)
        {
            $unprocessedRefunds = $this->getUnprocessedRefunds();

            $processedRefunds = $this->getProcessedRefunds();

            if (in_array($input[Constants\Entity::REFUND][Refund\Entity::ID], $processedRefunds, true) === true)
            {
                return $scroogeResponse->setSuccess(true)
                                       ->toArray();
            }

            if (in_array($input[Constants\Entity::REFUND][Refund\Entity::ID], $unprocessedRefunds, true) === true)
            {
                return $scroogeResponse->setSuccess(false)
                                       ->setStatusCode(ErrorCode::REFUND_MANUALLY_CONFIRMED_UNPROCESSED)
                                       ->toArray();
            }

            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode(ErrorCode::GATEWAY_ERROR_VERIFY_REFUND_NOT_SUPPORTED)
                                   ->toArray();
        }

        $this->provider = strtoupper($this->provider);

        $response = $this->sendVerifyRefundRequest($input);

        return $this->checkVerifyRefundResponse($response, $scroogeResponse);
    }

    protected function sendVerifyRefundRequest($input)
    {
        $request = $this->getVerifyRefundRequestContent($input);

        $traceRequest = $this->stripSensitiveHeader($request);

        $this->trace->info(TraceCode::GATEWAY_REFUND_VERIFY_REQUEST,
            [
                'request'   => $traceRequest,
                'gateway'   => $this->gateway,
                'provider'  => $this->provider,
            ]);

        return $this->sendGatewayRequest($request);
    }

    protected function getVerifyRefundRequestContent($input)
    {
        $content = [
            RequestFields::REFUND_ID => $input['refund']['id'],
        ];

        return $this->getStandardRequestArray($content);
    }

    protected function checkVerifyRefundResponse($response, Base\ScroogeResponse &$scroogeResponse)
    {
        $this->trace->info(TraceCode::REFUND_VERIFY_RESPONSE, [
            'raw_response'   => $response,
            'gateway'        => $this->gateway,
            'provider'       => $this->provider,
        ]);

        $response = $this->jsonToArray($response->body);

        $scroogeResponse->setGatewayVerifyResponse($response)
                        ->setGatewayKeys($this->getGatewayData($response));

        if ((isset($response[ResponseFields::ERROR_CODE])) or
            ((isset($response[ResponseFields::STATUS])) and ($response[ResponseFields::STATUS] !== self::SUCCESS_RESPONSE)))
        {
            $errorCode = ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT;

            $responseCode = null;

            $responseDescription = null;

            if (isset($response[ResponseFields::ERROR_CODE]) === true)
            {
                $responseCode = $response[ResponseFields::ERROR_CODE];

                $errorCode = $this->getInternalErrorCode($responseCode,
                    ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR);
            }

            return $scroogeResponse->setSuccess(false)
                                   ->setStatusCode($errorCode)
                                   ->toArray();
        }

        return $scroogeResponse->setSuccess(true)
                               ->toArray();
    }

    public function callback(array $input)
    {
        parent::callback($input);

        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $this->trace->info(TraceCode::GATEWAY_PAYMENT_CALLBACK,
            [
                'gateway_response'  => $input['gateway'],
                'payment_id'        => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
                'gateway'           => $this->gateway,
                'provider'          => $this->provider,
            ]);

        $response = $input['gateway'];

        $this->verifyChecksum($response);

        $expectedAmount = number_format($input['payment']['amount'],
            2, '.', '');

        $actualAmount   = number_format($response[ResponseFields::AMOUNT],
            2, '.', '');

        $this->assertAmount($expectedAmount, $actualAmount);

        $gatewayEntity = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::AUTHORIZE);

        $this->assertPaymentId($gatewayEntity->getPaymentId(), $response[ResponseFields::PAYMENT_ID]);

        $attrs = $this->getCallbackAttributes($response);

        $gatewayEntity->fill($attrs);

        $this->repo->saveOrFail($gatewayEntity);

        $this->checkCallbackStatus($response);

        return $response;
    }

    protected function getCallbackAttributes(array $response)
    {
        return [
            Entity::RECEIVED                => true,
            Entity::STATUS                  => $response[ResponseFields::STATUS],
            Entity::GATEWAY_REFERENCE_ID    => $response[ResponseFields::PROVIDER_PAYMENT_ID] ?? '',
        ];
    }

    protected function checkCallbackStatus($response)
    {
        if ((isset($response[ResponseFields::STATUS]) === true) and
            ($response[ResponseFields::STATUS] !== 'authorized'))
        {
            if (isset($response[ResponseFields::ERROR_CODE]) === true)
            {
                $errorCode = $this->getInternalErrorCode(
                    $response[ResponseFields::ERROR_CODE],
                    ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED);

            }
            else
            {
                $errorCode = ErrorCode::GATEWAY_ERROR_PAYMENT_FAILED;
            }

            throw new Exception\GatewayErrorException(
                $errorCode,
                $response[ResponseFields::ERROR_CODE],
                $response[ResponseFields::ERROR_DESCRIPTION],
                [
                    'gateway'   => $this->gateway,
                    'response'  => $response,
                    'provider'  => $this->provider,
                ]);
        }
    }

    protected function verifyChecksum($response)
    {
        $actualChecksum = $response[ResponseFields::CHECKSUM];

        $generatedChecksum = $this->getCheckSumString($response);

        if (hash_equals($generatedChecksum, $actualChecksum) !== true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Failed checksum verification');
        }

    }

    protected function getCheckSumString($response)
    {
        unset($response[ResponseFields::CHECKSUM]);
        unset($response['key_id']);

        return $this->getHashOfArray($response);
    }

    protected function getStringToHash($content, $glue = '|')
    {
        $str = '';

        foreach ($content as $key => $value)
        {
            $str .= $key . '=' . $value . '|';
        }

        return rtrim($str, '|');
    }

    protected function getHashOfString($str)
    {
        $secret = $this->getSecret();

        return base64_encode(hash_hmac(HashAlgo::SHA256, $str, $secret, true));
    }

    protected function getTestSecret()
    {
        assert($this->mode === Mode::TEST);

        $secret = $this->config[strtolower($this->provider)]['test_hash_secret'];

        return $secret;
    }

    protected function getLiveSecret()
    {
        $secret = $this->config[strtolower($this->provider)]['live_hash_secret'];

        return $secret;
    }

    private function isProviderEpayLater()
    {
        return ($this->provider === strtoupper(PayLater::EPAYLATER));
    }

    protected function modifyResponseErrorFieldForEpayLater($response)
    {
        if (isset($response[ResponseFields::EPAYLATER_ERROR_CODE]) === true)
        {
            $response[ResponseFields::ERROR_CODE] = $response[ResponseFields::EPAYLATER_ERROR_CODE];
        }

        if (isset($response[ResponseFields::EPAYLATER_ERROR_DESCRIPTION]) === true)
        {
            $response[ResponseFields::ERROR_DESCRIPTION] = $response[ResponseFields::EPAYLATER_ERROR_DESCRIPTION];
        }

        return $response;
    }
    public function mockCheckAccountResponseForSharpTerminal($input)
    {
        $interest_rate = 18;

        $no_of_months = 12;

        $interest_rate_per_month = ($interest_rate / ($no_of_months * 100));

        $emi_durations = [3, 6, 9, 12];

        $principal_amount = $input["amount"];

        $emi_plans = [];

        foreach($emi_durations as $emi_duration)
        {
            $emi_plan = ($principal_amount * $interest_rate_per_month * pow(1 + $interest_rate_per_month, $emi_duration)) / (pow(1 + $interest_rate_per_month, $emi_duration) - 1);

            array_push($emi_plans, $emi_plan);
        }

        $content = [
        'account_exists'  => true,
        'emi_plans'       => [
            [
                'entity'           => 'emi_plan',
                'duration'         => $emi_durations[0],
                'interest'         => $interest_rate,
                'currency'         => 'INR',
                'amount_per_month' => $emi_plans[0],
            ],
            [
                'entity'           => 'emi_plan',
                'duration'         => $emi_durations[1],
                'interest'         => $interest_rate,
                'currency'         => 'INR',
                'amount_per_month' => $emi_plans[1],
            ],
            [
                'entity'           => 'emi_plan',
                'duration'         => $emi_durations[2],
                'interest'         => $interest_rate,
                'currency'         => 'INR',
                'amount_per_month' => $emi_plans[2],
            ],
            [
                'entity'           => 'emi_plan',
                'duration'         => $emi_durations[3],
                'interest'         => $interest_rate,
                'currency'         => 'INR',
                'amount_per_month' => $emi_plans[3],
            ],
        ],
        'loan_agreement'      => 'link_to_loan_agreement',
        'redirection_url'     => 'dummy_redirect_url',
        'extra'               => 'lender_brand',
        'lender_branding_url' => 'dummy_lender_branding_url',
    ];

        $this->provider = $input['provider'];

        if($input['method'] === Payment\Gateway::PAYLATER)
        {
            unset($content['emi_plans']);

            if (in_array($input['provider'], PayLater::getPaylaterDirectAquirers()) === false)
            {
                $this->provider = PayLater::getProviderForBank($input['provider']);
            }

            if (in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true)
            {
                $this->provider = strtoupper($this->provider);

                $this->addCacheData($input, $content);
            }
        }

        if($input['method'] === Payment\Gateway::CARDLESS_EMI)
        {
            if(in_array($input['provider'], CardlessEmi::getCardlessEmiDirectAquirers()) === false)
            {
                $this->provider = CardlessEmi::getProviderForBank($input['provider']);
            }

            $this->provider = strtoupper($this->provider);

            $this->addCacheData($input, $content);

            if (in_array(strtolower($this->provider), Payment\Gateway::$redirectFlowProvider) === true)
            {
                return $content;
            }
        }

        return;
    }

    protected function isEarlySalaryRedirectApplicable($input)
    {
        if(($this->gateway === Payment\Gateway::CARDLESS_EMI) and
            (strtolower($this->provider) === CardlessEmi::EARLYSALARY) and
            (in_array(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY , $input['merchant_features'])))
        {
            return true;
        }

        return false;
    }
}
