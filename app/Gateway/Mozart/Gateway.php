<?php

namespace RZP\Gateway\Mozart;

use RZP\Exception;
use RZP\Gateway\Base;
use RZP\Models\CardMandate\MandateHubs\MandateHubs;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Upi\Mozart;
use RZP\Gateway\Base\Verify;
use RZP\Models\Customer\Token;
use RZP\Constants\Entity as E;
use RZP\Gateway\Upi\Base\Type;
use RZP\Gateway\Base\VerifyResult;
use RZP\Gateway\Upi\Mindgate\Crypto;
use RZP\Gateway\Base\AuthorizeFailed;
use RZP\Gateway\Upi\Base\MandateTrait;
use RZP\Gateway\Upi\Base\RecurringTrait;
use RZP\Gateway\Upi\Base\CommonGatewayTrait;
use RZP\Gateway\Upi\Base\Entity as UpiEntity;
use RZP\Models\Payment\Processor\App as AppMethod;
use RZP\Gateway\Mozart\Entity as MozartEntity;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\Payment\Verify\Action as VerifyAction;

class Gateway extends Base\Gateway
{
    // set Upi Aquirer as null for all upi gateways.
    const ACQUIRER = null;

    use AuthorizeFailed {
        extractPaymentsProperties as extractPaymentsPropertiesAuthorizedFailedTrait;
    }
    use CardMandate;

    use CommonGatewayTrait;
    protected $gateway = 'mozart';

    const CACHE_KEY    = 'gateway:cache_key_%s';
    const BAJAJ_FINSERV_REST_API =  'bajaj_finserv_rest_api';
    const UPI_SBI_V3_MIGRATION = 'upi_sbi_v3_migration';

    protected $map = [
        'data'      => Entity::RAW,
    ];

    // Mozart Gateways for which amount needs to be checked
    protected $gatewaysForAmountCheck = [
        Payment\Gateway::UPI_AIRTEL => true
    ];

    public function checkAccount(array $input)
    {
        if($this->terminal['gateway'] === Payment\Gateway::SHARP)
        {
            return;
        }

        $this->action($input, Action::CHECKACCOUNT);

        $provider = strtoupper($input['provider']);

        $input['terminal'] = $this->terminal;

        switch ($input['method'])
        {
            case Payment\Gateway::PAYLATER:
                if ($input['provider'] === Payment\Processor\PayLater::ICICI)
                {
                    $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;
                }
                else
                {
                    $input['payment']['gateway'] = $input['provider'];
                }
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

        if ($response['success'] !== true and $input['method'] === Payment\Gateway::PAYLATER and
            ($input['provider'] === Payment\Processor\PayLater::ICICI or $input['provider'] === Payment\Processor\PayLater::GETSIMPL))
        {
            $meta_data = [];

            if(array_key_exists('order_id', $input))
            {
                $meta_data['order_id'] = $input['order_id'];
            }


            $response['meta_data'] = $meta_data;
        }

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response, null);

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

        if (($this->getGateway($input) === 'upi_airtel') and ($input['upi']['flow'] == 'intent'))
        {
            parent::action($input, Action::INTENT);
        }

        if (($this->isContactMandatoryGateway($input) === true) and
            ($input['payment']['contact'] == Payment\Entity::DUMMY_PHONE))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        if ($this->isS2SFlow($input) === true)
        {
            parent::action($input, Action::AUTHENTICATE_INIT);
        }

        if ($this->getGateway($input) === 'wallet_paypal')
        {
            parent::action($input, Action::AUTH_INIT);
        }

        if (is_null($this->terminal) === false)
        {
            switch ($this->terminal->getGatewayAcquirer())
            {
                case Payment\Gateway::GETSIMPL:

                    if(empty($input['simpltoken']) === true)
                    {
                        $input['simpltoken'] = $this->fetchCacheData($input);
                    }

                    $input['payment']['gateway'] = $input['payment']['wallet'];
                    break;

                case Payment\Processor\PayLater::ICICI:
                    $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;
                    break;
            }
        }

        list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
            $input,
            TraceCode::GATEWAY_AUTHORIZE_REQUEST,
            TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
            false);

        if ((isset($input['payment']['gateway']) === true) and
            ($this->isUpiGateway($input['payment']['gateway']) === true))
        {
            $mozart = new Mozart\Gateway();

            $upiAttributes = $response['data']['upi'] ?? [];

            $mozart->createOrUpdateUpiEntityForMozartGateways($input, $upiAttributes, Action::AUTHORIZE);
        }

        $this->gatewayPayment = $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        if ($this->action === Action::INTENT)
        {
            $data = [
                'intent_url' => $response['next']['redirect']['url'],
            ];

            return ['data' => $data];
        }

        $intentGatewaysWithPayInit = [
            Payment\Gateway::UPI_JUSPAY,
            Payment\Gateway::CRED
        ];

        // In cred, we get to know the payment flow after making pay init request
        // In pay init response cred will tell us its a collect/intent flow.
        // In intent cred returns the intent url in the response.
        if (($this->action === Action::PAY_INIT) and
            (in_array($this->getGateway($input), $intentGatewaysWithPayInit, true)))
        {
            if (($this->isUpiIntent($input) === true) or
                (($this->getGateway($input) === Payment\Gateway::CRED) and
                (empty($response['next']['redirect']['url']) === false) and
                (empty($response['data']['checkout_mode']) === false) and
                ($response['data']['checkout_mode'] !== 'web')))
            {
                $data = [
                    'intent_url' => $response['next']['redirect']['url'],
                ];

                return ['data' => $data];
            }
        }

        if ($this->getGateway($input) === 'upi_sbi')
        {
            return $response;
        }

