<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\VerifyResult;

class Gateway extends Base\Gateway
{
    use Base\AuthorizeFailed;

    protected $gateway = Payment\Gateway::CARDLESS_EMI;

    protected $provider;

    const EMI_PLAN_CACHE_KEY = 'emi_plans_%s';

    const LOAN_URL_CACHE_KEY = 'loan_url_%s';

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

    /**
     * Checks customer's account with provider and sends otp for authentication. This function is called from
     * fetchGlobalCustomerStatus function in customer service.The EMI Plans and loan URL received in the response will
     * be stored in cache so that they can be shown to the customer only after OTP Authentication.
     */
    public function checkAccount($input)
    {
        $this->action($input, Action::CHECK_ACCOUNT);

        $this->provider = strtoupper($input['provider']);

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
        $this->checkAccountExists($responseArray);

        $this->checkEmiPlansExists($responseArray);

        $contact = $input['contact'];

        $emiPlans = $responseArray[ResponseFields::EMI_PLANS];

        $loanUrl = isset($responseArray[ResponseFields::LOAN_URL]) ? $responseArray[ResponseFields::LOAN_URL] : null;

        $cacheKey = $this->provider . '_' . $contact . '_' . $this->terminal[Terminal\Entity::MERCHANT_ID];

        $emiPlanKey = sprintf(self::EMI_PLAN_CACHE_KEY, $cacheKey);

        $loanUrlKey = sprintf(self::LOAN_URL_CACHE_KEY, $cacheKey);

        $this->app['cache']->put($emiPlanKey, $emiPlans, self::CACHE_TTL);

        $this->app['cache']->put($loanUrlKey, $loanUrl, self::CACHE_TTL);

        return;
    }

    /**
     * The Authorization flow consists of two steps : fetch token for customer and then authorize the payment
     */
    public function authorize(array $input)
    {
        parent::authorize($input);

        //TODO : Handle case when we already have the token for a customer. Will be implementing this in a later version.
        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $this->action = 'fetch_token';

        $token = $this->fetchToken($input);

        $this->action = 'authorize';

        $content = $this->getAuthorizeAttributes($input, $token);

        $gatewayPayment = $this->createGatewayPaymentEntity($content);

        $request = $this->getStandardRequestArray($content);

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

    public function capture(array $input)
    {
        parent::capture($input);

        $this->provider = strtoupper($input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_ACQUIRER]);

        $this->action = 'fetch_token';

        $token = $this->fetchToken($input);

        $this->action = 'capture';

        $content = $this->getCaptureRequestContent($input, $token);

        $request = $this->getStandardRequestArray($content);

        $traceRequest = $this->stripSensitiveHeader($request);

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

        $this->trace->info(
            TraceCode::GATEWAY_REFUND_RESPONSE,
            [
                'response'    => $response->body,
                'gateway'     => $this->gateway,
                'provider'    => $this->provider,
                'terminal_id' => $input[Constants\Entity::TERMINAL][Terminal\Entity::ID],
            ]);

        $responseArray = $this->jsonToArray($response->body);

        $this->createGatewayPaymentEntity($responseArray);

        $this->checkRefundSuccess($responseArray);
    }

/*-------------------------------------------------HELPER FUNCTIONS--------------------------------------------------*/

    protected function getCheckAccountRequestContent($input)
    {
        $content = [
            RequestFields::CONTACT                  => $input['contact'],
            RequestFields::AMOUNT                   => $input['amount'],
            RequestFields::MERCHANT_ID              => $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID],
            RequestFields::MERCHANT_CATEGORY_CODE   => $this->terminal[Terminal\Entity::CATEGORY],
            RequestFields::BILLING_LABEL            => $this->terminal[Terminal\Entity::GATEWAY_MERCHANT_ID2]
        ];

