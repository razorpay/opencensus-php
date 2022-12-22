<?php

namespace RZP\Models\Payment\Validation;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Entity;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment\Processor\AppTrait;

class Cred extends Base
{
    use AppTrait;

    protected $gateway = Payment\Gateway::CRED;
    protected $validateAction = Payment\Action::VALIDATE_CRED;

    /**
     * @param array $input
     * @param array $options
     * @return array
     *
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\GatewayTimeoutException
     * @throws Exception\GatewayErrorException
     * @throws Exception\LogicException
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function processValidation($input, array $options = [])
    {
        $methods = $this->merchant->getMethods();

        if ($methods->isCredEnabled() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cred not enabled for merchant',
                null,
                [
                    Payment\Entity::MERCHANT_ID => $this->merchant->getId(),
                ]
            );
        }

        $uniqueSessionId = null;

        if (isset($input['_']['checkout_id']) === true)
        {
            $uniqueSessionId = $input['_']['checkout_id'];
        }

        $validateInput = [
            Entity::CONTACT => $input['value'],
            'id'            => $uniqueSessionId,
        ];

        (new Payment\Validator)->validateInput($this->validateAction, $validateInput);

        $options['session_id'] = $uniqueSessionId;

        $gatewayInput = $this->constructGatewayInput($input, $options);

        try {
            $response = $this->validateApp($gatewayInput, $this->gateway, Payment\Action::VALIDATE_APP);

            (new Payment\Metric)->pushCredEligibilityMetrics($input, $response);

            return $response;
        }
        catch (\Throwable $exception)
        {
            $response['success'] = false;

            (new Payment\Metric)->pushCredEligibilityMetrics($input, $response, $exception);

            throw $exception;
        }
    }

    protected function constructGatewayInput(array $input, array $options) : array
    {
        $gatewayInput = [
            'payment'  => [
                'contact'     => $input['value'],
                'currency'    => $input['currency'] ?? Currency::INR,
                'gateway'     => Payment\Gateway::CRED,
            ],
            'cred'     => [
                'session_id'  => $options['session_id'],
            ],
            'options'  => $options,
        ];

        if (isset($input['_']['agent']) === true)
        {
            $agent = $input['_']['agent'];
            $gatewayInput['cred']['os']       = $agent['os'] ?? null;
            $gatewayInput['cred']['platform'] = $agent['platform'] ?? null;
            $gatewayInput['cred']['device']   = $agent['device'] ?? null;
        }

        return $gatewayInput;
    }

    protected function makeCredEligibilityResponse(array &$response, array $gatewayResponse)
    {
        $response['success'] = $gatewayResponse['data']['state'] == 'ELIGIBLE' ?? false;

        $response['data']['state'] = $gatewayResponse['data']['state'] ?? null;

        $response['data']['tracking_id'] = $gatewayResponse['data']['tracking_id'] ?? null;

        // offer to display at checkout
        if (isset($gatewayResponse['data']['layout']['sub_text']))
        {
            $response['data']['offer']['description'] = $gatewayResponse['data']['layout']['sub_text'];
        }
    }

    protected function addEligibilityEventDetailsForCred(array &$properties, array $input)
    {
        $properties['checkout_id'] = $input['cred']['session_id'];

        $properties['agent']       = [
            'os'       => $input['cred']['os'] ?? null,
            'platform' => $input['cred']['platform'] ?? null,
            'device'   => $input['cred']['device'] ?? null,
        ];
    }
}