        if (($input['payment']['method'] === 'upi') or
            (($input['payment']['method'] === Payment\Method::APP) and
             ($input['payment']['wallet'] === AppMethod::CRED) and
             (empty($response['data']['checkout_mode']) === false) and
             ($response['data']['checkout_mode'] !== 'web')))
        {
            $merchantId = ($input['payment']['method'] === Payment\Method::APP) ? $input['terminal']['gateway_merchant_id'] :
            $input['terminal']['gateway_merchant_id2'];

            return [
                'data'   => [
                    Payment\Entity::VPA => $merchantId
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
        if (($input['payment']['gateway'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['method'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['wallet'] === Payment\Processor\PayLater::ICICI))
        {
            return $this->paylaterIciciOtpGenerate($input);
        }
        else
        {
            return $this->authorize($input);
        }
    }

    public function paylaterIciciOtpGenerate(array $input)
    {
        parent::action($input, Action::AUTH_INIT);

        $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::AUTH_INIT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->trace->info(
            TraceCode::AUTH_INIT_RESPONSE,
            [
                'response' => $traceRes,
            ]);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        return $this->getOtpSubmitRequest($input);
    }

    public function mandateCreate($input)
    {
        if ($this->isUpiRecurringPayment($input['payment']) === true)
        {
            parent::action($input, Action::AUTH_INIT);

            $traceReq = TraceCode::GATEWAY_MANDATE_CREATE_REQUEST;
            $traceRes = TraceCode::GATEWAY_MANDATE_CREATE_RESPONSE;

            list($response) = $this->sendMozartRequestAndGetResponse($input, $traceReq, $traceRes, false);

            return $response;
        }

        parent::action($input, Action::PAY_INIT);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
          'method' => $request['method'],
          'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_MANDATE_CREATE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_MANDATE_CREATE_RESPONSE);

        $attributes = $this->getMappedAttributes($response);

        $this->createGatewayPaymentEntity($attributes, $input, Action::AUTHORIZE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        /**
         *  In case of otm, We require upi entity to be sent in response,
         *  We expect mozart entity to send exact keys for upi entity, to properly
         *  the values.
         */

        return [
            'data'   => [
                Payment\Entity::VPA            => $input['payment']['vpa'] ?? ($response['vpa'] ?? null),
            ],
            'upi'                              => array_only($response['data'], (new UpiEntity())->getFillable()),
        ];
    }

    // Not Used
    public function authorizeRecurring($input)
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
                Payment\Entity::VPA            => $input['payment']['vpa'] ?? null,
                'token' => [
                    Token\Entity::RECURRING_STATUS => Token\RecurringStatus::INITIATED,
                ],
            ],
            'upi' => array_only($response['data'], (new UpiEntity())->getFillable()),
        ];
    }

    public function mandateExecute($input)
    {
        parent::action($input, Action::CAPTURE);

        list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
            $input,
            TraceCode::GATEWAY_MANDATE_EXECUTE_REQUEST,
            TraceCode::GATEWAY_MANDATE_EXECUTE_RESPONSE, false);

        $this->createGatewayPaymentEntity($attributes, $input, Action::CAPTURE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

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

        if (($input['payment']['gateway'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['method'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['wallet'] === Payment\Processor\PayLater::ICICI))
        {
            return $this->paylaterIciciCallbackOtpSubmit($input);
        }
        else
        {
            return $this->callback($input);
        }

    }

    public function paylaterIciciCallbackOtpSubmit(array $input)
    {
        parent::action($input,Action::AUTH_VERIFY);

        $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::AUTH_VERIFY_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->trace->info(
            TraceCode::AUTH_VERIFY_RESPONSE,
            [
                'response' => $traceRes,
            ]);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $callbackResponse = $this->getCallbackResponseData($input);

        return $callbackResponse;
    }

    public function checkBalance(array $input)
    {
        if (($input['payment']['gateway'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['method'] === Payment\Gateway::PAYLATER)
            and ($input['payment']['wallet'] === Payment\Processor\PayLater::ICICI))
        {
            $this->paylaterIciciCheckBalance($input);
        }
        else
        {
            parent::checkBalance($input);
        }
    }

    public function paylaterIciciCheckBalance(array $input)
    {
        parent::action($input, Action::CHECK_BALANCE);

        $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::CHECK_BALANCE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->trace->info(
            TraceCode::CHECK_BALANCE_RESPONSE,
            [
                'response' => $traceRes,
            ]);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        $this->assertPaymentAmountWithBalance($input, $response);
    }

    public function debit(array $input)
    {
        if (($input['payment']['gateway'] === Payment\Gateway::PAYLATER) and
            ($input['payment']['method'] === Payment\Gateway::PAYLATER) and
            ($input['payment']['wallet'] === Payment\Processor\PayLater::ICICI))
        {
            $this->authorize($input);
        }

        else if ($this->isDebitGateway($input))
        {
            parent::action($input,Action::DEBIT);

            $request = $this->getMozartRequestArray($input);

            $traceReq = [
                'method' => $request['method'],
                'url' => $request['url'],
            ];

            $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_REQUEST);

            $response = $this->sendGatewayRequest($request);

            $traceRes = $this->getRedactedData($response);

            $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_RESPONSE);

            $attributes = $this->getMappedAttributes($response);

            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], Action::AUTHORIZE);

            $this->gatewayPayment = $this->updateGatewayPaymentEntityWithAction(
                $gatewayPayment,
                $response,
                true,
                Action::AUTHORIZE
            );

            $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
        }
        else if ($this->isUpiRecurringPayment($input['payment']) === true)
        {
            parent::action($input, Action::PAY_INIT);

            $request = $this->getMozartRequestArray($input);

            $traceReq = [
                'method' => $request['method'],
                'url' => $request['url'],
            ];

            $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_REQUEST);

            $response = $this->sendGatewayRequest($request);

            $traceRes = $this->getRedactedData($response);

            $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_PAYMENT_DEBIT_RESPONSE);

            return $response;
        }
        else
        {
            parent::debit($input);
        }
    }

    public function preDebit(array $input)
    {
        parent::action($input, Action::NOTIFY);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url' => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_SUPPORT_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_SUPPORT_RESPONSE);

        return $response;
    }

    public function callback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        if ($this->getGateway($input) === 'wallet_paypal')
        {
            parent::action($input, Action::AUTH_VERIFY);
        }

        if ($this->getGateway($input) === Payment\Gateway::UPI_AIRTEL)
        {
            $version = $input['gateway']['data']['version'] ?? '';

            if ($version === 'v2')
            {
                return $this->upiCallback($input);
            }
        }

        if ($this->isS2SFlow($input) === true)
        {
            parent::action($input, Action::AUTHENTICATE_VERIFY);

            $gateway = $input['gateway'];

            unset($input['gateway']);

            $input['gateway']['redirect'] = $gateway;

            $gatewayPayment = $this->repo->findByPaymentIdAndActionOrFail(
                $input['payment']['id'], Action::AUTHORIZE)->toArray();

            $gatewayPayment = $gatewayPayment['data'];
            /*
             * Merges the array like so:
             * {
             *  "redirect":{"otp": "111111"},
             *  "BankReferenceNo": "bank_ref"
             * }
             */
            $input['gateway'] = array_merge($input['gateway'], $gatewayPayment);

            list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
                $input,
                TraceCode::GATEWAY_PAYMENT_REQUEST,
                TraceCode::GATEWAY_PAYMENT_RESPONSE,
                false);
        }

        else if ($this->fullyEncryptedFlow($input['payment']['gateway']) === false)
        {
            if (($this->isUpiRecurringPayment($input['payment']) === true) and
                ($this->isMandateCreateCallback($input['gateway'], $input['payment']['gateway']) === true))
            {
                parent::action($input, Action::AUTH_VERIFY);
            }

            $gateway = $input['gateway'];

            $gateway = $this->parsegatewayresponse($input, $gateway);

            $traceRes = $this->getRedactedData($gateway);

            $this->traceGatewayPaymentRequest($traceRes, $input, TraceCode::PAYMENT_CALLBACK_REQUEST);

            unset($input['gateway']);

            $input['gateway']['redirect'] = $gateway;

            list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
                $input,
                TraceCode::GATEWAY_PAYMENT_REQUEST,
                TraceCode::GATEWAY_PAYMENT_RESPONSE,
                false);
        }
        else
        {
            $gateway = $input['payment']['gateway'];

            if($gateway === Payment\Gateway::UPI_SBI)
            {
                $response = $input['gateway'];
            }
            else
            {
                $response = json_decode($input['gateway']['preProcessServerCallbackResponse'], true);
            }
        }

        $action = Action::AUTHORIZE;

        if (($input['payment']['method'] === Payment\Method::UPI) and
            ($input['payment']['recurring_type'] === 'initial'))
        {
            $action = Action::MANDATE_CREATE;
        }

        if ((isset($input['payment']['gateway']) === true) and
            ($this->isUpiGateway($input['payment']['gateway']) === true))
        {
            $mozart = new Mozart\Gateway();

            $attributes = $response['data']['upi'] ?? [];

            $mozart->createOrUpdateUpiEntityForMozartGateways($input, $attributes, Action::AUTHORIZE);
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

        if ($this->isS2SFlow($input) === true)
        {
            parent::action($input, Action::PAY_INIT);

            // This was set when we sent auth_verify request
            unset($input['gateway']['redirect']);

            list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
                $input,
                TraceCode::GATEWAY_AUTHORIZE_REQUEST,
                TraceCode::GATEWAY_AUTHORIZE_RESPONSE,
                false);

            $this->gatewayPayment = $this->updateGatewayPaymentEntityWithAction(
                $gatewayPayment,
                $response,
                true,
                $action
            );

            $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
        }

        if ($this->immediateVerifyApplicable($input) === true)
        {
            $this->verifyCallback($input);
        }

        return $this->getResponseData($input, $response, $gatewayPayment);
    }

    public function upiRecurringCallback(array $input)
    {
        parent::action($input, Action::PAY_VERIFY);

        // Mandate create request has action Authenticate, first and auto recurring have action Authorize
        $isMandateCreate = ($input[UpiEntity::UPI][UpiEntity::ACTION] === Base\Action::AUTHENTICATE);

        if ($isMandateCreate === true)
        {
            parent::action($input, Action::AUTH_VERIFY);
        }

        // Some how auth verify is expecting the gateway data in redirect field
        $input['gateway']['redirect'] = array_pull($input, 'gateway');

        $traceReq = TraceCode::GATEWAY_SUPPORT_REQUEST;
        $traceRes = TraceCode::GATEWAY_SUPPORT_RESPONSE;

        list($response) = $this->sendMozartRequestAndGetResponse($input, $traceReq, $traceRes, false);

        if ($response['success'] === true)
        {
            // These validations will throw Logic Exceptions, which we do not need to suppress
            if ($isMandateCreate === true)
            {
                $this->runMandateCreateCallbackValidations($input, $response);
            }
            else
            {
                $this->runCallbackValidationsIfApplicable($input, $response);
            }
        }

        return $response;
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

        $traceContent = $this->getTraceContent($request['content']);

        $traceReq = [
            'method'    => $request['method'],
            'url'       => $request['url'],
            'content'   => $traceContent,
        ];

        $this->traceGatewayTerminalOnboarding($traceReq, 'request', $input, TraceCode::GATEWAY_CREATE_TERMINAL_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayTerminalOnboarding($response, 'response', $input, TraceCode::GATEWAY_CREATE_TERMINAL_RESPONSE);
        // TODO check error codes and throw exception

        return $response;
    }

    public function decrypt(array $input)
    {
        $this->input = $input;

        $this->action = Action::DECRYPT;

        $request = $this->getGooglePayCardsDecryptionMozartRequestArray($input);

        $this->trace->info(
            TraceCode::MOZART_SERVICE_REQUEST,
            [
                'url'      => $request['url'],
                'gateway'  => $this->gateway,
                'input'    => $request['content'],
            ]);

        $response = $this->sendGatewayRequest($request);

        $this->trace->info(
            TraceCode::MOZART_SERVICE_RESPONSE,
            [
                'gateway'  => $this->gateway,
            ]);

        return $response;
    }

    public function disableTerminal(array $input)
    {
        parent::disableTerminal($input);

        $request = $this->getTerminalOnboardingMozartRequestArray($input);

        $traceReq = [
            'method'    => $request['method'],
            'url'       => $request['url'],
            'content'   => $request['content'],
        ];

        $this->traceGatewayTerminalOnboarding($traceReq, 'request', $input, TraceCode::GATEWAY_DISABLE_TERMINAL_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayTerminalOnboarding($response, 'response', $input, TraceCode::GATEWAY_DISABLE_TERMINAL_RESPONSE);

        return $response;
    }

    public function enableTerminal(array $input)
    {
        parent::enableTerminal($input);

        $request = $this->getTerminalOnboardingMozartRequestArray($input);

        $traceReq = [
            'method'    => $request['method'],
            'url'       => $request['url'],
            'content'   => $request['content'],
        ];

        $this->traceGatewayTerminalOnboarding($traceReq, 'request', $input, TraceCode::GATEWAY_ENABLE_TERMINAL_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $this->traceGatewayTerminalOnboarding($response, 'response', $input, TraceCode::GATEWAY_ENABLE_TERMINAL_RESPONSE);

        return $response;
    }

    public function mandateRevoke($input)
    {
        parent::action($input, Action::MANDATE_REVOKE);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::GATEWAY_MANDATE_REVOKE_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::GATEWAY_MANDATE_REVOKE_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        return;
    }

    public function callbackDecryption($input)
    {
        parent::action($input, Action::CALLBACK_DECRYPTION);

        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, TraceCode::UPI_GATEWAY_CALLBACK_DECRYPTION_REQUEST);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, TraceCode::UPI_GATEWAY_CALLBACK_DECRYPTION_RESPONSE);

        $this->checkErrorsAndThrowExceptionFromMozartResponse($response);

        unset($response['data']['_raw'], $response['_raw']);

        return $response['data'];
    }

    protected function isMandateCreateCallback($input, $gateway)
    {
        switch($gateway)
        {
            case Payment\Gateway::UPI_MINDGATE:
                return ($input['mandateDtls'][0]['mandateType'] === 'CREATE');
            case Payment\Gateway::UPI_ICICI:
                return (substr($input['merchantTranId'], 14, 6) === 'create');
        }
    }

    protected function isFirstDebitCallback($input, $gateway)
    {
        switch($gateway)
        {
            case Payment\Gateway::UPI_MINDGATE:
                return ($input['mandateDtls'][0]['mandateType'] === 'EXECUTE');
            case Payment\Gateway::UPI_ICICI:
                return (substr($input['merchantTranId'], 14, 6) === 'execte');
        }
    }

    public function immediateVerifyApplicable($input)
    {
        $immediateVerifyEnabledMethods = [
            Payment\Method::NETBANKING,
            Payment\Method::WALLET,
        ];

        $immediateVerifyDisabledNetbankingWalletGateways = [
        Payment\Gateway::WALLET_PHONEPESWITCH,
        Payment\Gateway::WALLET_PAYPAL,
    ];

        if (((in_array($input['payment'][Payment\Entity::METHOD], $immediateVerifyEnabledMethods, true) === true)
            and
            (in_array($input['payment'][Payment\Entity::GATEWAY], $immediateVerifyDisabledNetbankingWalletGateways, true) === false))
            or
            (in_array($input['payment'][Payment\Entity::GATEWAY], Payment\Gateway::$immediateVerifyGateways, true) === true))
        {
            return true;
        }

        return false;
    }

    protected function isS2SFlow($input)
    {
        if (in_array($input['payment'][Payment\Entity::GATEWAY], Payment\Gateway::$s2sGateways, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function isVerifyMissingGateway($input)
    {
        if (in_array($input['payment'][Payment\Entity::GATEWAY], Payment\Gateway::$verifyMissingGateways, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function isContactMandatoryGateway($input)
    {
        if (in_array($input['payment'][Payment\Entity::GATEWAY], Payment\Gateway::$contactMandatoryGateways, true) === true)
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
            case Payment\Gateway::UPI_AXISOLIVE:
            case Payment\Gateway::UPI_KOTAK:
            case Payment\Gateway::UPI_RZPRBL:
                $data = [
                    'payload'       => $input,
                    'gateway'       => $gateway,
                    'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
                ];
                return $this->upiPreProcess($data);
            case Payment\Gateway::UPI_AIRTEL:
                return $this->preProcessServerCallbackForUpiAirtel($input, $mode);
            case Payment\Gateway::UPI_JUSPAY:
            case Payment\Gateway::CRED:
            case Payment\Gateway::UPI_CITI:
                return $input;
            case Payment\Gateway::UPI_SBI:
                if($this->shouldUseUpiPreProcess(Payment\Gateway::UPI_SBI)){
                    $data = [
                        'payload'       => $input,
                        'gateway'       => Payment\Gateway::UPI_SBI,
                        'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
                    ];

                    return $this->upiPreProcess($data);
                }
                return $this->preProcessServerCallbackForUpiSbi($input);
            case Payment\Gateway::NETBANKING_YESB:
                return $this->preProcessServerCallbackForYesb($input);
            case Payment\Gateway::WALLET_PHONEPE:
                $response = json_decode(base64_decode($input['response'], true), true);
                $response['callback_type'] = 's2s';
                return $response;
            case Payment\Gateway::NETBANKING_KVB:
                return $this->preProcessServerCallbackForKvb($input, $mode);
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for prcessing S2S callback');
        }
    }

    public function postProcessServerCallback($input, $exception = null)
    {
        if ($exception === null)
        {
            return [
                'success' => true,
            ];
        }

        return [
            'success' => false,
        ];
    }

    public function preProcessMandateCallback($input, $gateway)
    {
        switch ($gateway)
        {
            case Payment\Gateway::UPI_MINDGATE:
                return json_decode($this->decryptForUpiMindgateMandate($input), true);
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
            case Payment\Gateway::UPI_AIRTEL:
            case Payment\Gateway::UPI_KOTAK:
            case Payment\Gateway::UPI_RZPRBL:
                $version = $response['data']['version'] ?? '';

                if ($version === 'v2')
                {
                    return $this->upiPaymentIdFromServerCallback($response);
                }

                return $response[UpiAirtelResponseFields::PAYMENT_ID];
            case Payment\Gateway::UPI_CITI:
                return $response[UpiCiti\Fields::PUSH_NOTIFICATION_TO_SSG][UpiCiti\Fields::ORDER_NO];
            case Payment\Gateway::NETBANKING_KVB:
            case Payment\Gateway::NETBANKING_YESB:
                return $response['data']['paymentId'];
            case Payment\Gateway::WALLET_PHONEPE:
                return $response['data']['transactionId'];
            case Payment\Gateway::UPI_JUSPAY:
                return $this->getPaymentIdForUpiJuspay($response);
            case Payment\Gateway::CRED:
                return $response['response']['tracking_id'];
            case Payment\Gateway::UPI_AXISOLIVE:
                return $response['data']['upi']['merchant_reference'];
            default :
                throw new Exception\LogicException(
                    'Invalid gateway passed for getting payment id from S2S callback');
        }
    }

    public function refund(array $input)
    {
        parent::refund($input);

        if ($input['payment']['gateway'] === Payment\Gateway::PAYLATER)
        {
            switch ($this->terminal->getGatewayAcquirer())
            {
                case Payment\Gateway::GETSIMPL:
                    $input['payment']['gateway'] = $input['payment']['wallet'];
                    break;
                case Payment\Processor\PayLater::ICICI:
                    $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;
                    break;
            }
        }

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

        list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
            $input,
            TraceCode::GATEWAY_REFUND_REQUEST,
            TraceCode::GATEWAY_REFUND_RESPONSE,
            false);

        $this->gatewayPayment = $this->createGatewayRefundEntity($attributes, $input, $this->action);

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

    public function merchantOnboard(array $input)
    {
        $this->input = $input;
        $this->action = Action::MERCHANT_ONBOARD;

        $request = $this->getMozartOnboardRequestArray($input);

        $this->trace->info(
            TraceCode::MOZART_SERVICE_REQUEST,
            [
                'url'      => $request['url'],
                'gateway'  => $this->gateway,
            ]);

        $response = $this->sendGatewayRequest($request);

        return $this->parseMerchantOnboardResponse($response, $input);
    }

    public function verify(array $input)
    {
        parent::verify($input);

        if ($input['payment']['gateway'] === Payment\Gateway::PAYLATER)
        {
            switch ($this->terminal->getGatewayAcquirer())
            {
                case Payment\Gateway::GETSIMPL:
                    $input['payment']['gateway'] = $input['payment']['wallet'];
                    break;
                case Payment\Processor\PayLater::ICICI:
                    $input['payment']['gateway'] = Payment\Gateway::PAYLATER_ICICI;
                    break;
            }
        }

        $verify = new Verify($this->gateway, $input);

        return $this->runPaymentVerifyFlow($verify);
    }

    public function upiRecurringVerify(array $input, UpiEntity $entity)
    {
        parent::verify($input);

        $gateway = $input['payment']['gateway'];

        $verify = new Verify($gateway, $input);

        $verify->payment = $entity;

        return $this->runPaymentVerifyFlow($verify);
    }

    public function upiRecurringVerifyGateway(array $input, UpiEntity $entity)
    {
        parent::verify($input);

        $gateway = $input['payment']['gateway'];

        $verify = new Verify($gateway, $input);

        $verify->payment = $entity;

        return $this->runPaymentVerifyFlowGateway($verify);
    }

    public function forceAuthorizeFailed(array $input)
    {
        if ($this->isVerifyMissingGateway($input) === true)
        {
            return true;
        }

        if ($this->isForceAuthMozartGateway($input) === true)
        {
            return true;
        }

        return false;
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

    public function sendVerifyRequest($verify)
    {
        $this->action = Payment\Action::VERIFY;

        $verifyResponseContent = $this->sendPaymentVerifyRequest($verify);

        return $verifyResponseContent;
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

    public function sendPaymentVerifyRequestGateway($verify)
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

        return $verify;
    }

    protected function verifyPayment($verify)
    {
        if ($this->isUpiRecurringPayment($verify->input['payment']) === true)
        {
            return $this->verifyUpiRecurringPayment($verify);
        }

        $input = $verify->input;

        $content = $verify->verifyResponseContent;

        $verify->status = VerifyResult::STATUS_MATCH;

        $verify->gatewaySuccess = $content['success'];

        $this->checkApiSuccess($verify);

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $verify->status = VerifyResult::STATUS_MISMATCH;
        }

        $gateway =  $input['payment']['gateway'];

        // Check Payment Amount with Gateway Amount if the gateway is in the checklist map
        if ((isset($this->gatewaysForAmountCheck[$gateway]) === true) and
            (isset($content['data']['amount']) === true) and
            ($verify->gatewaySuccess === true))
        {
            $gatewayAmount = (int) $content['data']['amount'];
            $paymentEntityAmount = (int) $input['payment']['amount'];

            $verify->amountMismatch = ($gatewayAmount !== $paymentEntityAmount);
        }

        $verify->match = ($verify->status === VerifyResult::STATUS_MATCH);

        $this->updateGatewayPaymentEntityWithAction($verify->payment, $content, true, Action::AUTHORIZE);

        $gatewayName = $this->getGateway($input);

        $this->restrictPaymentVerifyGatewayIfApplicable($gatewayName, $verify);

        return $verify->status;
    }

    /**
     * @param $verify
     * @return string
     *
     * Sample v2 verify response :
     *  {
        "data": {
            "upi": {
                "gateway_data": {
                    "id": "Hv4iga1CmfWU3F0execte1"
                },
                "gateway_payment_id": "125227393136",
                "merchant_reference": "Hv4iga1CmfWU3F0execte1",
                "npci_reference_id": "125227393136",
                "status_code": "0"
                },
            "version": "v2"
            }
        }
     */
    protected function verifyUpiRecurringPayment($verify)
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

        $action = Action::AUTHORIZE;

        if (($this->isUpiRecurringPayment($verify->input['payment']) === true) and
            ($verify->input['upi']['action'] === Base\Action::AUTHENTICATE))
        {
            $action = Action::MANDATE_CREATE;
        }

        if ((isset($content['data']['version']) === true) and
            ($content['data']['version'] === 'v2'))
        {
            $attributes = array_only($content['data']['upi'], (new UpiEntity())->getFillable());
        }
        else
        {
            $attributes = array_only($content['data'], (new UpiEntity())->getFillable());
        }

        $new = array_pull($attributes, UpiEntity::GATEWAY_DATA);

        $current = $verify->payment->getGatewayData();

        // In fact, we should not allow gateway data to be updated from mozart response
        // But, as of now merging this in case it seems useful from mozart's side
        $updated = array_merge($current, $new);

        $attributes[UpiEntity::GATEWAY_DATA] = $updated;

        $this->updateGatewayPaymentEntity($verify->payment, $attributes, false);

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

            if (($gateway === 'wallet_paypal') and ($this->getAction() === 'refund') and ($input['gateway'][$prevStepName]['status'] !== 'capture_successful'))
            {
                $input['gateway'][$prevStepName] = $this->getPreviousData($input, "capture");

            }
        }

        $content['entities'] = $input;

        $this->checkTpvAndModifyOrder($content, $input);

        $this->setPaymentVpaForUpiRecurringIntent($content, $input);

        $prefix = 'payments';

        if ((isset($input['gateway']['cps_route']) === true) and
            ($input['gateway']['cps_route'] === Payment\Entity::UPI_PAYMENT_SERVICE))
        {
            $prefix = 'upiPayments';

            $content = $input;
        }

        $url = $this->getUrlForMozartRequest($input, $prefix, $mode);

        // TODO : Once these wallets migrated to Nbplus service, remove this hack

        $payment = $input['payment'];

        if ($payment instanceof Payment\Entity)
        {
            $payment = $payment->toArrayPublic();
        }

        if ((array_key_exists('method',$payment)) and (($gateway === Payment\Gateway::CCAVENUE) or ($gateway === Payment\Gateway::PAYU)) and ($payment['method'] === Payment\Method::WALLET))
        {
            $url = $this->getUrlForMozartRequest($input, 'walletPayments', $mode);
        }

        $mozartRequest = $this->getAuthenticatedMozartRequestArray($url, $content, $mode);

        $this->addMozartTimeoutIfApplicable($input, $mozartRequest);

        return $mozartRequest;
    }

    protected function addMozartTimeoutIfApplicable($input, &$mozartRequest)
    {
        $gateway = $this->getGateway($input);
        $timeout = null;

        if (($gateway === Payment\Gateway::CRED) and
            ($this->action === Action::VALIDATE) and
            (empty($input['options']['override_timeout']) === false))
        {
            $credTimeoutConfig = 'applications.mozart.cred_eligibility_request_timeout';

            $timeout = $this->app['config']->get($credTimeoutConfig);
        }

        if(($gateway === Payment\Gateway::CCAVENUE) and
            ($this->action) === Action::PAY_INIT and
            ($this->isUpiCollectFlow($input) === true))
        {
            $ccavenueTimeoutConfig = 'applications.mozart.ccavenue_collect_request_timeout';

            $timeout = $this->app['config']->get($ccavenueTimeoutConfig);
        }

        if(($gateway === Payment\Gateway::OPTIMIZER_RAZORPAY) and
            ($this->action) === Action::PAY_INIT and
            ($this->isUpiCollectFlow($input) === true))
        {
            // optimizer_razorpay internally calls ccavenue,
            // so we need to increase timeout as ccavenue upi call is a sync call
            $optRzpTimeoutConfig = 'applications.mozart.optrzp_collect_request_timeout';

            $timeout = $this->app['config']->get($optRzpTimeoutConfig);
        }

        if ((is_null($timeout) === false) and
            (is_numeric($timeout) === true))
        {
            $mozartRequest['options']['timeout'] = $timeout;
        }
    }

    protected function isUpiCollectFlow($input)
    {
        if((isset($input['upi']['flow']) === true)
            and ($input['upi']['flow'] === "collect"))
        {
            return true;
        }

        return false;
    }

    protected function getTerminalOnboardingMozartRequestArray($input)
    {
        if ( (isset($input['terminal']) === true) and
             (($input['terminal'] instanceof TerminalEntity) === true) )
        {
            $input['terminal'] = $input['terminal']->toArrayWithPassword();
        }

        $content['entities'] = $input;

        $url = $this->getUrlForMozartRequest($input, 'onboarding');

        return $this->getAuthenticatedMozartRequestArray($url, $content);
    }

    protected function getGooglePayCardsDecryptionMozartRequestArray($input)
    {
        $url = $this->getUrlForMozartRequest($input, 'payments', Mode::LIVE);

        return $this->getAuthenticatedMozartRequestArray($url, $input['content'], Mode::LIVE);
    }

    protected function getMozartOnboardRequestArray($input)
    {
        $url = $this->getUrlForMozartRequest($input, 'onboarding', Mode::LIVE);

        $content = $this->getMozartOnboardRequestContent($input);

        return $this->getAuthenticatedMozartRequestArray($url, $content, Mode::LIVE, $this->getGateway($input));
    }

    protected function getMozartOnboardRequestContent($input)
    {
        return [
            'merchant' => $input['merchant'],
            'merchant_details' => $input['merchant_details'],
            'currency'       => [ $input['gateway_input']['currency_code'] ] ,
            'methods'     => [ 'CARDS' ],
            'identifiers' => [
                'gateway_merchant_id' => $input['gateway_input']['mid'],
                'gateway_terminal_id'    => $input['gateway_input']['tid'],
                'category'                 => $input['gateway_input']['mcc'],
            ],
        ];
    }

    protected function shouldUseMozartWhitelisted($gateway)
    {
        $gateways = [
            Payment\Gateway::HITACHI,
        ];

        return (in_array($gateway, $gateways, true));
    }

    protected function getWhitelistedConfig($mode, $postfix)
    {
        return 'applications.mozart.' . $mode . '_whitelisted' . $postfix;
    }

    protected function getUrlForMozartRequest($input, $prefix, $mode = null)
    {
        $mode = $this->mode ?? $mode;

        if (($mode === 'live') and ($this->shouldUseMozartWhitelisted($this->getGateway($input)) === true))
        {
            $urlConfig = $this->getWhitelistedConfig($mode, '.url');
        }
        else {
            $urlConfig = 'applications.mozart.' . $mode . '.url';
        }

        $baseUrl = $this->app['config']->get($urlConfig);

        $gateway = $this->getGateway($input);

        $url =  $baseUrl . $prefix . '/' .  $gateway . '/v1/' . $this->action;

        // Use access code from terminal only when it is a UPI ICICI Recurring
        if ($gateway === Payment\Gateway::UPI_ICICI)
        {
            if($prefix === 'upiPayments')
            {
                return $baseUrl . $prefix . '/' . $gateway . '/v1/' . $this->action;
            }

            $version = $this->getMozartVersionForUpiIcici($input);

            if ($version !== '')
            {
                $url = $baseUrl . $prefix . '/' . $gateway . '/' . $version . '/' . $this->action;

                return $url;
            }
        }
        if($gateway === Payment\Gateway::UPI_SBI)
        {
            if($prefix === 'upiPayments')
            {
                return $baseUrl . $prefix . '/' . $gateway . '/v1/' . $this->action;
            }

            $url = $baseUrl . $prefix . '/' . $gateway . '/v2/' . $this->action;

            $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(), self::UPI_SBI_V3_MIGRATION, $mode);

            if (strtolower($variant) === 'v3')
            {
                $url = $baseUrl . $prefix . '/' . $gateway . '/v3/' . $this->action;
            }

            return $url;
        }
        $isBajajFinserv = $this->isBajajFinservGateway($input);

        if($isBajajFinserv === true)
        {
            $variant = $this->app->razorx->getTreatment($input['merchant']['id'], self::BAJAJ_FINSERV_REST_API, $this->mode);
            if(strtolower($variant) === 'v2')
            {
                $url =  $baseUrl . $prefix . '/' .  $gateway . '/v2/' . $this->action;
            }
            else {
                $url =  $baseUrl . $prefix . '/' .  $gateway . '/v1/' . $this->action;
            }
        }

        $isGooglePay = $this->isGooglePayGateway($input);

        if ($isGooglePay === true)
        {
            $url =  $baseUrl . $prefix . '/' . $input['gateway'] . '/v1/' . $this->action;
        }

        if($gateway === Payment\Gateway::PAYSECURE) {
            $url = $baseUrl . 'cardPayments/' . $input['gateway'] . '/v4/' . $this->action;
        }
        if ($gateway === Payment\Gateway::BT_RBL)
        {
            $url = $baseUrl . $prefix . '/' . $input['gateway'] . '/v1/' . $this->action;
        }

        return $url;
    }

    /**
     * @param $input
     * @return string
     */
    protected function getMozartVersionForUpiIcici($input): string
    {
        $version = '';

        if($this->action === Action::CALLBACK_DECRYPTION)
        {
            $version = 'v4';
            return $version;
        }

        if ($this->isUpiRecurringPayment($input['payment']) === true)
        {
            $gatewayAccessCode = trim($input['terminal']['gateway_access_code'] ?? null);

            if ($gatewayAccessCode === 'v2')
            {
                $version = 'v2';
            }
            else if ($gatewayAccessCode === 'v4')
            {
                $version = 'v4';
            }
        }

        return $version;
    }

    protected function getAuthenticatedMozartRequestArray($url, $content, $mode = null, $gateway = null)
    {
        $mode = $this->mode ?? $mode;

        if (($mode === 'live') and ($this->shouldUseMozartWhitelisted($gateway)) === true)
        {
            $passwordConfig = $this->getWhitelistedConfig($mode, '.password');
        }
        else
        {
            $passwordConfig = 'applications.mozart.' . $mode . '.password';
        }

        $authentication = [
            'api',
            $this->app['config']->get($passwordConfig)
        ];

        $mozartRequest = [
            'url'     => $url,
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Task-ID'    => $this->app['request']->getTaskId(),
            ],
            'content'  => json_encode($content),
            'options'  => [
                'auth' => $authentication
            ]
        ];

        if (isset($this->app['rzp.mode']) and $this->app['rzp.mode'] === 'test')
        {
            $testCaseId = $this->app['request']->header('X-RZP-TESTCASE-ID');

            if (empty($testCaseId) === false)
            {
                $mozartRequest['headers']['X-RZP-TESTCASE-ID'] = $testCaseId;
            }
        }
        return $mozartRequest;
    }

    protected function parseMerchantOnboardResponse($response, $input)
    {
        if ((isset($response['success']) === true) and
            ($response['success'] === false))
            {
                throw new Exception\GatewayErrorException(
                    $response['error']['internal_error_code'],
                    $response['error']['gateway_error_code'],
                    $response['error']['gateway_error_description']);
            }
        return [
            'gateway_merchant_id' => $response['data']['identifiers']['gateway_merchant_id'],
            'gateway_terminal_id' => $response['data']['identifiers']['gateway_terminal_id'],
            'gateway_acquirer'   => 'ratn',
            'gateway'            => 'hitachi',
            'currency'           => $input['gateway_input']['currency_code'],
            'category'           => $input['gateway_input']['mcc'],
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
            Payment\Gateway::HDFC_DEBIT_EMI => [
                Action::AUTHENTICATE_INIT   => null,
                Action::AUTHENTICATE_VERIFY => Action::AUTHENTICATE_INIT,
                Action::PAY_INIT            => Action::AUTHENTICATE_VERIFY,
                Action::VERIFY              => Action::PAY_INIT,
                Action::REFUND              => Action::PAY_INIT,
            ],
            Payment\Gateway::BAJAJFINSERV => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => Action::PAY_INIT,
                Action::VERIFY        => Action::PAY_VERIFY,
                Action::REFUND        => Action::PAY_VERIFY,
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
                Action::AUTH_INIT     => null,
                Action::AUTH_VERIFY   => Action::PAY_INIT,
                Action::DEBIT         => Action::PAY_INIT,
                Action::VERIFY        => Action::PAY_INIT,
                Action::REFUND        => Action::CAPTURE,
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
            Payment\Gateway::PAYU => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
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
                Action::INTENT        => null,
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
                Action::REFUND        => Action::PAY_VERIFY,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::UPI_JUSPAY => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
                Action::REFUND        => null,
            ],
            Payment\Gateway::UPI_SBI => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
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
                Action::AUTH_VERIFY       => null,
                Action::PAY_INIT          => null,
                Action::PAY_VERIFY        => null,
                Action::CAPTURE           => Action::PAY_INIT,
                Action::VERIFY            => null,
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
            ],
            Payment\Gateway::PAYLATER_ICICI  =>  [
                Action::CHECKACCOUNT    => null,
                Action::AUTH_INIT       => Action::CHECKACCOUNT,
                Action::AUTH_VERIFY     => Action::CHECKACCOUNT,
                Action::CHECK_BALANCE   => Action::CHECKACCOUNT,
                Action::PAY_INIT        => Action::CHECKACCOUNT,
                Action::VERIFY          => Action::CHECKACCOUNT,
            ],
            Payment\Gateway::WALLET_PHONEPESWITCH  =>  [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => null,
                Action::REFUND          => null,
                Action::VERIFY_REFUND   => null,
            ],
            Payment\Gateway::CRED       =>  [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => null,
                Action::REFUND          => null,
                Action::VERIFY_REFUND   => null,
                Action::VALIDATE        => null,
            ],
            Payment\Gateway::NETBANKING_JSB =>  [
                Action::PAY_INIT    =>  null,
                Action::PAY_VERIFY  =>  Action::PAY_INIT,
                Action::VERIFY      =>  Action::PAY_VERIFY,
            ],
            Payment\Gateway::UPI_ICICI => [
                Action::AUTH_INIT           => null,
                Action::AUTH_VERIFY         => null,
                Action::PAY_INIT            => null,
                Action::PAY_VERIFY          => null,
                Action::MANDATE_REVOKE      => null,
                Action::NOTIFY              => null,
                Action::VERIFY              => null,
                Action::CALLBACK_DECRYPTION => null,
            ],
            Payment\Gateway::CCAVENUE => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
            ],
            Payment\Gateway::PAYSECURE    => [
                Action::AUTHENTICATE_VERIFY => null,
                Action::NOTIFY              => null,
            ],
            Payment\Gateway::BILLDESK_SIHUB => [
                Action::AUTHENTICATE_INIT   => null,
                Action::AUTHENTICATE_VERIFY => null,
                Action::PAY_INIT            => null,
                Action::PAY_VERIFY          => null,
                Action::MANDATE_REVOKE      => null,
                Action::UPDATE_TOKEN        => null,
            ],
            Payment\Gateway::PAYU => [
                Action::CHECK_BIN => null
            ],
            Payment\Gateway::OPTIMIZER_RAZORPAY => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
            ],
        ];

        return $previousActionForStep[$gateway][$this->action];
    }

    protected function getPreviousStepForDB($gateway)
    {
        $previousActionForData = [
            Payment\Gateway::HDFC_DEBIT_EMI => [
                Action::AUTHENTICATE_INIT   => null,
                Action::AUTHENTICATE_VERIFY => Action::AUTHORIZE,
                Action::PAY_INIT            => Action::AUTHORIZE,
                Action::VERIFY              => Action::AUTHORIZE,
                Action::REFUND              => Action::AUTHORIZE,
            ],
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
                Action::AUTH_INIT     => null,
                Action::AUTH_VERIFY   => Action::AUTHORIZE,
                Action::DEBIT         => Action::AUTHORIZE,
                Action::REFUND        => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
                Action::VERIFY        => Action::AUTHORIZE,
            ],
            Payment\Gateway::UPI_AIRTEL => [
                Action::INTENT => null,
                Action::PAY_INIT => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY => null,
                Action::REFUND => Action::AUTHORIZE,
                Action::VERIFY_REFUND => Action::REFUND,
            ],
            Payment\Gateway::UPI_JUSPAY => [
                Action::PAY_INIT   => null,
                Action::PAY_VERIFY => null,
                Action::VERIFY     => null,
                Action::REFUND     => null,
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
                Action::AUTH_VERIFY     => null,
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::CAPTURE         => Action::AUTHORIZE,
                Action::VERIFY          => null,
            ],
            Payment\Gateway::UPI_SBI => [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => null,
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
            ],

            Payment\Gateway::PAYLATER_ICICI  =>  [
                Action::CHECKACCOUNT    => null,
                Action::AUTH_INIT       => Action::CHECKACCOUNT,
                Action::AUTH_VERIFY     => Action::CHECKACCOUNT,
                Action::CHECK_BALANCE   => Action::CHECKACCOUNT,
                Action::PAY_INIT        => Action::CHECKACCOUNT,
                Action::VERIFY          => Action::CHECKACCOUNT,
            ],
            Payment\Gateway::WALLET_PHONEPESWITCH  =>  [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => null,
                Action::REFUND          => null,
                Action::VERIFY_REFUND   => null,
            ],
            Payment\Gateway::PAYU => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
            ],
            Payment\Gateway::CRED       =>  [
                Action::PAY_INIT        => null,
                Action::PAY_VERIFY      => null,
                Action::VERIFY          => null,
                Action::REFUND          => null,
                Action::VERIFY_REFUND   => null,
                Action::VALIDATE        => null,
            ],
            Payment\Gateway::NETBANKING_JSB =>  [
                Action::PAY_INIT    =>  null,
                Action::PAY_VERIFY  =>  Action::AUTHORIZE,
                Action::VERIFY      =>  Action::AUTHORIZE,
            ],
            Payment\Gateway::UPI_ICICI => [
                Action::AUTH_INIT           => null,
                Action::AUTH_VERIFY         => null,
                Action::PAY_INIT            => null,
                Action::PAY_VERIFY          => null,
                Action::MANDATE_REVOKE      => null,
                Action::NOTIFY              => null,
                Action::VERIFY              => null,
                Action::CALLBACK_DECRYPTION => null,
            ],
            Payment\Gateway::CCAVENUE => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
            ],
            Payment\Gateway::PAYSECURE    => [
                Action::AUTHENTICATE_VERIFY => null,
                Action::NOTIFY              => null,
            ],
            Payment\Gateway::BILLDESK_SIHUB => [
                Action::AUTHENTICATE_INIT   => null,
                Action::AUTHENTICATE_VERIFY => null,
                Action::PAY_INIT            => null,
                Action::PAY_VERIFY          => null,
                Action::MANDATE_REVOKE      => null,
                Action::UPDATE_TOKEN        => null,
            ],
            Payment\Gateway::OPTIMIZER_RAZORPAY => [
                Action::PAY_INIT      => null,
                Action::PAY_VERIFY    => null,
                Action::VERIFY        => null,
            ],
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

        unset($data['data']['account_number']);

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
        if ($this->isUpiRecurringPayment($verify->input['payment']) === true)
        {
            return $verify->payment;
        }

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

    public function preProcessServerCallbackForUpiSbi($input): array
    {
        $this->action = Action::PAY_VERIFY;

        $content['gateway']['redirect'] = $input;

        $content['terminal'] = '';

        $content['payment']['gateway'] = Payment\Gateway::UPI_SBI;

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

    protected function assertPaymentAmountWithBalance($input, $response)
    {
    //This function checks if the payment amount is greater than the available balance returned by mozart action check_balance

            if (isset($response['data']['amount']) === true)
            {
                $paymentAmount = $input['payment']['amount'];
                $balanceAmount = $response['data']['amount'];

                if ($paymentAmount > $balanceAmount)
                {
                    throw new Exception\GatewayErrorException(
                        ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
                        null,
                        'Payment Amount should be less than available balance',
                        ['paymentAmount' => $paymentAmount, 'balanceAmount' => $balanceAmount]
                    );
                }
            }
            else
            {
                throw new Exception\GatewayErrorException(
                    'Amount should have been passed for assertion');
            }
    }

    protected function runCallbackValidationsIfApplicable($input, $response)
    {
        if ($this->shouldRunCallbackValidations($input['payment']['gateway']) === true)
        {
            // For upi recurring payments, we get mandate create callback first. We get the mandate amount in the
            // mandate create callback and not the payment amount. So adding condition for that here.
            if (($this->isUpiRecurringPayment($input['payment']) === true) and
                ($this->isUpiRecurringApplicableGateway($input['payment']['gateway']) === true) and
                ($this->isMandateCreateCallback($input['gateway']['redirect'], $input['payment']['gateway']) === true))
            {
                $this->runMandateCreateCallbackValidations($input, $response);

                return;
            }

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

    protected function runMandateCreateCallbackValidations($input, $response)
    {
        //TODO:: We need to remove the return statement and start validating this once icici fixes this.
        return;

        if (isset($response['data']['mandate_amount']) === true)
        {
            $dbAmount = $input['upi_mandate']['max_amount'];
            $gatewayAmount = $response['data']['mandate_amount'];

            $this->assertAmount($dbAmount, $gatewayAmount);
        }
        else
        {
            throw new Exception\LogicException(
                'Amount should have been passed for Mandate create callback validation');
        }
    }

    protected function shouldRunCallbackValidations($gateway)
    {
        $validationGateways = [
            Payment\Gateway::UPI_AIRTEL,
            Payment\Gateway::UPI_CITI,
            Payment\Gateway::UPI_JUSPAY,
            Payment\Gateway::UPI_SBI,
            Payment\Gateway::UPI_MINDGATE,
            Payment\Gateway::UPI_ICICI,
            Payment\Gateway::WALLET_PHONEPE,
            Payment\Gateway::WALLET_PHONEPESWITCH,
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
            Payment\Gateway::CRED,
            Payment\Gateway::NETBANKING_JSB,
            Payment\Gateway::CCAVENUE,
            Payment\Gateway::PAYU
        ];

        return in_array($gateway, $validationGateways, true);
    }

    protected function isUpiRecurringApplicableGateway($gateway)
    {
        $upiRecurringApplicableGateways = [
            Payment\Gateway::UPI_ICICI,
            Payment\Gateway::UPI_MINDGATE,
        ];

        return in_array($gateway, $upiRecurringApplicableGateways, true);
    }

    protected function fullyEncryptedFlow($gateway)
    {
        $fullyEncryptedInputGateways = [
          Payment\Gateway::NETBANKING_YESB,
          Payment\Gateway::UPI_SBI,
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
            Payment\Gateway::UPI_SBI,
            Payment\Gateway::NETBANKING_KVB,
            Payment\Gateway::CRED,
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

    protected function disableVerifyCronForGateway($gateway, $verify)
    {
        $verifyDisabledGateways = [
            Payment\Gateway::PAYLATER_ICICI,
        ];

        if (in_array($gateway, $verifyDisabledGateways, true) === true)
        {
            if ($this->app['basicauth']->isCron() === true)
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
        if ($this->isUpiRecurringPayment($input['payment']) === true)
        {
            if ($this->isMandateCreateCallback($input['gateway']['redirect'], $input['payment']['gateway']) === true)
            {
                $response = $this->getMandateCreateResponseData($input, $mozartResponse);
            }
            else if ($this->isFirstDebitCallback($input['gateway']['redirect'], $input['payment']['gateway']) === true)
            {
                $response = [
                    'acquirer' => [
                        Payment\Entity::VPA         => $mozartResponse['data']['vpa'] ?? $input['payment']['vpa'] ?? '',
                        Payment\Entity::REFERENCE16 => $mozartResponse['data']['rrn'] ?? '',
                    ],
                    'upi' => array_only($mozartResponse['data'], (new UpiEntity())->getFillable()),
                ];
            }
            else
            {
                throw new Exception\LogicException('Only mandate create is handled on UPI');
            }
        }
        elseif ($input['payment']['method'] === Payment\Method::UPI)
        {
            $response = [
                'acquirer' => [
                    Payment\Entity::VPA         => $mozartResponse['data']['vpa'] ?? $input['payment']['vpa'] ?? null,
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

    protected function getMandateCreateResponseData($input, $mozartResponse)
    {
        $response = [
            'acquirer' => [
                Payment\Entity::VPA         => $mozartResponse['data']['vpa'] ?? $input['payment']['vpa'] ?? null,
                Payment\Entity::REFERENCE16 => $mozartResponse['data']['rrn'] ?? null,
            ],
            'mandate' => [
                'order_id'      => $input['payment']['order_id'],
                'status'        => 'confirmed',
                'umn'           => $mozartResponse['data']['umn'] ?? '',
                'npci_txn_id'   => $mozartResponse['data']['npci_txn_id'] ?? '',
                'rrn'           => $mozartResponse['data']['rrn'] ?? '',
            ],
            'upi' => array_only($mozartResponse['data'], (new UpiEntity())->getFillable()),
        ];

        return $response;
    }


    protected function checkTpvAndModifyOrder(& $content, $input)
    {

        // Initialize entities.merchant for UPI Recurring
        // based on merchant.feature.tpv flag mozart will execute preConfig rule and
        // select config for UPI Recurring TPV
        if ((isset($input['payment']['method']) === true) and
            ($this->isUpiRecurringPayment($input['payment']) === true))
        {
            $category = $input['merchant']->getCategory();

            // if dedicated terminal pick MCC/Category from Terminal table
            if((isset($input['terminal'])) and
                (isset($input['terminal']['category'])) and
                (empty($input['terminal']['category']) === false) and
                ($this->isUpiIciciRecurringSharedTerminal($input['terminal']['merchant_id']) === false))
            {
                $category = $input['terminal']['category'];
            }

            $content['entities']['merchant'] = [
                'category'      =>  $category,
                'billing_label' =>  $input['merchant']->getBillingLabel(),
                'feature' => [
                    'tpv' => $input['merchant']->isTPVRequired()
                ]
            ];

            return ;
        }

        if (($this->action === Action::PAY_INIT) and
            (in_array($this->getGateway($input), [Payment\Gateway::GOOGLE_PAY, Payment\Gateway::BILLDESK_SIHUB], true) === false))
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
            Payment\Gateway::PAYLATER_ICICI,
            Payment\Gateway::NETBANKING_JSB,
        ];

        return in_array($gateway, $fileBasedGateways, true);
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
    protected function isBajajFinservGateway($input)
    {
        if ($this->getGateway($input) === Payment\Gateway::BAJAJFINSERV)
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

    public function decryptForUpiMindgateMandate($input)
    {
        if ((isset($input['keyId']) === true) and ($input['keyId'] == 1))
        {
            $payload =  $this->getCipherInstance(Crypto::MODE_CBC)
                             ->setIV($input['ivToken'])
                             ->enablePadding()
                             ->decrypt($input['payload']);
            return $payload;
        }

        return $this->getCipherInstance()
                    ->decrypt($input['payload']);
    }

    protected function getEncryptionKey()
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        return hex2bin($key);
    }

    protected function getCipherInstance($mode = null)
    {
        $key = config('gateway.upi_mindgate.gateway_encryption_key');

        if ($mode === null)
        {
            return new Crypto($key);
        }

        return new Crypto($key, $mode);
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

        $merchantId = $input['merchant_id'];

        $cacheKey = strtolower($input['provider']) . '_' . $contact . '_' . $merchantId;

        $key = sprintf(self::CACHE_KEY, $cacheKey);

        $this->createCacheData($key, $token);
    }

    protected function createCacheData($key, $value, $ttl = self::CARD_CACHE_TTL)
    {
        // Multiplying by 60 since cache put() expect ttl in seconds
        $this->app['cache']->put($key, $value, $ttl*60);
    }

    protected function fetchCacheData($input)
    {
        $contact = $input['payment']['contact'];

        $merchantId = $input['merchant']['id'];

        $cacheKey = strtolower($input['payment']['wallet']) . '_' . $contact . '_' . $merchantId;

        $key = sprintf(self::CACHE_KEY, $cacheKey);

        return $this->app['cache']->get($key);
    }

    protected function extractPaymentsProperties($gatewayPayment)
    {
        $response = $this->extractPaymentsPropertiesAuthorizedFailedTrait($gatewayPayment);

        $gateway = $gatewayPayment->getGateway();

        if ($this->isUpiGateway($gateway) === true)
        {
            $data = $gatewayPayment->getDataAttribute();

            if (isset($data['rrn']) === true)
            {
                $response['acquirer'][Payment\Entity::REFERENCE16] = $data['rrn'];
            }
        }

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

    protected function isUpiGateway($gateway)
    {
        return in_array($gateway, Payment\Gateway::$methodMap[Payment\Method::UPI], true);
    }

    protected function sendMozartRequestAndGetResponse(
        $input,
        $requestTraceCode,
        $responseTraceCode,
        $handleException = true)
    {
        $request = $this->getMozartRequestArray($input);

        $traceReq = [
            'method' => $request['method'],
            'url'    => $request['url'],
        ];

        $this->traceGatewayPaymentRequest($traceReq, $input, $requestTraceCode);

        $response = $this->sendGatewayRequest($request);

        $traceRes = $this->getRedactedData($response);

        $this->traceGatewayPaymentResponse($traceRes, $input, $responseTraceCode);

        if ($handleException === true)
        {
            // This function is also being used for credpay where $input['gateway'] will not be present
            if (isset($input['gateway']) && $input['gateway'] == MandateHubs::BILLDESK_SIHUB)
            {
                $response['meta_data']['payment_id'] = "pay_".$input['payment']['id'] ?? null;
                if (isset($input['payment']['order_id'])) {
                    $response['meta_data']['order_id']   = "order_".$input['payment']['order_id'] ?? null;
                }
            }

            if (isset($input['gateway']) && $input['gateway'] == Payment\Gateway::PAYSECURE)
            {
                $response['success'] = $response['formatted']['success'];
                $response['error']['internal_error_code'] = $response['formatted']['code'] ?? null;
                $response['error']['gateway_error_code'] = $response['formatted']['gateway_error_code'] ?? null;
                $response['error']['gateway_error_description'] = $response['formatted']['gateway_error_description'] ?? null;
            }

            $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
        }

        $attributes = $this->getMappedAttributes($response);

        return [$response, $attributes];
    }

    protected function getPaymentIdForUpiJuspay($response)
    {
        return $response['body'][UpiJuspay\Fields::MERCHANT_REQUEST_ID];
    }

    protected function isDebitGateway($input)
    {
        if ($input['payment']['method'] === Payment\Method::WALLET)
        {
            return $this->isDebitWallet($input['payment']['wallet']);
        }

        return false;
    }

    private function isUpiRecurringPayment($payment): bool
    {
        return (($payment['method'] === Payment\Method::UPI) and
                ($payment['recurring'] === true));
    }

    private function isUpiIciciRecurringSharedTerminal($merchantId): bool
    {
        $razorpaySharedMerchantId = ['100000Razorpay'];
        return (in_array($merchantId, $razorpaySharedMerchantId, true));
    }

    public static function isDebitWallet($wallet)
    {
        $wallets = [
            Payment\Processor\Wallet::PAYPAL,
        ];

        return (in_array($wallet, $wallets, true));
    }

    public function getParsedDataFromUnexpectedCallback($callbackData)
    {
        $version = $callbackData['data']['version'] ?? '';
        if ($version === 'v2')
        {
            return $this->upiGetParsedDataFromUnexpectedCallback($callbackData);
        }

        $payment = [
            'method'   => 'upi',
            'amount'   => (int) ($callbackData['amount'] * 100),
            'currency' => 'INR',
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
            'vpa'      => $callbackData['payerVPA'],
        ];

        $terminal = $this->getTerminalDetailsFromCallback($callbackData);

        return [
            'payment'  => $payment,
            'terminal' => $terminal
        ];
    }

    public function getTerminalDetailsFromCallback($callbackData)
    {
        return [
            'gateway'              => 'upi_airtel',
            'gateway_merchant_id2' => $callbackData['payeeVPA'],
        ];
    }

    public function validatePush($input)
    {
        if ((isset($input['meta']['version']) === true) and
            ($input['meta']['version'] === 'api_v2'))
        {
            $this->upiIsDuplicateUnexpectedPaymentV2($input);

            $this->upiIsValidUnexpectedPaymentV2($input);

            return;
        }

        $version = $input['data']['version'] ?? '';
        if ($version === 'v2')
        {
            return $this->upiValidatePush($input);
        }
        parent::action($input, Base\Action::VALIDATE_PUSH);

        $this->isDuplicateUnexpectedPayment($input);

        $this->isValidUnexpectedPayment($input);
    }

    protected function isDuplicateUnexpectedPayment($callbackData)
    {
        $merchantReference = $callbackData['hdnOrderID'];

        $mozart = new Mozart\Gateway();

        $gatewayPayment = $mozart->fetchByMerchantReference($merchantReference);

        if ($gatewayPayment !== null)
        {
            throw new Exception\LogicException(
                'Duplicate Gateway payment found',
                null,
                [
                    'callbackData' => $callbackData
                ]
            );
        }
    }

    protected function isValidUnexpectedPayment($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment' => [
                'id'      => $callbackData['hdnOrderID'],
                'gateway' => 'upi_airtel',
                'amount'  => (int) ($callbackData['amount']),
                'vpa'     => $callbackData['payerVPA'],
            ],
            'terminal' => $this->terminal,
        ];

        $this->action = Action::VERIFY;

        $verify = new Verify($this->gateway, $input);

        $this->sendPaymentVerifyRequest($verify);

        $paymentAmount = $verify->input['payment']['amount'];

        $content = $verify->verifyResponseContent;

        $actualAmount = $content['data']['amount'];

        $this->assertAmount($paymentAmount, $actualAmount);

        $status = $content['data']['txnStatus'];

        $this->checkResponseStatus($status);
    }

    public function authorizePush($input)
    {
        list($paymentId , $callbackData) = $input;

        if ((isset($callbackData['meta']['version']) === true) and
            ($callbackData['meta']['version'] === 'api_v2'))
        {
            return $this->upiAuthorizePushV2($input);
        }

        $version = $callbackData['data']['version'] ?? '';

        if ($version === 'v2')
        {
            return $this->upiAuthorizePush($input);
        }

        $gatewayInput = [
            'payment' => [
                'id'      => $paymentId,
                'vpa'     => $callbackData['payerVPA'],
                'amount'  => $callbackData['amount'],
                'gateway' => 'upi_airtel',
            ],
        ];

        parent::action($gatewayInput, Action::AUTHORIZE);

        $attributes = [
            UpiEntity::TYPE                    => Type::PAY,
            UpiEntity::MERCHANT_REFERENCE      => $callbackData['hdnOrderID'],
            UpiEntity::RECEIVED                => 1,
            UpiEntity::VPA                     => $callbackData['payerVPA'],
        ];

        $attributes = array_merge($callbackData, $attributes);

        $mozart = new Mozart\Gateway();

        $gatewayPayment = $mozart->createOrUpdateUpiEntityForMozartGateways($gatewayInput, $attributes, Action::AUTHORIZE);

        $result = $callbackData['txnStatus'];

        $this->checkResponseStatus($result);

        return [
            'acquirer' => [
                Payment\Entity::VPA           => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16   => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    protected function checkResponseStatus($status)
    {
        if ($status !== 'SUCCESS')
        {
            $ex = new Exception\GatewayErrorException();
        }
    }

    protected function isForceAuthMozartGateway($input)
    {
        $forceAuthMozartGateways = [
            Payment\Gateway::NETBANKING_YESB,
            Payment\Gateway::NETBANKING_JSB,
            Payment\Gateway::NETBANKING_SIB,
        ];

        return in_array($input['payment'][Payment\Entity::GATEWAY], $forceAuthMozartGateways, true);
    }

    public function validateApp(array $input)
    {
        parent::action($input, Action::VALIDATE);

        if (is_null($this->terminal) === false)
        {
            switch ($this->terminal->getGateway())
            {
                case Payment\Gateway::CRED:
                    $input['terminal'] = $this->terminal->toArrayWithPassword();

                    // to prevent code breaking while sending mozart request
                    $input['payment']['id'] = 'NA';
                    break;

                default:
                    break;
            }
        }
        else
        {
            throw new Exception\LogicException(
                'Terminal should not be null.'
            );
        }

        list($response, $attributes) = $this->sendMozartRequestAndGetResponse(
            $input,
            TraceCode::GATEWAY_VALIDATE_REQUEST,
            TraceCode::GATEWAY_VALIDATE_RESPONSE
        );

        return $response;
    }

    /**
     * Pre Process server callback for UPI Airtel
     * @param string $input
     * @return array
     *
     * Splits the traffic between common gateway trait and existing API execution for pre-processing.
     */
    public function preProcessServerCallbackForUpiAirtel(string $input,$mode = null)
    {
        $inputArray = json_decode($input, true);

        if ($this->shouldUseUpiPreProcess(Payment\Gateway::UPI_AIRTEL) === true)
        {
            $terminalData = [
                'gateway' => Payment\Gateway::UPI_AIRTEL,
            ];

            if ((empty($inputArray['gateway_merchant_id']) === true) and
                (empty($inputArray['payeeVPA']) === true))
            {
                $exception = new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
                    null,
                    $inputArray,
                    'payload does not contain required keys - gateway_merchant_id and payeeVPA.');

                $this->trace->traceException($exception);

                throw $exception;
            }

            if (isset($inputArray['payeeVPA']) === true)
            {
                $terminalData['gateway_merchant_id2'] = $inputArray['payeeVPA'];
            }

            if (isset($inputArray['gateway_merchant_id']) === true)
            {
                $terminalData['gateway_merchant_id'] = $inputArray['gateway_merchant_id'];
            }

            $mode = ($mode === null) ? Mode::LIVE : $mode ;

            $terminal = $this->app['repo']->terminal->findByGatewayAndTerminalData(Payment\Gateway::UPI_AIRTEL,
                $terminalData, false, $mode);

            if (empty($terminal) === true)
            {
                throw new Exception\RuntimeException(
                    'No terminal found',
                    [
                        'input'     => $input,
                        'action'    => Action::PRE_PROCESS,
                        'gateway'   => Payment\Gateway::UPI_AIRTEL,
                    ],
                    null,
                    ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND);
            }

            $data = [
                'payload'       => $input,
                'gateway'       => Payment\Gateway::UPI_AIRTEL,
                'terminal'      => $terminal,
                'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
            ];

            return $this->upiPreProcess($data);
        }

        return json_decode($input, true);
    }

    /**
     * Sets the VPA in payment entity from the upi entity if
     *  - The payment is UPI Recurring Intent
     *  - The VPA is not set in payment entity already
     *
     * @param $content
     * @param $input
     */
    protected function setPaymentVpaForUpiRecurringIntent(& $content, $input): void
    {
        if (($this->isUpiIntent($input) === true) and
            ($this->isUpiRecurringPayment($input['payment']) === true) and
            (isset($content['entities']['payment']['vpa']) === false))
        {
            $content['entities']['payment']['vpa'] = $input['upi']['vpa'] ?? null;
        }
    }

    private function getTraceContent($content)
    {
        try
        {
            if (is_string($content) === true) {
                $content = json_decode($content, true);
            }

            if (is_array($content) === false) {
                return $content;
            }

            if ((is_array($content['entities']) === true) and (is_array($content['entities']['terminal']) === true)) {

                $terminalData = $content['entities']['terminal'];

                $keys = ['mc_mpan', 'visa_mpan', 'rupay_mpan'];

                foreach ($keys as $key) {
                    unset($terminalData[$key]);
                }

                $content['entities']['terminal'] = $terminalData;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::GATEWAY_GET_TRACE_CONTENT_ERROR,
                [
                    'message'             => 'exception',
                    'error'               => $e->getMessage(),
                ]);
        }

        return $content;
    }

    public function createVirtualAccount($input, $handleException = true) {

        parent::action($input, Action::CREATE_VIRTUAL_ACCOUNT);

        $request = $this->getVirtualAccountMozartRequestArray($input);

        $this->traceVirtualAccountCreateRequest($request);

        $response = $this->sendGatewayRequest($request);

        $this->traceVirtualAccountResponse($response);

        if ($handleException === true)
        {
            $this->checkErrorsAndThrowExceptionFromMozartResponse($response);
        }

        return $this->getVirtualAccountResponseArray($response);
    }

    protected function getVirtualAccountMozartRequestArray($input) {

        $url = $this->getUrlForMozartRequest($input, 'smartCollect');

        return $this->getAuthenticatedMozartRequestArray($url, $input);
    }

    protected function getVirtualAccountResponseArray($response)
    {
        $res = $this->jsonToArray($response['data']['_raw']);
        $res['isSuccess']  = $response['success'];

        return $res;
    }

    // Gets BIN information from gateway. Requires, payment, terminal and card entity.
    // Takes gateway from payment.gateway, IIN from card.iin, and terminal secrets
    public function checkBin($input): array
    {
        parent::action($input, Action::CHECK_BIN);

        list($response) = $this->sendMozartRequestAndGetResponse($input, TraceCode::GATEWAY_CHECK_BIN_REQUEST,
            TraceCode::GATEWAY_CHECK_BIN_RESPONSE, true);

        return $response;
    }
}
