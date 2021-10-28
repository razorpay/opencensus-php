<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Exception;
use RZP\Gateway\Upi;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use RZP\Gateway\Base\Verify;
use RZP\Constants\Environment;
use RZP\Gateway\Base\VerifyResult;
use RZP\Models\Payment\UpiMetadata\Flow;

/**
 * CommonGatewayTrait
 * Trait Common
 * A common trait to make gateway use upi flow in the gateway
 * every function is implemented with namespaced version of the action
 * to avoid trait conflicts.
 * @package RZP\Gateway\Upi\Base
 * @property $action
 * @property $input
 */
trait CommonGatewayTrait
{
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
     * Pre Process function will be callback function , The purpose it serves that it makes the callback
     * to comply with the contracts . Give a simple interface to work with.
     * @param array $input
     * @return array
     */
    public function upiPreProcess(array $input)
    {
        $gatewayInput = [
            'gateway'  => $input,
            'terminal' => '',    // Attaching it so mozart request sender does not fail
            'payment'  => [
                'gateway' => $this->gateway,
                'id'      => '',
            ]
        ];

        $mozart = $this->getUpiMozartGatewayWithModeFromEnvironment();

        $result = $mozart->sendUpiMozartRequest(
            $gatewayInput,
            TraceCode::GATEWAY_PRE_PROCESS_CALLBACK,
            'pre_process'
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
        list ($paymentId, $content) = $data;

        $response = new Response($content['data'] ?? []);

        // Create attributes for upi entity.
        $attributes = [
            Entity::TYPE                => Upi\Base\Type::PAY,
            Entity::RECEIVED            => 1,
        ];

        $attributes = array_merge($attributes, $response->getFilteredUpi());

        $payment  = $response->getPayment();

        $upi      = $response->getUpi();

        // Create input structure for upi entity.
        $input = [
            'payment'    => [
                'id'       => $paymentId,
                'gateway'  => $this->gateway,
                'vpa'      => $upi['vpa'],
                'amount'   => $payment['amount_authorized'],
            ],
        ];

        // Call to set the input in gateway
        parent::authorize($input);

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

        $this->upiGetRepository()->save($entity);

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

        $this->upiGetRepository()->save($gatewayPayment);

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
}
