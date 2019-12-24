<?php

namespace RZP\Gateway\Mozart;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Constants\Entity as E;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Upi\Mindgate\Crypto;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Mozart\Entity as MozartEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    use AuthorizeFailed {
        extractPaymentsProperties as extractPaymentsPropertiesAuthorizedFailedTrait;
    }

    protected $gateway = 'mozart';

    const CACHE_KEY    = 'gateway:cache_key_%s';

    protected $map = [
        'data'      => Entity::RAW,
    ];

    public function checkAccount(array $input)
    {
        $this->action($input, Action::CHECKACCOUNT);

        $provider = strtoupper($input['provider']);

        $input['terminal'] = $this->terminal;

        switch ($input['method'])
        {
            case Payment\Gateway::PAYLATER:
                $input['payment']['gateway'] = $input['provider'];
                break;
        }

        $request = $this->getMozartRequestArray($input);

        $this->trace->info(
            TraceCode::CHECK_ACCOUNT_REQUEST,
            [
                'url'      => $request['url'],
                'gateway'  => $this->gateway,
                'provider' => $provider,
                'contact'  => $input['contact'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->trace->info(
            TraceCode::CHECK_ACCOUNT_RESPONSE,
            [
                'response' => $traceRes,
            ]);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::CHECKACCOUNT);

        switch ($input['provider'])
        {
            case Payment\Gateway::GETSIMPL:
                $token = $response['data']['simpltoken'];
                break;
            default:
                $token = null;
        }

        if ($this->shouldCacheToken($token, $input) == true)
        {
            $this->cacheValue($token, $input);
        }

        return $response;
    }

    public function authorize(array $input)
    {
        parent::action($input, Action::PAY_INIT);

        if (($this->getGateway($input) === 'wallet_phonepe') and ($input['wallet']['flow'] == 'intent'))
        {
            parent::action($input, Action::INTENT);
        }

        switch ($this->terminal->getGatewayAcquirer())
        {
            case Payment\Gateway::GETSIMPL:

                if(empty($input['simpltoken']) === true)
                {
                    $input['simpltoken'] = $this->fetchCacheData($input);
                }

                $input['payment']['gateway'] = $input['payment']['wallet'];
                break;
        }

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_AUTHORIZE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        if ($this->action === Action::INTENT)
        {
            $data = [
                'intent_url' => $response['next']['redirect']['url'],
            ];

            return ['data' => $data];
        }

        $intentGatewaysWithPayInit = [
            Payment\Gateway::UPI_JUSPAY,
        ];

        if (($this->action === Action::PAY_INIT) and
            (in_array($this->getGateway($input), $intentGatewaysWithPayInit, true)))
        {
            if($this->isUpiIntent($input) === true)
            {
                $data = [
                    'intent_url' => $response['next']['redirect']['url'],
                ];

                return ['data' => $data ];
            }
        }

        if ($input['payment']['method'] === 'upi')
        {
            return [
                'data'   => [
                    Payment\Entity::VPA => $input['terminal']['gateway_merchant_id2']
                ]
            ];
        }

        return $response['next']['redirect'] ?? null;
    }

    public function reconcile(array $input)
    {
        parent::action($input, Action::RECONCILE);

        //Create a mapping if there are more gateways for which migration from api to mozart is done with api based reconciliation.
        if ($input['gateway'] === 'netbanking_bob_v2')
        {
            $input['gateway'] = 'netbanking_bob';
            $input['payment']['gateway'] = 'netbanking_bob';
        }

        $request = $this->getMozartReconcileRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::GATEWAY_RECONCILE_RESPONSE,
            [
                'response'   => $response,
                'gateway'    => $this->gateway,
            ]);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        return $response;
    }

    protected function getMozartReconcileRequestArray($input)
    {
        if (($input['terminal'] instanceof TerminalEntity) === true)
        {
            $input['terminal'] = $input['terminal']->toArrayWithPassword();
        }

        $gateway = $input['gateway'];

        $content['entities'] = $input;

        $urlConfig = 'applications.mozart.' . $this->mode . '.url';

        $baseUrl = $this->app['config']->get($urlConfig);

        $url =  $baseUrl . 'payments/' . $gateway . '/v1/' . $this->action;

        $passwordConfig = 'applications.mozart.' . $this->mode . '.password';

        $authentication = [
            'api',
            $this->app['config']->get($passwordConfig)
        ];

        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Task-ID'    => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($content),
            'options' => [
                'auth' => $authentication
            ]
        ];
    }

    public function otpGenerate(array $input)
    {
        return $this->authorize($input);
    }

    public function mandateCreate($input)
    {
        parent::action($input, Action::AUTH_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
          'method' => $request['method'],
          'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_MANDATE_CREATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_MANDATE_CREATE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->createGatewayPaymentEntity($attributes, $input, Action::MANDATE_CREATE);

        return [
            'data'   => [
                Payment\Entity::VPA            => $input['terminal']['gateway_merchant_id2'],
                Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED,
            ],
        ];
    }

    public function mandateExecute($input)
    {
        parent::action($input, Action::PAY_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_MANDATE_EXECUTE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_MANDATE_EXECUTE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $this->createGatewayPaymentEntity($attributes, $input, Action::MANDATE_EXECUTE);

        return;
    }

    public function mandateUpdate($input)
    {
        parent::action($input, Action::AUTH_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url']
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_MANDATE_UPDATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentRequest($traceRes, $input, TraceCode::GATEWAY_MANDATE_UPDATE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $attributes = $this->getMappedAttributes($response);

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], Action::MANDATE_CREATE);

        $this->gatewayPayment = $this->updateGatewayPaymentEntityWithAction(
            $gatewayPayment,
            $response,
            true,
            Action::MANDATE_CREATE
        );
    }

    public function callbackOtpSubmit(array $input)
    {
        $this->verifyOtpAttempts($input['payment']);

        return $this->callback($input);
    }

    public function callback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        if ($this->fullyEncryptedFlow($input['payment']['gateway']) === false)
        {
            $gateway = $input['gateway'];

            $gateway = $this->parsegatewayresponse($input, $gateway);

            $traceRes = $this->getRedactedData($gateway);

            $this->traceGatewayPaymentRequest($traceRes, $input, TraceCode::PAYMENT_CALLBACK_REQUEST );

            unset($input['gateway']);

            $input['gateway']['redirect'] = $gateway;

            $request = $this->getMozartRequestArray($input);

            $traceReq = [
                'method' => $request['method'],
                'url' => $request['url'],
            ];

            $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_REQUEST);

            $response = $this->sendGatewayRequest($request);

            $traceRes = $this->getRedactedData($response);

            $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_RESPONSE);
        }
        else
        {
            $response = json_decode($input['gateway']['preProcessServerCallbackResponse'], true);
        }

        $action = Action::AUTHORIZE;

        if (($input['payment']['method'] === Payment\Method::UPI) and
            ($input['payment']['recurring_type'] === 'initial'))
        {
            $action = Action::MANDATE_CREATE;
        }

        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], $action);

        $this->gatewayPayment = $this->updateGatewayPaymentEntityWithAction(
                                                   $gatewayPayment,
                                                   $response,
                                                   true,
                                                   $action
                                             );

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $this->runCallbackValidationsIfApplicable($input, $response);

        $gatewayName = $input['payment']['gateway'];

        if ($this->immediateVerifyApplicable($input) === true)
        {
            $this->verifyCallback($input);
        }

        return $this->getResponseData($input, $response, $gatewayPayment);
    }

    public function omniPay(array $input)
    {
        parent::omniPay($input);

        $this->authorize($input);
    }

    public function createTerminal(array $input)
    {
        parent::createTerminal($input);

        $request = $this->getTerminalOnboardingMozartRequestArray($input);

        $traceReq = [
            'method'    => $request['method'],
            'url'       => $request['url'],
            'content'   => $request['content'],
        ];

        $this->traceGatewayTerminalOnboarding($traceReq, 'request', $input, TraceCode::GATEWAY_CREATE_TERMINAL_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayTerminalOnboarding($response, 'response', $input, TraceCode::GATEWAY_CREATE_TERMINAL_RESPONSE);
        // TODO check error codes and throw exception

        return $response;
    }

    public function verifyTerminal(array $input)
    {
        parent::verifyTerminal($input);

        $request = $this->getTerminalOnboardingMozartRequestArray($input);

        $traceReq = [
            'method'    => $request['method'],
            'url'       => $request['url'],
            'content'   => $request['content'],
        ];

        $this->traceGatewayTerminalOnboarding($traceReq, 'request', $input, TraceCode::GATEWAY_VERIFY_TERMINAL_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayTerminalOnboarding($response, 'response', $input, TraceCode::GATEWAY_VERIFY_TERMINAL_RESPONSE);

        return $response;
    }

    public function immediateVerifyApplicable($input)
    {
        if ( in_array($input['payment'][Payment\Entity::METHOD], [
                Payment\Method::NETBANKING,
                Payment\Method::WALLET,
            ], true) === true)
        {
            return true;
        }

        if (in_array($input['payment'][Payment\Entity::GATEWAY], Payment\Gateway::$immediateVerifyGateways, true) === true)
        {
            return true;
        }

        return false;
    }

    public function preProcessServerCallback($input, $gateway = null, $mode = null): array
    {
        $this->validateClientOnServerCallback($gateway);

        switch ($gateway)
        {
            case Payment\Gateway::UPI_AIRTEL:
                return json_decode($input[0], true);
            case Payment\Gateway::UPI_JUSPAY:
            case Payment\Gateway::UPI_CITI:
                return $input;
            case Payment\Gateway::NETBANKING_YESB:
                return $this->preProcessServerCallbackForYesb($input);
            case Payment\Gateway::WALLET_PHONEPE:
                return json_decode(base64_decode($input['response'], true), true);
            case Payment\Gateway::NETBANKING_KVB:
                return $this->preProcessServerCallbackForKvb($input, $mode);
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for prcessing S2S callback');
        }
    }

    public function preProcessMandateCallback($input, $gateway)
    {
        switch ($gateway)
        {
            case Payment\Gateway::UPI_MINDGATE:
                return json_decode($this->decrypt($input['payload']), true);
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for processing mandate callback');
        }
    }

    public function getPaymentIdFromMandateCallback($response, $gateway)
    {
        switch($gateway)
        {
            case Payment\Gateway::UPI_MINDGATE:
                return $response['requestInfo']['pspRefNo'];
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for processing mandate callback');

        }
    }

    public function getPaymentIdFromServerCallback(array $response, $gateway)
    {
        switch ($gateway)
        {
            case 'upi_airtel':
                return $response[UpiAirtelResponseFields::PAYMENT_ID];
            case 'upi_citi':
                return $response[UpiCiti\Fields::PUSH_NOTIFICATION_TO_SSG][UpiCiti\Fields::ORDER_NO];
            case Payment\Gateway::NETBANKING_YESB:
                return $response['data']['paymentId'];
            case Payment\Gateway::WALLET_PHONEPE:
                return $response['data']['transactionId'];
            case Payment\Gateway::NETBANKING_KVB:
                return $response['data']['paymentId'];
            case Payment\Gateway::UPI_JUSPAY:
                return $response['body'][UpiJuspay\Fields::MERCHANT_REQUEST_ID];
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for getting payment id from S2S callback');
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        if ($this->isFileBasedRefund($input['payment']['gateway']) === true)
        {
            return;
        }

        if ($this->isRefundDisableOnMozart($input['payment']['gateway']) === true)
        {
            throw new Exception\LogicException(
                'Refund not available on mozart',
                ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION);
        }

        switch ($this->terminal->getGatewayAcquirer())
        {
            case Payment\Gateway::GETSIMPL:
                $input['payment']['gateway'] = $input['payment']['wallet'];
                break;
        }

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->gatewayPayment = $this->createGatewayRefundEntity($attributes, $input, $this->action);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function capture(array $input)
    {
        parent::capture($input);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_CAPTURE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_CAPTURE_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->createGatewayPaymentEntity($attributes, $input, Action::CAPTURE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
    }

    public function verifyRefund(array $input)
    {
        $this->input = $input;
        $this->action = Action::VERIFY_REFUND;

        switch ($this->terminal->getGatewayAcquirer())
        {
            case Payment\Gateway::GETSIMPL:
                $input['payment']['gateway'] = $input['payment']['wallet'];
                break;
        }

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_REFUND_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_REFUND_VERIFY_RESPONSE);

        if ($response['success'] === true)
        {
            $gatewayEntity = $this->repo->findByRefundId($input['refund']['id']);

            if ($gatewayEntity !== null)
            {
                $gatewayEntity->setReceived(true);

                $this->repo->saveOrFail($gatewayEntity);
            }
            else
            {
                $attributes = $this->getMappedAttributes($response);

                $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::REFUND);
            }

            return true;
        }

        return false;
    }

    public function verify(array $input)
    {
        parent::verify($input);

        switch ($this->terminal->getGatewayAcquirer())
        {
            case Payment\Gateway::GETSIMPL:
                $input['payment']['gateway'] = $input['payment']['wallet'];
                break;
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    protected function verifyCallback($input)
    {
        parent::verify($input);

        $verify = new Verify($this->gateway, $input);

        $verifyResponseContent = $this->sendPaymentVerifyRequest($verify);

        //
        // If the status in callback and verify does not match
        //
        if ($verifyResponseContent['success'] !== true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_PAYMENT_VERIFICATION_ERROR,
                $verifyResponseContent['error']['gateway_error_code'] ?? 'gateway_error_code',
                $verifyResponseContent['error']['gateway_error_description'] ?? 'gateway_error_desc',
                [
                    'callback_response' => $input['gateway'],
                    'verify_response'   => $verify->verifyResponseContent,
                    'payment_id'        => $input['payment']['id'],
                    'gateway'           => $input['payment']['gateway']
                ]);
        }
    }

    public function sendPaymentVerifyRequest($verify)
    {
        $input = $verify->input;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->verifyResponseContent = $response;

        $verify->verifyResponse = null;

        $verify->verifyResponseBody = null;

        return $verify->verifyResponseContent;
    }

    protected function verifyPayment($verify)
    {
        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->gatewaySuccess = $content['success'];

        $this->checkApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->updateGatewayPaymentEntityWithAction($verify->payment, $content, true, Action::AUTHORIZE);

        $gatewayName = $this->getGateway($input);

        $this->restrictPaymentVerifyGatewayIfApplicable($gatewayName, $verify);

        return $verify->status;
    }

    protected function getMozartRequestArray($input, $mode = null)
    {
        if (($input['terminal'] instanceof TerminalEntity) === true)
        {
            $input['terminal'] = $input['terminal']->toArrayWithPassword();
        }

        $input['terminal'] = $this->updateTerminalFromConfig($input);

        $gateway = $this->getGateway($input);

        $prevStepName = $this->getPreviousStepName($gateway);

        $prevStepDB = $this->getPreviousStepForDB($gateway);

        if ($prevStepName != null)
        {
            $input['gateway'][$prevStepName] = $this->getPreviousData($input, $prevStepDB);
        }

        $content['entities'] = $input;

        $this->checkTpvAndModifyOrder($content, $input);

        $url = $this->getUrlForMozartRequest($input, 'payments', $mode);

        return $this->getAuthenticatedMozartRequestArray($url, $content, $mode);
    }

    protected function getTerminalOnboardingMozartRequestArray($input)
    {
        if (($input['terminal'] instanceof TerminalEntity) === true)
        {
            $input['terminal'] = $input['terminal']->toArrayWithPassword();
        }

        $content['entities'] = $input;

        $url = $this->getUrlForMozartRequest($input, 'onboarding');

        return $this->getAuthenticatedMozartRequestArray($url, $content);
    }

    protected function getUrlForMozartRequest($input, $prefix, $mode = null)
    {
        $mode = $this->mode ?? $mode;

        $urlConfig = 'applications.mozart.' . $mode . '.url';

        $baseUrl = $this->app['config']->get($urlConfig);

        $gateway = $this->getGateway($input);

        $url =  $baseUrl . $prefix . '/' .  $gateway . '/v1/' . $this->action;

        $isGooglePay = $this->isGooglePayGateway($input);

        if ($isGooglePay === true)
        {
            $url =  $baseUrl . $prefix . '/' . $input['gateway'] . '/v1/' . $this->action;
        }

        return $url;
    }

    protected function getAuthenticatedMozartRequestArray($url, $content, $mode = null)
    {
        $mode = $this->mode ?? $mode;

        $passwordConfig = 'applications.mozart.' . $mode . '.password';

        $authentication = [
            'api',
            $this->app['config']->get($passwordConfig)
        ];

        return [
            'url' => $url,
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Task-ID'    => $this->app['request']->getTaskId(),
            ],
            'content' => json_encode($content),
            'options' => [
                'auth' => $authentication
            ]
        ];
    }

    protected function updateTerminalFromConfig($input)
    {
        switch ($input['payment']['gateway'])
        {
            case Payment\Gateway::NETBANKING_CUB:
                $input['terminal']['gateway_secure_secret']      = $this->config['netbanking_cub']['gateway_secure_secret'];
                $input['terminal']['gateway_secure_secret2']     = $this->config['netbanking_cub']['gateway_secure_secret2'];
                $input['terminal']['gateway_terminal_password']  = $this->config['netbanking_cub']['gateway_terminal_password'];
                $input['terminal']['gateway_terminal_password2'] = $this->config['netbanking_cub']['gateway_terminal_password2'];
                break;
            case Payment\Gateway::NETBANKING_YESB:
                $input['terminal']['gateway_secure_secret']      = $this->config['netbanking_yesb']['gateway_secure_secret'];
        }

        return $input['terminal'];
    }

    protected function getPreviousStepName($gateway)
    {
        $previousActionForStep = [
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
                Action::REFUND => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_UBI => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY     => Action::PAY_VERIFY,
            ],
            Payment\Gateway::NETBANKING_SCB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY     => Action::PAY_VERIFY,
                Action::REFUND     => null,
                Action::VERIFY_REFUND => null
            ],
            Payment\Gateway::WALLET_PAYPAL => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::CAPTURE => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_INIT,
                Action::REFUND => Action::CAPTURE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_CUB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY     => Action::PAY_VERIFY,
            ],
            Payment\Gateway::NETBANKING_IBK => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY     => Action::PAY_VERIFY,
            ],
            Payment\Gateway::NETBANKING_IDBI => [
                Action::PAY_INIT    => null,
                Action::PAY_VERIFY  => Action::PAY_INIT,
                Action::VERIFY      => Action::PAY_VERIFY,
            ],
            Payment\Gateway::NETBANKING_YESB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY     => Action::PAY_VERIFY,
            ],
            Payment\Gateway::WALLET_PHONEPE => [
                Action::INTENT        => null,
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => Action::PAY_INIT,
                Action::VERIFY        => null,
                Action::REFUND        => null,
                Action::VERIFY_REFUND => null,
            ],
            Payment\Gateway::UPI_AIRTEL => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => Action::PAY_VERIFY,
                Action::REFUND        => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::UPI_JUSPAY => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => Action::PAY_VERIFY,
                Action::REFUND        => Action::PAY_VERIFY,
            ],
            Payment\Gateway::UPI_CITI => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::PAY_VERIFY,
                Action::REFUND => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY => Action::PAY_VERIFY,
            ],
            Payment\Gateway::NETBANKING_CBI => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::PAY_INIT,
                Action::VERIFY     => Action::PAY_VERIFY,
            ],
            Payment\Gateway::GOOGLE_PAY => [
                Action::PAY_INIT => null,
            ],
            Payment\Gateway::UPI_MINDGATE => [
                Action::AUTH_INIT         => null,
                Action::PAY_INIT          => null,
                Action::PAY_VERIFY        => null,
            ],
            Payment\Gateway::NETBANKING_KVB =>  [
                Action::PAY_INIT    =>  null,
                Action::PAY_VERIFY  =>  null,
                Action::VERIFY      =>  Action::PAY_VERIFY,
            ],
            Payment\Gateway::GETSIMPL   =>  [
                Action::CHECKACCOUNT    =>  null,
                Action::PAY_INIT        =>  null,
                Action::REFUND          =>  Action::PAY_INIT,
                Action::VERIFY          =>  Action::PAY_INIT,
                Action::VERIFY_REFUND   =>  Action::REFUND,
            ]
        ];

        return $previousActionForStep[$gateway][$this->action];
    }

    protected function getPreviousStepForDB($gateway)
    {
        $previousActionForData = [
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::NETBANKING_YESB => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::AUTHORIZE,
            ],
            Payment\Gateway::WALLET_PHONEPE => [
                Action::INTENT => null,
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY => null,
                Action::REFUND => null,
                Action::VERIFY_REFUND => null,
            ],
            Payment\Gateway::NETBANKING_SCB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
                Action::REFUND     => null,
                Action::VERIFY_REFUND => null
            ],
            Payment\Gateway::WALLET_PAYPAL => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::CAPTURE => Action::AUTHORIZE,
                Action::REFUND => Action::CAPTURE,
                Action::VERIFY_REFUND => Action::REFUND,
                Action::VERIFY => Action::AUTHORIZE,
            ],
            Payment\Gateway::UPI_AIRTEL => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::UPI_JUSPAY => [
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => Action::AUTHORIZE,
                Action::REFUND => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_SIB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_CBI => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_CUB => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_UBI => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_IBK => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::NETBANKING_IDBI => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => Action::AUTHORIZE,
                Action::VERIFY     => Action::AUTHORIZE,
            ],
            Payment\Gateway::GOOGLE_PAY => [
                Action::PAY_INIT => null,
            ],
            Payment\Gateway::UPI_CITI => [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => Action::AUTHORIZE,
                Action::REFUND          => Action::AUTHORIZE,
                Action::VERIFY_REFUND   => Action::REFUND,
            ],
            Payment\Gateway::UPI_MINDGATE => [
                Action::AUTH_INIT       => null,
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
            ],
            Payment\Gateway::NETBANKING_KVB =>  [
                Action::PAY_INIT    =>  null,
                Action::PAY_VERIFY  =>  null,
                Action::VERIFY      =>  Action::AUTHORIZE,
            ],

            Payment\Gateway::GETSIMPL   =>  [
                Action::CHECKACCOUNT    =>  null,
                Action::PAY_INIT        =>  null,
                Action::REFUND          =>  Action::AUTHORIZE,
                Action::VERIFY          =>  Action::AUTHORIZE,
                Action::VERIFY_REFUND   =>  Action::REFUND,
            ]
        ];

        return $previousActionForData[$gateway][$this->action];
    }

    protected function getPreviousData($input, $prevActionForData)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
            $input['payment']['id'], $prevActionForData);

        $jsonRaw = json_decode($gatewayPayment['raw'], true);

        return $jsonRaw;
    }

    protected function sendGatewayRequest($request)
    {
        $response = parent::sendGatewayRequest($request);

        return $this->jsonToArray($response->body, true);
    }

    protected function getRedactedData($data)
    {
        unset($data['data']['Key']);

        unset($data['data']['enqinfo']['0']['Key']);

        unset($data['data']['enqinfo']['0']['MOBILENO']);

        unset($data['data']['MobileNo']);

        unset($data['data']['valkey']);

        unset($data['otp']);

        unset($data['data']['_raw']);

        unset($data['_raw']);

        return $data;
    }

    protected function createGatewayPaymentEntity($attributes, $input, $action)
    {
        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $amount    = $input['payment']['amount'];
        $gateway   = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setAmount($amount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function createGatewayRefundEntity($attributes, $input, $action)
    {

        $redactedRaw = $this->getRedactedData($attributes['raw']);
        $attributes['raw'] = json_encode($redactedRaw);

        $gatewayPayment = $this->getNewGatewayPaymentEntity();

        $paymentId = $input['payment']['id'];
        $refundId  = $input['refund']['id'];
        $refundAmount = $input['refund']['amount'];
        $gateway      = $input['payment']['gateway'];

        $gatewayPayment->setAction($action);
        $gatewayPayment->setRefundId($refundId);
        $gatewayPayment->setAmount($refundAmount);
        $gatewayPayment->setPaymentId($paymentId);
        $gatewayPayment->setGateway($gateway);

        $gatewayPayment->fill($attributes);

        $this->repo->saveOrFail($gatewayPayment);

        $this->gatewayPayment = $gatewayPayment;

        return $gatewayPayment;
    }

    protected function updateGatewayPaymentEntityWithAction(
        MozartEntity $gatewayPayment,
        array $attributes,
        bool $mapped = true,
        $action = null)
    {
        if ($mapped === true)
        {
            $attributes = $this->getMappedAttributes($attributes);
        }

        $raw = $gatewayPayment->getRaw();

        $rawArray = json_decode($raw, true);

        $action = $action ?: $this->action;

        $redactedRaw = $this->getRedactedData($attributes['raw']);

        $finalRaw = array_merge($rawArray, $redactedRaw);

        $attributes['raw'] = json_encode($finalRaw);

        $gatewayPayment->setAction($action);

        $gatewayPayment->fill($attributes);

        $this->getRepository()->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    public function syncGatewayTransaction(array $gatewayTransaction, array $input)
    {
        $paymentId = $input[E::PAYMENT][Entity::ID];

        $action = $input[Entity::ACTION];

        $gatewayEntity = $this->repo->findByPaymentIdAndAction($paymentId, $action);

        $mappedAttributes = $this->getMappedAttributes([
            'data' => $gatewayTransaction
        ]);

        if ($gatewayEntity === null)
        {
            $gatewayEntity = $this->createGatewayPaymentEntity($mappedAttributes, $input, $action);
        }
        else
        {
            $this->updateGatewayPaymentEntityWithAction($gatewayEntity, $mappedAttributes, false, $action);
        }
    }

    protected function getPaymentToVerify(Verify $verify)
    {
        $gatewayPayment = $this->repo->findByPaymentIdAndAction(
            $verify->input['payment']['id'], Action::AUTHORIZE);

        $verify->payment = $gatewayPayment;

        return $gatewayPayment;
    }

    public function preProcessServerCallbackForYesb($input): array
    {
        $this->action = Action::PAY_VERIFY;

        $content['gateway']['redirect'] = $input;

        $content['payment']['gateway'] = Payment\Gateway::NETBANKING_YESB;

        $content['terminal']['gateway_secure_secret'] = $this->config['netbanking_yesb']['gateway_secure_secret'];

        $request = $this->getMozartRequestArray($content, Mode::LIVE);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        // Till here response was fully encrypted and we did not know the payment id
        $paymentDetails['payment']['id'] = $response['data']['paymentId'];

        $this->traceGatewayPaymentResponse($traceRes, $paymentDetails, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        return $response;
    }

    public function preProcessServerCallbackForKvb($input, $mode): array
    {
        $this->action = Action::PAY_VERIFY;

        $content['gateway']['redirect'] = $input;

        $content['payment']['gateway'] = Payment\Gateway::NETBANKING_KVB;

        $content['terminal']['gateway_secure_secret'] = "";

        $request = $this->getMozartRequestArray($content, $mode);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        // Till here response was fully encrypted and we did not know the payment id
        $paymentDetails['payment']['id'] = $response['data']['paymentId'];

        $this->traceGatewayPaymentResponse($traceRes, $paymentDetails, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        return $response;
    }

    protected function runCallbackValidationsIfApplicable($input, $response)
    {
        if ($this->shouldRunCallbackValidations($input['payment']['gateway']) === true)
        {
            if (isset($response['data']['paymentId']) === true)
            {
                $this->assertPaymentId($input['payment']['id'], $response['data']['paymentId']);
            }
            else
            {
                throw new Exception\LogicException(
                    'Payment Id should have been passed for validation');
            }

            if (isset($response['data']['amount']) === true)
            {
                // Adding this check as all the mozart gateways do not support formatted amount response
                // They will have to be migrated eventually as well to this flow. When all gateways are
                // migrated, this check should be removed
                if ($this->formattedResponseAmountGateway($input['payment']['gateway']))
                {
                    $dbAmount      = number_format($input['payment']['amount'] / 100, 2, '.', '');
                    $gatewayAmount = number_format($response['data']['amount'], 2, '.', '');
                }
                else
                {
                    $dbAmount      = $input['payment']['amount'];
                    $gatewayAmount = $response['data']['amount'];
                }

                $this->assertAmount($dbAmount, $gatewayAmount);
            }
            else
            {
                throw new Exception\LogicException(
                    'Amount should have been passed for validation');
            }
        }
    }

    protected function shouldRunCallbackValidations($gateway)
    {
        $validationGateways = [
            Payment\Gateway::UPI_AIRTEL,
            Payment\Gateway::UPI_CITI,
            Payment\Gateway::UPI_JUSPAY,
            Payment\Gateway::WALLET_PHONEPE,
            Payment\Gateway::WALLET_PAYPAL,
            Payment\Gateway::NETBANKING_UBI,
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
            Payment\Gateway::NETBANKING_SCB,
            Payment\Gateway::NETBANKING_CBI,
            Payment\Gateway::NETBANKING_CUB,
            Payment\Gateway::NETBANKING_IBK,
            Payment\Gateway::NETBANKING_IDBI,
            Payment\Gateway::NETBANKING_KVB,
        ];

        return in_array($gateway, $validationGateways, true);
    }

    protected function fullyEncryptedFlow($gateway)
    {
        $fullyEncryptedInputGateways = [
          Payment\Gateway::NETBANKING_YESB,
          Payment\Gateway::NETBANKING_KVB,
        ];

        return in_array($gateway, $fullyEncryptedInputGateways, true);
    }

    protected function formattedResponseAmountGateway($gateway)
    {
        $formattedAmountGateways = [
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
            Payment\Gateway::NETBANKING_CBI,
            Payment\Gateway::NETBANKING_UBI,
            Payment\Gateway::NETBANKING_CUB,
            Payment\Gateway::NETBANKING_IBK,
            Payment\Gateway::NETBANKING_IDBI,
            Payment\Gateway::UPI_AIRTEL,
            Payment\Gateway::NETBANKING_KVB,
        ];

        return in_array($gateway, $formattedAmountGateways, true);
    }

    protected function restrictPaymentVerifyGatewayIfApplicable($gateway, $verify)
    {
        $verifyRestrictedGateways = [
            Payment\Gateway::NETBANKING_KVB,
        ];

        if (in_array($gateway, $verifyRestrictedGateways, true) === true)
        {
            if (($verify->match === true) and
                ($this->app['basicauth']->isCron() === true))
            {
                throw new Exception\PaymentVerificationException(
                    $verify->getDataToTrace(),
                    null,
                    VerifyAction::FINISH
                );
            }
        }
    }

    protected function getResponseData($input, $mozartResponse, $gatewayPayment)
    {
        if ((isset($input['gateway']['redirect']['mandateDtls']) === true) and
            ($input['gateway']['redirect']['mandateDtls'][0]['mandateType'] === 'UPDATE'))
        {
           $response = [
               'amount'     => $mozartResponse['data']['amount'],
               'start_time' => $mozartResponse['data']['start_time']
           ];

           return $response;
        }
        if (($input['payment']['recurring_type'] === Payment\RecurringType::INITIAL) and
            ($input['payment']['method'] === Payment\Method::UPI))
        {
            $response = [
                'recurring_status' => 'confirmed'
            ];
        }
        elseif ($input['payment']['method'] === Payment\Method::UPI)
        {
            $response = [
                'acquirer' => [
                    Payment\Entity::VPA         => $input['payment']['vpa'] ?? $input['terminal']['gateway_merchant_id2'],
                    Payment\Entity::REFERENCE16 => $mozartResponse['data']['rrn'] ?? null,
                ]
            ];
        }
        elseif ($input['payment']['method'] === Payment\Method::NETBANKING)
        {
            $acquirerData = $this->getAcquirerData($input, $gatewayPayment);

            $response = $this->getCallbackResponseData($input, $acquirerData);
        }
        else
        {
            $response = $mozartResponse;
        }

        return $response;
    }

    protected function checkTpvAndModifyOrder(& $content, $input)
    {
        if ($this->action === Action::PAY_INIT and $this->getGateway($input) !== Payment\Gateway::GOOGLE_PAY)
        {
            $isTpvEnabled = $input['merchant']->isTPVRequired();

            if ($isTpvEnabled === false)
            {
                if (isset($content['entities']['order']['account_number']) === true)
                {
                    $content['entities']['order']['account_number'] = null;
                }

                if (isset($content['entities']['order']['bank_account']['account_number']) === true)
                {
                    $content['entities']['order']['bank_account']['account_number'] = null;
                }
            }

            $content['entities']['gateway']['features']['tpv'] = $isTpvEnabled;
        }
    }

    protected function isFileBasedRefund($gateway)
    {
        $fileBasedGateways = [
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_SIB,
            Payment\Gateway::NETBANKING_CBI,
            Payment\Gateway::NETBANKING_CUB,
            Payment\Gateway::NETBANKING_IBK,
            Payment\Gateway::NETBANKING_IDBI,
            Payment\Gateway::NETBANKING_KVB,
        ];

        return in_array($gateway, $fileBasedGateways, true);
    }

    protected function getGateway($input)
    {
        if (
            (in_array($this->action, [Action::CREATE_TERMINAL, ACTION::VERIFY_TERMINAL])) or
            ((isset($input['gateway']) === true) and ($input['gateway'] === Payment\Gateway::GOOGLE_PAY))
            )
        {
            return $input['gateway'];
        }

        return $input['payment']['gateway'];
    }

    protected function isUpiIntent($input): bool
    {
        return (isset($input['upi']['flow']) and ($input['upi']['flow'] === 'intent'));
    }

    protected function isGooglePayGateway($input)
    {
        if ($this->getGateway($input) === Payment\Gateway::GOOGLE_PAY)
        {
            return true;
        }

        return false;
    }

    // This function is used when the callback does not come as key-value pairs
    // the encrypted value comes as key so as default "encdata" is added as key and the encrypted string as
    // its value. This is a temporary solution.
    // Note: Modify gateway data to be transformed for gateways

    protected function parsegatewayresponse($input, $gatewayInput)
    {
        if ($input['payment']['gateway'] == Payment\Gateway::NETBANKING_IDBI)
        {
            $key = array_keys($gatewayInput)[0];

            if($gatewayInput[$key] === '')
            {
                $gatewayInput['encdata'] = $key;

                unset($gatewayInput[$key]);
            }
        }
        return $gatewayInput;
    }

    protected function isRefundDisableOnMozart($gateway)
    {
        return in_array($gateway, [
            Payment\Gateway::UPI_CITI,
        ], true);
    }

    protected function validateClientOnServerCallback($gateway)
    {
        if (in_array($gateway, Payment\Gateway::$verifyClientOnS2s, true))
        {
            $allowedIps = explode(',', $this->config[$gateway]['allowed_s2p_client_ips']);
            $clientIps  = $this->request->getClientIps();

            foreach ($allowedIps as $allowedIp)
            {
                if (in_array(trim($allowedIp), $clientIps, true) === true)
                {
                    return;
                }
            }

            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_BLOCKED_IP,
                null,
                'S2S request received from wrong blocked IP',
                [
                    'gateway'       => $gateway,
                    'client_ips'    => $clientIps,
                    'allowed_ips'   => $allowedIps,
                ]
            );

        }
    }

    public function decrypt(string $cipherText)
    {
        return $this->getCipherInstance()
            ->decrypt($cipherText);
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }

    protected function getCipherInstance()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return new Crypto($key);
    }

    /**
     * For Netbanking gateways we store the bank's reference number in the payment entity.
     * @param $input
     * @param $gatewayPayment
     * @return array
     */
    protected function getAcquirerData($input, $gatewayPayment)
    {
        $data = $gatewayPayment->getDataAttribute();

        return [
            'acquirer' => [
                Payment\Entity::REFERENCE1 => $data['bank_payment_id'] ?? null
            ]
        ];
    }

    protected function shouldCacheToken($token, $input)
    {
        if ((empty($token) === false) and ($input['provider'] === Payment\Gateway::GETSIMPL))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    protected function cacheValue($token, $input)
    {
        $contact = $input['contact'];

        $merchantId = $this->terminal[Terminal\Entity::MERCHANT_ID];

        $cacheKey = strtolower($input['provider']) . '_' . $contact . '_' . $merchantId;

        $key = sprintf(self::CACHE_KEY, $cacheKey);

        $this->createCacheData($key, $token);
    }

    protected function createCacheData($key, $value, $ttl = self::CARD_CACHE_TTL)
    {
        $this->app['cache']->put($key, $value, $ttl);
    }

    protected function fetchCacheData($input)
    {
        $contact = $input['payment']['contact'];

        $merchantId = $this->terminal[Terminal\Entity::MERCHANT_ID];

        $cacheKey = $input['payment']['wallet'] . '_' . $contact . '_' . $merchantId;

        $key = sprintf(self::CACHE_KEY, $cacheKey);

        return $this->app['cache']->get($key);
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = $this->extractPaymentsPropertiesAuthorizedFailedTrait($gatewayPayment);

        $gateway = $gatewayPayment->getGateway();

        if ($this->isNetbankingGateway($gateway) === true)
        {
            $data = $gatewayPayment->getDataAttribute();

            if (isset($data['bank_payment_id']) === true)
            {
                $response['acquirer'][Payment\Entity::REFERENCE1] = $data['bank_payment_id'];
            }
        }

        return $response;
    }

    protected function isNetbankingGateway($gateway)
    {
        return in_array($gateway, Payment\Gateway::$methodMap[Payment\Method::NETBANKING], true);
    }
}
