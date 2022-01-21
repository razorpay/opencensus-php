<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentsUpi;
use RZP\Models\Terminal;
use RZP\Models\Merchant;


trait AppTrait
{
    /**
     * @param array $input
     * @param $gateway
     * @param $action
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws Exception\LogicException
     * @throws Exception\GatewayErrorException
     */
    public function validateApp(array $input, $gateway, $action)
    {
        $params = [
            Terminal\Entity::MERCHANT_ID => $this->merchant->getId(),
            Terminal\Entity::GATEWAY     => $gateway,
            Terminal\Entity::STATUS      => Terminal\Status::ACTIVATED,
            Terminal\Entity::ENABLED     => 1,
        ];

        $terminals = $this->repo->terminal->getByParams($params, true);

        if (count($terminals) < 1)
        {
            //Check if Shared terminal is available.

            $params[Terminal\Entity::MERCHANT_ID] = Merchant\Account::SHARED_ACCOUNT;

            $terminals = $this->repo->terminal->getByParams($params);

            if (count($terminals) < 1)
            {
                throw new Exception\RuntimeException(
                    'No Terminal Found',
                    [
                        Terminal\Entity::MERCHANT_ID => $this->merchant->getId(),
                        Terminal\Entity::GATEWAY     => $gateway,
                        Terminal\Entity::ACTION      => $action
                    ],
                    null,
                    ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND
                );
            }
        }

        $this->app['diag']->trackPaymentEventV2(
            EventCode::PAYMENT_ELIGIBILITY_CHECK_INITIATED,
            null,
            null,
            [],
            $this->constructEligibilityEventProperties($input, $gateway)
        );

        $tracable = [
            Terminal\Entity::MERCHANT_ID => $this->merchant->getId(),
            Terminal\Entity::GATEWAY     => $gateway,
            Terminal\Entity::ACTION      => $action,
            'code'                       => TraceCode::GATEWAY_VALIDATE_REQUEST,
            'contact'                    => mask_phone($input['payment']['contact'] ?? null),
            'success'                    => false,
            'gateway_response'           => null,
            'mozart_id'                  => null,
            'error_code'                 => null,
        ];

        $gatewayResponse = null;

        $response = [
            'success' => false,
            'data'    => []
        ];

        $startTime = Carbon::now();

        $gatewayException = null;

        try {
            $gatewayResponse = $this->app['gateway']->call($gateway, $action, $input, $this->mode, $terminals[0]);

            switch ($gateway)
            {
                case Payment\Gateway::CRED:

                    $this->makeCredEligibilityResponse($response, $gatewayResponse);

                    break;

                default:

                    throw new Exception\LogicException(
                        'Invalid gateway!',
                        ErrorCode::BAD_REQUEST_ERROR,
                        [
                            'gateway' => $gateway
                        ]
                    );
            }

            // do redaction if required

            $tracable['gateway_response'] = $gatewayResponse;

            $tracable['success'] = true;

            $tracable['mozart_id'] = $gatewayResponse['mozart_id'] ?? null;
        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $tracable['error_code'] = $exception->getCode();

            $gatewayException = $exception;

            throw $exception;
        }
        finally
        {
            $tracable['time'] = Carbon::now()->diffInRealSeconds($startTime);

            $this->trace->info(
                TraceCode::GATEWAY_VALIDATE_REQUEST,
                $tracable
            );

            $this->app['diag']->trackPaymentEventV2(
                EventCode::PAYMENT_ELIGIBILITY_CHECK_PROCESSED,
                null,
                $gatewayException,
                [],
                $this->constructEligibilityEventProperties($input, $gateway)
            );
        }

        return $response;
    }

    public function constructEligibilityEventProperties($input, $gateway) : array
    {
        $properties = [
            'merchant' => [
                'id'        => $this->merchant->getId(),
                'name'      => $this->merchant->getBillingLabel(),
                'mcc'       => $this->merchant->getCategory(),
                'category'  => $this->merchant->getCategory2(),
            ],
            'gateway'  => $gateway,
        ];

        switch ($gateway)
        {
            case Payment\Gateway::CRED:

                $this->addEligibilityEventDetailsForCred($properties, $input);

                break;

            default:
                break;

        }

        return $properties;
    }

}