        return $content;
    }

    protected function checkAccountExists($response)
    {
        if ((isset($response[ResponseFields::ERROR_CODE]) === true) and
            ($response[ResponseFields::ERROR_CODE] !== 'OK'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST,
                $response[ResponseFields::ERROR_CODE]);
        }
    }

    protected function  fetchToken($input)
    {
        $fetchTokenContent = $this->getFetchTokenRequestContent($input);

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
            RequestFields::CONTACT        => $input[Constants\Entity::PAYMENT][Payment\Entity::CONTACT],
            RequestFields::MERCHANT_ID    => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
            RequestFields::BILLING_LABEL  => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2]
        ];
    }

    protected function getAuthorizeAttributes($input, $token)
    {
        return [
            RequestFields::AMOUNT         => $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT],
            RequestFields::TOKEN          => $token,
            RequestFields::PAYMENT_ID     => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
            RequestFields::CURRENCY       => $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY],
            RequestFields::EMI_DURATION   => $input['gateway']['emi_duration'],
            RequestFields::MERCHANT       => [
                RequestFields::MERCHANT_ID   => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                RequestFields::BILLING_LABEL => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2]
            ],
            RequestFields::ACTION         => Base\Action::AUTHORIZE,
            RequestFields::USER_IP        => $input[Constants\Entity::PAYMENT_ANALYTICS][Payment\Analytics\Entity::IP],
        ];
    }

    protected function getCaptureRequestContent($input, $token)
    {
        return [
            RequestFields::TOKEN          => $token,
            RequestFields::ACTION         => Base\Action::CAPTURE,
            RequestFields::AMOUNT         => $input[Constants\Entity::PAYMENT][Payment\Entity::AMOUNT],
            RequestFields::CURRENCY       => $input[Constants\Entity::PAYMENT][Payment\Entity::CURRENCY],
            RequestFields::PAYMENT_ID     => $input[Constants\Entity::PAYMENT][Payment\Entity::ID],
            RequestFields::MERCHANT       => [
                RequestFields::MERCHANT_ID   => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID],
                RequestFields::BILLING_LABEL => $input[Constants\Entity::TERMINAL][Terminal\Entity::GATEWAY_MERCHANT_ID2],
            ]
        ];
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
        $content = $this->getVerifyRequestContent($verify->input);

        $request = $this->getStandardRequestArray($content);

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

        $this->verifyAmountMismatch($verify, Constants\Entity::PAYMENT);

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
        $content = json_encode($content);

        $request = parent::getStandardRequestArray($content, $method, $type);

        $request['headers'] = $this->getRequestHeaders();

        return $request;
    }

    protected function getRequestHeaders()
    {
        $token = $this->terminal[Terminal\Entity::GATEWAY_TERMINAL_PASSWORD];

        $headers = [
            'Content-Type'   => 'application/json',
            'Authorization'  => 'Basic ' . $token,
        ];

        return $headers;
    }

    public function getEmiPlans($input)
    {
        $input['provider'] = strtoupper($input['provider']);

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $contact = $input['contact'];

        $cacheKey = $input['provider'] . '_' . $contact . '_' . $this->terminal[Terminal\Entity::MERCHANT_ID];

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
        if ((isset($response[ResponseFields::STATUS]) === true) and
             ($response[ResponseFields::STATUS] !== 'authorized'))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
        }
    }

    protected function checkGatewaySuccess(Base\Verify $verify)
    {
        $verify->gatewaySuccess = false;

        $content = $verify->verifyResponseContent;

        if ((isset($content[ResponseFields::ERROR_CODE]) !== true) or
            ($content[ResponseFields::ERROR_CODE] === 'OK'))
        {
            $verify->gatewaySuccess = true;
        }
    }

    protected function checkCaptureSuccess($response)
    {
        if ((isset($response[ResponseFields::ERROR_CODE]) === true) or
            ((isset($response[ResponseFields::STATUS]) === true) and ($response[ResponseFields::STATUS] !== 'captured')))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED);
        }
    }

    protected function checkRefundSuccess($response)
    {
        if (((isset($response[ResponseFields::ERROR_CODE]) === true) and
             ($response[ResponseFields::ERROR_CODE] !== 'OK')) or
            ((isset($response[ResponseFields::STATUS]) === true) and
             ($response[ResponseFields::STATUS] !== 'success')))
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED);
        }
    }

    protected function checkTokenExists($content)
    {
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
            $entity->setGatewayReferenceId($attributes[ResponseFields::PROVIDER_REFUND_ID]);
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

    public function pushDimensions($action, $input, $status, $excData = null)
    {
        $gatewayMetric = new Metric();

        $gatewayMetric->pushGatewayDimensions($action, $input, $status, $this->gateway);
    }
}
