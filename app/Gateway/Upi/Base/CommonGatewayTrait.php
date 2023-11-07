<?php

namespace RZP\Gateway\Upi\Base;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use Carbon\Exceptions\InvalidFormatException;

use RZP\Exception;
use RZP\Gateway\Upi;
use RZP\Models\QrCode;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Constants\Environment;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\UpiMetadata\Flow;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Models\BharatQr\GatewayResponseParams as QrGatewayResponseParams;
/**
 * CommonGatewayTrait
 * Trait Common
 * A common trait to make gateway use upi flow in the gateway
 * every function is implemented with namespaced version of the action
 * to avoid trait conflicts.
 * @package RZP\Gateway\Upi\Base
 * @property $action
 * @property $input
 * @property $qrPaymentMerchantRefSuffix
 */
trait CommonGatewayTrait
{
    protected $qrPaymentMerchantRefSuffix = QrCode\Constants::QR_CODE_V2_TR_SUFFIX;

    /**
     * This static array maintains a list of all gateways migrated to the common QR payments flow.
     * @var array A list of supported gateway on common QR payments flow.
     */
    public static $qrCodePaymentGateways = [
        Payment\Gateway::UPI_KOTAK,
    ];

    /************** Payment Actions ************

     * @param array $input
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function upiAuthorize(array $input)
    {
        $attributes = $this->upiPrepareGatewayAttributes($input, Action::AUTHORIZE);

        $this->upiAttachPaymentRemark($input);

        $gatewayEntity = $this->upiCreateGatewayEntity($input, $attributes);

        $result = $this->upiSendGatewayRequest(
                        $input,
                        TraceCode::GATEWAY_AUTHORIZE_REQUEST,
                        'pay_init');

        $response = new Response($result['data'] ?? []);

        $this->upiUpdateGatewayEntity($gatewayEntity, $response->getFilteredUpi());

        $this->upiTraceGatewayResponse($response, $result, TraceCode::GATEWAY_AUTHORIZE_RESPONSE);

        $this->upiCheckErrorsAndThrowExceptionFromResponse($result);

        return $this->upiPrepareAuthorizeResponse($response, $input, $result);
    }

    public function upiCallback(array $input)
    {
        /**
         * @var $gatewayEntity Entity
         */
        $gatewayEntity = $this->upiGetRepository()
                              ->findByPaymentIdAndActionOrFail($input['payment']['id'], Action::AUTHORIZE);

        $result = $input['gateway'];

        $response = new Response($result['data'] ?? []);

        $this->upiTraceGatewayResponse($response, $result, TraceCode::GATEWAY_PAYMENT_RESPONSE);

        $gatewayEntity->setReceived(1);

        $this->upiUpdateGatewayEntity($gatewayEntity, $response->getFilteredUpi());

        $this->upiCheckErrorsAndThrowExceptionFromResponse($result);

        $this->upiRunCallbackValidations($response, $input);

        return $this->upiPrepareCallbackResponse($response, $input);
    }

    public function upiSendPaymentVerifyRequest(Verify $verify)
    {
        /**
         * @var $gatewayPayment Entity
         */
        $gatewayPayment       = $verify->payment;

        $input                = $verify->input;

        // Merging UPI
        $input[Entity::UPI]   = $this->upiMergeData($gatewayPayment, $input);

        $result               = $this->upiSendGatewayRequest(
                                    $input,
                                    TraceCode::GATEWAY_PAYMENT_VERIFY_REQUEST,
                                    'verify'
                                );

        $response             = new Response($result['data'] ?? []);

        $this->upiTraceGatewayResponse($response, $result, TraceCode::GATEWAY_PAYMENT_VERIFY_RESPONSE);

        $verify->setVerifyResponseBody($result);

        // Attaching the upi entity, for authorize failed flow.
        $verify->setVerifyResponseContent($response->getFilteredUpi());

        return $result;
    }

    public function upiVerifyPayment(Verify $verify)
    {
        $input = $verify->input;

        /**
         * @var $gatewayPayment Entity
         */
        $gatewayPayment = $verify->payment;

        $content = $verify->verifyResponseBody;

        $response = new Response($content['data'] ?? []);

        $this->checkApiSuccess($verify);

        $verify->gatewaySuccess = $content['success'];

        $status = VerifyResult::STATUS_MATCH;

        if ($verify->gatewaySuccess !== $verify->apiSuccess)
        {
            $status = VerifyResult::STATUS_MISMATCH;
        }

        if ($verify->gatewaySuccess === true)
        {
            $payment = $response->getPayment();

            $verify->setAmountMismatch(
                $payment[Payment\Entity::AMOUNT_AUTHORIZED] !== $input['payment'][Payment\Entity::AMOUNT]
            );

            $verify->setCurrencyAndAmountAuthorized(
                $payment[Payment\Entity::CURRENCY],
                $payment[Payment\Entity::AMOUNT_AUTHORIZED]
            );
        }

        $verify->match = ($status === VerifyResult::STATUS_MATCH);

        $this->upiUpdateGatewayEntity($gatewayPayment, $response->getFilteredUpi());
    }

    /**
     * builds and returns pre-process action input
     *
     * @param array $input
     * @return array
     */
    protected function getInputForPreProcess(array $input): array
    {
        $gateway = $this->gateway;

        if ($gateway === 'mozart')
        {
            $gateway = $input['gateway'];
        }

        $terminal = '';

        if (isset($input['terminal']) === true)
        {
            $terminal = $input['terminal'];

            unset($input['terminal']);
        }

        $gatewayInput = [
            'gateway'  => $input,
            'terminal' => $terminal,
            'payment'  => [
                'gateway' => $gateway,
                'id'      => '',
            ]
        ];

        return $gatewayInput;
    }

    private function isPreProcessRampedUpFully(string $gateway): bool
    {
        if($this->env === 'testing' || (app()->isEnvironmentQA() === true))
        {
            return false;
        }

        $gateways = [
            Payment\Gateway::UPI_SBI,
            Payment\Gateway::UPI_KOTAK,
            Payment\Gateway::UPI_AXIS,
            Payment\Gateway::UPI_AIRTEL,
            Payment\Gateway::UPI_MINDGATE,
            Payment\Gateway::UPI_RZPRBL,
            Payment\Gateway::UPI_AXISOLIVE,
            Payment\Gateway::UPI_ICICI,
            ];

        return (in_array($gateway, $gateways, true));
    }

    public function shouldUseUpiPreProcess(string $gateway)
    {
        if($this->isPreProcessRampedUpFully($gateway) === true)
        {
            return true;
        }

        if ($this->isRearchBVTRequestForUPIPreProcess($this->app['request']->header('X-RZP-TESTCASE-ID')) === true)
        {
            return true;
        }

        $feature = 'api' . '_' . $gateway . '_' . \RZP\Gateway\Mozart\Action::PRE_PROCESS . '_' . 'v1';

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $requestOptions = [
            'connect_timeout' => 1,
            'timeout'         => 1,
        ];

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            $feature, $mode, 3, $requestOptions);

        $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_PRE_PROCESS_RAZORX_VARIANT, [
            'gateway' => $gateway,
            'variant' => $variant,
            'mode'    => $mode,
            'feature' => $feature,
        ]);

        return $variant === $gateway;
    }

    /**
     * Pre Process function will be callback function , The purpose it serves that it makes the callback
     * to comply with the contracts . Give a simple interface to work with.
     * @param array $input
     * @return array
     */
    public function upiPreProcess(array $input)
    {
        $gatewayInput = $this->getInputForPreProcess($input);

        $action = 'pre_process';

        if (app('api.route')->getCurrentRouteName() === Gateway::AXISOLIVE_PAYER_CALLBACK_ROUTE)
        {
            $action = 'payer_' . $action;
        }

        $mozart = $this->getUpiMozartGatewayWithModeFromEnvironment();

        $result = $mozart->sendUpiMozartRequest(
            $gatewayInput,
            TraceCode::GATEWAY_PRE_PROCESS_CALLBACK,
            $action
        );

        $response = new Response($result['data'] ?? []);

        $this->upiTraceGatewayResponse($response, $result, TraceCode::GATEWAY_PRE_PROCESS_CALLBACK);

        return $result;
    }

    public function upiPaymentIdFromServerCallback($input)
    {
        return $input['data']['upi']['merchant_reference'];
    }

    public function upiGetParsedDataFromUnexpectedCallback($input)
    {
        $data = $input['data'];

        $payment = [
            'method'   => 'upi',
            'amount'   => $data['payment']['amount_authorized'],
            'currency' => $data['payment']['currency'],
            'contact'  => '+919999999999',
            'email'    => 'void@razorpay.com',
            'upi'     => [
                'flow'  => 'intent'
            ],
        ];

        if (isset ($data['payment']['payer_account_type'])=== true) {
            $payment['payer_account_type'] = $data['payment']['payer_account_type'];
        }

        return [
            'payment'   => $payment,
            'terminal'  => $data['terminal'],
        ];
    }

    public function upiValidatePush($input)
    {
        $this->upiIsDuplicateUnexpectedPayment($input);
    }

    protected function upiIsDuplicateUnexpectedPayment($input)
    {
        $data = $input['data'];

        $gatewayPayment = $this->upiGetRepository()->fetchByMerchantReference($data['upi']['merchant_reference']);

        if ($gatewayPayment !== null)
        {
            throw new LogicException(
                'Duplicate Gateway payment found',
                null,
                [
                    'callbackData' => $input,
                ]
            );
        }
    }

    protected function upiAuthorizePush($data)
    {
        [$paymentId, $content] = $data;

        $response = new Response($content['data'] ?? []);

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Upi\Base\Type::PAY,
            Entity::RECEIVED            => 1,
        ];

        $attributes = array_merge($attributes, $response->getFilteredUpi());

        $payment  = $response->getPayment();

        $upi      = $response->getUpi();

        $gateway = $this->gateway;

        // create gateway entity for upi_airtel
        if ($gateway === 'mozart')
        {
            $gateway = 'upi_airtel';
        }

        // Create input structure for upi entity.
        $input = [
            'payment'    => [
                'id'       => $paymentId,
                'gateway'  => $gateway,
                'vpa'      => $upi['vpa'],
                'amount'   => $payment['amount_authorized'],
            ],
        ];

        // Call to set the input in gateway
        parent::action($input, Action::AUTHORIZE);

        $gatewayPayment = $this->upiCreateGatewayEntity($input, $attributes);

        $this->upiCheckErrorsAndThrowExceptionFromResponse($content);

        return [
            'acquirer' => [
                Payment\Entity::VPA           => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16   => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    protected function upiRefund(array $input)
    {
        $mozart = $this->getUpiMozartGatewayWithModeSet();

        $mozart->refund($input);
    }

    /****************** Helper **************************
     * @param array $input
     * @param string $action
     * @return array
     */
    protected function upiPrepareGatewayAttributes(array $input, string $action): array
    {
        $attributes = [];

        switch ($this->action)
        {
            case Action::AUTHORIZE:
                $flow = $input['upi']['flow'];

                if (Flow::isCollect($flow) === true)
                {
                    $attributes[Entity::TYPE] = Type::COLLECT;
                }
                else if (Flow::isIntent($flow) === true)
                {
                    $attributes[Entity::TYPE] = Type::PAY;
                }

                return $attributes;

            default:
                return $attributes;
        }
    }


    /**
     * Prepares the authorize response for the payment controller
     * @param Response $response
     * @param array $input
     * @param array $result
     * @return array
     */
    protected function upiPrepareAuthorizeResponse(Response $response, array $input, array $result = [])
    {
        $flow = $input['upi']['flow'];

        $data = [];

        switch ($flow)
        {
            case Flow::COLLECT:
                if ($response->isV2())
                {
                    $data = ['vpa' => $result['next']['vpa']];
                }
                else
                {
                    $data = [ 'vpa'  => $input['terminal']['vpa'] ?? null ];
                }
                break;

            case Flow::INTENT:
                if ($response->isV2())
                {
                    $data = ['intent_url' => $result['next']['intent_url']];
                }
                else
                {
                    $data = ['intent_url' => $result['next']['redirect']['url']];
                }

        }

        return ['data' => $data];
    }

    protected function upiPrepareCallbackResponse(Response $response, array $input)
    {
        $upi     = $response->getUpi();
        $payment = $response->getPayment();

        if ($response->isV2() === true)
        {
            if ($upi['vpa'] === null)
            {
                return [
                    'acquirer' => [
                        Payment\Entity::REFERENCE16 => $upi['npci_reference_id'] ?? null,
                    ],
                    'amount_authorized' => $payment['amount_authorized'],
                    'currency'          => $payment['currency'],
                ];
            }
            return [
                'acquirer' => [
                    Payment\Entity::VPA         => $upi['vpa'] ?? null,
                    Payment\Entity::REFERENCE16 => $upi['npci_reference_id'] ?? null,
                ],
                'amount_authorized' => $payment['amount_authorized'],
                'currency'          => $payment['currency'],
            ];
        }

        return [
            'acquirer' => [
                Payment\Entity::VPA         => $upi['vpa'] ?? $input['payment']['vpa'] ?? null,
                Payment\Entity::REFERENCE16 => $upi['rrn'] ?? null,
            ],
        ];
    }
    /**
     * Currently Mozart takes callback request in format
     * {
     *   "gateway":
     *      {
     *         "redirect" : {  <data > }
     *     }
     * }
     * @param array $input
     * @return array
     */
    protected function prepareCallbackInput(array $input)
    {
        $gateway = $input['gateway'];

        unset($input['gateway']);

        $input['gateway']['redirect'] = $gateway;

        return $input;
    }

    /**
     * For V1 responses
     * @param Response $response
     * @param $input
     */
    protected function upiRunCallbackValidations(Response $response, $input)
    {
        if ($response->isV2() === true) return;

        $this->assertAmount($input['payment']['amount'], $response->get('amount'));
    }

    /****************** Repository Helpers *************

     /*
     * @param array $input
     * @param array $attributes
     * @return Entity
     */
    protected function upiCreateGatewayEntity(array $input, array $attributes): Entity
    {
        $entity = new Entity;

        $action = $this->action;

        switch ($action)
        {
            case Action::REFUND:

                $entity->setRefundId($input['refund']['id']);

                $entity->setAmount($input['refund']['amount']);

                $entity->setPaymentId($input['payment']['id']);

                break;

            default:
                $entity->setAmount($input['payment']['amount']);

                $entity->setPaymentId($input['payment']['id']);
        }

        $entity->setAction($this->action);

        // Should be defined in the gateway
        $acquirer = static::ACQUIRER;

        if ($acquirer === null)
        {
            $acquirer = $input['payment']['gateway'];
        }

        $entity->setAcquirer($acquirer);

        $entity->setGateway($input['payment']['gateway']);

        $entity->generate($attributes);

        $entity->fill($attributes);

        try
        {
            // Changed from save -> saveOrFail and ignoring exception as only saveOrFail is overridden as of now for dual write
            $this->upiGetRepository()->saveOrFail($entity);
        }
        catch (\Throwable $exception){}

        return $entity;
    }

    /**
     * @param Entity $gatewayPayment
     * @param array $attributes
     * @return Entity
     */
    protected function upiUpdateGatewayEntity(Entity $gatewayPayment, array $attributes): Entity
    {
        $gatewayPayment->fill($attributes);

        $gatewayPayment->generatePspData($attributes);

        try
        {
            // Changed from save -> saveOrFail and ignoring exception as only saveOrFail is overridden as of now for dual write
            $this->upiGetRepository()->saveOrFail($gatewayPayment);
        }
        catch (\Throwable $exception){}

        return $gatewayPayment;
    }

    protected function upiGetRepository(): Repository
    {
        return app('repo')->upi;
    }

    /***************** Client Helpers *****************
     * @param array $input
     * @param $traceCode
     * @param string $action
     * @return array
     * @throws Exception\GatewayErrorException
     */
    protected function upiSendGatewayRequest(array $input, $traceCode, string $action)
    {
        $mozart = $this->getUpiMozartGatewayWithModeSet();

        $response = $mozart->sendUpiMozartRequest($input, $traceCode, $action);

        return $response;
    }

    protected function upiCheckErrorsAndThrowExceptionFromResponse(array $response)
    {
        if ($response['success'] !== true)
        {
            $error = collect($response['error']);

            $internalErrorCode = $error->get('internal_error_code', 'BAD_REQUEST_PAYMENT_FAILED');

            $gatewayErrorCode = $error->get('gateway_error_code', null);

            $gatewayErrorDesc = $error->get('gateway_error_description', null);

            throw new Exception\GatewayErrorException(
                $internalErrorCode,
                $gatewayErrorCode,
                $gatewayErrorDesc,
                [],
                null,
                $this->action);
        }
    }

    /**
     * UPI common function to trace gateway response
     * @param Response $response
     * @param $result
     * @param $traceCode
     */
    protected function upiTraceGatewayResponse(Response $response, $result, $traceCode)
    {
        $result['data'] = $response->toArrayTrace();

        $this->trace->info(
            $traceCode,
            [
                'response' => $result,
                'gateway'  => $this->gateway,
                'action'   => $this->action,
            ]
        );
    }

    /**
     * This function will be used for any action , we dont have
     * mode determined yet.
     * eg. pre_process
     * It sets the mode as `test` for all the environments except for
     * production
     * @return Upi\Mozart\Gateway
     */
     protected function getUpiMozartGatewayWithModeFromEnvironment()
     {
         $mozart = $this->getUpiMozartGatewayWithModeSet();

         if ($this->env === Environment::PRODUCTION)
         {
             $mozart->setMode(Mode::LIVE);
         }
        else
        {
            $mozart->setMode(Mode::TEST);
        }

        return $mozart;
     }

    /**
     * Returns UPI Mozart gateway
     * @return Upi\Mozart\Gateway
     */
    protected function getUpiMozartGatewayWithModeSet()
    {
        /**
         * @var $gateway Upi\Mozart\Gateway
         */
        $gateway = $this->app['gateway']->gateway('upi_mozart');

        $gateway->setMode($this->getMode());

        return $gateway;
    }

    /**
     * This functions safely attaches upi entity in the input
     * Case 1: upi block is set then we have to merge the upi entities.
     * Case 2: upi block is not set then we can directly send upi entity to gateway.
     * @param Entity $gatewayPayment
     * @param array $input Input has upi block with currently being set with two values
     * flow and expiry time, we will preserving the value and attaching all other upi data to it.
     * @return array
     */
    protected function upiMergeData(Entity $gatewayPayment, array $input)
    {
        if (isset($input['upi']) === true)
        {
           // Currently flow and type are conflicting between upi and upi_metadata
           // We will keep the flow of upi_metadata which is getting attached.
           $data = array_except($gatewayPayment->toArray(), [
                Entity::TYPE,
           ]);

           $data = array_merge($data, $input['upi']);

           return $data;
        }

        return $gatewayPayment->toArray();
    }

    /*
     * Attach Payment Remark using payment description.
     * */
    protected function upiAttachPaymentRemark(&$input)
    {
        $paymentDescription = $input['payment']['description'] ?? '';

        $filteredPaymentDescription = Payment\Entity::getFilteredDescription($paymentDescription);

        $description = $input['merchant']->getFilteredDba() . ' ' . $filteredPaymentDescription;

        $input[Entity::UPI][Entity::REMARK] = $description ? substr($description, 0, 50) : 'Pay via Razorpay';
    }

    protected function upiIsDuplicateUnexpectedPaymentV2($input)
    {
        $rrn = $input['upi']['npci_reference_id'];

        $gateway = $input['terminal']['gateway'];

        $upiEntity = $this->upiGetRepository()->fetchByNpciReferenceIdAndGateway($rrn, $gateway);

        if (empty($upiEntity) === false)
        {
            // TODO: To fix this logic later by freezing one rrn i.e updating old payment rrn and create new payment

            if ($upiEntity->getAmount() === (int) ($input['payment']['amount']))
            {
                throw new Exception\LogicException(
                    'Duplicate Unexpected payment with same amount',
                    null,
                    [
                        'callbackData' => $input
                    ]
                );
            }
        }
    }

    protected function upiAuthorizePushV2($input)
    {
        [$paymentId, $content] = $input;

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Upi\Base\Type::PAY,
            Entity::RECEIVED            => 1,
        ];

        $attributes = array_merge($attributes, $content['upi']);

        $payment  = $content['payment'];

        $upi      = $content['upi'];

        $gateway = $content['terminal']['gateway'];

        // Create input structure for upi entity.
        $input = [
            'payment'    => [
                'id'       => $paymentId,
                'gateway'  => $gateway,
                'vpa'      => $upi['vpa'],
                'amount'   => $payment['amount'],
            ],
        ];

        // Call to set the input in gateway
        parent::action($input, Action::AUTHORIZE);

        $gatewayPayment = $this->upiCreateGatewayEntity($input, $attributes);

        return [
            'acquirer' => [
                Payment\Entity::VPA           => $gatewayPayment->getVpa(),
                Payment\Entity::REFERENCE16   => $gatewayPayment->getNpciReferenceId(),
            ]
        ];
    }

    /**
     * Check if its a valid Unexpected Payment
     * @param array $callbackData
     * @throws Exception\LogicException
     * @throws GatewayErrorException
     */
    protected function upiIsValidUnexpectedPaymentV2($callbackData)
    {
        //
        // Verifies if the payload specified in the server callback is valid.
        //
        $input = [
            'payment'       => [
                'id'             => $callbackData['upi']['merchant_reference'],
                'gateway'        => $callbackData['terminal']['gateway'],
                'vpa'            => $callbackData['upi']['vpa'],
                'amount'         => (int) ($callbackData['payment']['amount']),
            ],
            'terminal'      => $this->terminal,
            'upi'           => $callbackData['upi'],
            'gateway'       => [
                'cps_route'     => Payment\Entity::UPI_PAYMENT_SERVICE,
            ]
        ];

        $this->action = Action::VERIFY;

        $verify = new Verify($input['payment']['gateway'], $input);

        $this->sendPaymentVerifyRequest($verify);

        $paymentAmount = $verify->input['payment']['amount'];

        $content = $verify->verifyResponseContent;

        $actualAmount = $content['data']['payment']['amount_authorized'];

        $this->assertAmount($paymentAmount, $actualAmount);

        $status = $content['success'];

        $this->checkUnexpectedPaymentResponseStatus($status);
    }

    protected function checkUnexpectedPaymentResponseStatus($status)
    {
        if ($status !== true)
        {
           throw new Exception\GatewayErrorException(
               ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
           );
        }
    }

    /**
     * Returns true if the request is in testing environment
     * and is to be routed through upi payment service
     *
     * @param string $rzpTestCaseID
     *
     * @return bool
     */
    private function isRearchBVTRequestForUPIPreProcess(?string $rzpTestCaseID): bool
    {
        if (empty($rzpTestCaseID) === true)
        {
            return false;
        }

        return ((app()->isEnvironmentQA() === true) and (str_ends_with($rzpTestCaseID,'_rearchUPS') === true));
    }

    /**
     * This is explicitly used when a gateway callback comes for a certain UPI gateway and we detect that the payment
     * was actually made via the QR Code v2 flow.
     * upi_icici and upi_yesbank have their own implementations of this method for now.
     * This method is being released in a trial phase, hence the above overrides are not removed from their classes.
     * upi_icici does not support the holy grail contract for qrV2 payments yet, and hence it will continue overriding.
     * This method is also only applicable for upi_kotak for now, will be made general for all gateways in future releases.
     * payer_account_type is not supported for now, thus it will affect CC on UPI payments.
     * @param array       $input
     * @param string|null $gateway
     *
     * @return array
     * @throws Exception\GatewayErrorException
     */
    public function getQrData(array $input, string $gateway = null): array
    {
        $this->trace->info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'input'   => $input,
                'gateway' => $gateway,
            ]
        );

        if ($gateway === null)
        {
            if (empty($this->gateway) === true)
            {
                $this->trace->error(
                    TraceCode::GATEWAY_NOT_ENROLLED_ERROR,
                    [
                        'input'   => $input,
                        'message' => 'Gateway does not support getQrData method',
                        'merchant_reference' => $input['data']['upi'][Entity::MERCHANT_REFERENCE],
                    ]
                );

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_BQR_PAYMENT_FAILED,
                    null,
                    $input,
                    'Gateway does not support getQrData method'
                );
            }

            $gateway = $this->gateway;
        }

        $qrData = null;

        if (in_array($gateway, self::$qrCodePaymentGateways, true) === true)
        {
            $inputFields = $input['data'];

            // Check if the payment was successful or not
            // Make sure that Mozart is returning success as true in the response to make this work
            if (boolval($input[Payment\Gateway::SUCCESS]) !== true)
            {
                $this->trace->error(
                    TraceCode::QR_PAYMENT_FAILED_TRANSACTION_CALLBACK,
                    [
                        'notification_request' => $input,
                        'gateway'              => $this->gateway
                    ]);

                throw new Exception\GatewayErrorException(
                    ErrorCode::BAD_REQUEST_BQR_PAYMENT_FAILED,
                    null,
                    null,
                    [
                        'notification_request' => $input,
                        'gateway'              => $this->gateway
                    ]);
            }

            // Make sure that the Mozart response has all the fields necessary from below.
            // Else, we will see errors here.
            $qrData = [
                // Amount is already expected to be converted to paise in Mozart
                QrGatewayResponseParams::AMOUNT                => $inputFields['payment'][Payment\Entity::AMOUNT_AUTHORIZED],
                QrGatewayResponseParams::VPA                   => $inputFields['upi'][Entity::VPA],
                QrGatewayResponseParams::METHOD                => Payment\Method::UPI,
                QrGatewayResponseParams::GATEWAY_MERCHANT_ID   => $inputFields['terminal'][\RZP\Models\Terminal\Entity::GATEWAY_MERCHANT_ID],
                QrGatewayResponseParams::MERCHANT_REFERENCE    => $this->getQrPaymentMerchantReference($inputFields['upi'][Entity::MERCHANT_REFERENCE]),
                QrGatewayResponseParams::PROVIDER_REFERENCE_ID => $inputFields['upi'][Entity::NPCI_REFERENCE_ID],
                QrGatewayResponseParams::PAYEE_VPA             => $inputFields['terminal'][TerminalEntity::VPA],
            ];

            /* NOTE: payer_account_type to be figured out later, as Kotak has not provided any details
            $payerAccountType = $this->getInternalPayerAccountType($inputFields);

            if (isset($payerAccountType) === true)
            {
                $qrData[QrGatewayResponseParams::PAYER_ACCOUNT_TYPE] = $payerAccountType;
            }
            */

            $transactionTime = null;

            if (empty($inputFields['gateway_timestamp'] === false))
            {
                // Bad assumption for transaction time format
                // Making it work for Kotak for now
                // We will have to figure this out correctly once other gateways start using this.
                // TODO: Ideally, this conversion should happen on Mozart.
                try
                {
                    $transactionTime = Carbon::createFromFormat('Y-m-d H:i:s.v', $inputFields['gateway_timestamp'],
                                                                Timezone::IST);
                }
                catch (InvalidFormatException $e)
                {
                    // We are only catching this exception and tracing it for now
                    // We know that recon can only send timestamp in Y-m-d format
                    // Thus missing H:i:s.v data can cause this exception
                    $this->trace->traceException(
                        $e,
                        Logger::WARNING,
                        TraceCode::QR_DATA_TIMESTAMP_DATA_MISSING,
                        [
                            'input_timestamp' => $inputFields['gateway_timestamp'],
                        ]
                    );
                }

                if (empty($transactionTime) !== true)
                {
                    $qrData[QrGatewayResponseParams::TRANSACTION_TIME] = $transactionTime->getTimestamp();
                }
            }

            if (isset($input['data']['meta']) === true)
            {
                unset($input['data']['meta']);
            }
            if (isset($input['data']['_raw']) === true)
            {
                unset($input['data']['_raw']);
            }
        }

        return [
            'callback_data' => $input,
            'qr_data'       => $qrData
        ];
    }

    /**
     * This function removes any prefix and suffix in the merchant reference string. This helps us isolate out the QR
     * code ID for further processing.
     * Each gateway should override the qrPaymentMerchantPrefix and qrPaymentMerchantSuffix to faciliate this method.
     *
     * NOTE- This should be moved to Mozart only when QR code has their own namespace on Mozart.
     * Till then, we need the 'qrv2' suffix on API to differentiate UPI payments and QRv2 payments.
     * @param string $merchantReference
     *
     * @return string
     */
    public function getQrPaymentMerchantReference($merchantReference)
    {
        //TODO: For the far future, make sure to have a proper length check for merchantReference string
        // This is to avoid any complication due to the ref. string containing the prefix or suffix itself.
        // Though this is very rare, better be safe than sorry.

        // We expect gateways to define their own gateway prefix property for QR
        if ((empty($this->qrPaymentMerchantRefPrefix) === false) and
            (str_starts_with($merchantReference, $this->qrPaymentMerchantRefPrefix)))
        {
            $merchantReference = substr($merchantReference, strlen($this->qrPaymentMerchantRefPrefix));
        }

        // Although the suffix has been hard coded for qrv2 in this trait, gateway classes are free to override the
        // property when needed. Just make sure that you are using the same suffix during QR code creation as well
        if ((empty($this->qrPaymentMerchantRefSuffix)) === false and
            (str_ends_with($merchantReference, $this->qrPaymentMerchantRefSuffix)))
        {
            // Can not use the function str_before() here
            // This is to avoid the remote possibility that the ID itself contains the substring 'qrv2' :)
            $merchantReference = substr($merchantReference, 0, -1 * strlen($this->qrPaymentMerchantRefSuffix));
        }

        return $merchantReference;
    }

    /**
     * This function is used to create the UPI entity for a QR payment during payment authorise step.
     * This is important as we need the UPI entity during refunds.
     * NOTE- upi_icici and upi_yesbank use a different method, they shall be migrated here once holy grail contract is
     * established for QR payments.
     *
     * Also, this method does not support refund or (deprecated) payout action as of now.
     * @param $input
     * @param $action
     *
     * @return array[]
     */
    protected function createUpiEntityForQrPayment($input, $action): array
    {
        $entity = new Entity;

        $entity->setAmount($this->input['payment']['amount']);

        $entity->setPaymentId($this->input['payment']['id']);

        $entity->setAction($action);

        // Acquirer is set to mozart similar to all other UPI payment gateways on Mozart
        $entity->setAcquirer('mozart');

        // Since this gateway name is used in the query to fetch relevant details for UPI payment refund, we need to set
        // the proper gateway here.
        // Question- Is it better to do it using the payment entity in the input or the terminal entity??
        $entity->setGateway($input['terminal']->getGateway());

        $entity->generate($input);

        $entity->fill($input);

        $this->repo->saveOrFail($entity);

        return [
            'acquirer' => [
                Payment\Entity::REFERENCE16 => $entity->getNpciReferenceId(),
            ],
        ];
    }
}
