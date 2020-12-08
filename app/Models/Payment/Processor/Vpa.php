<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentsUpi;
use Razorpay\Trace\Logger as Trace;

trait Vpa
{
    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\GatewayErrorException
     * @throws Exception\RuntimeException
     */
    public function validateVpa(array $input)
    {
        $action = Payment\Action::VALIDATE_VPA;

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);

        $existing = $this->validateVpaCheckForExisting($input);

        if (empty($existing) === false)
        {
            $this->trace->info(
                TraceCode::VPA_ALREADY_VALIDATED,
                $existing
            );

            return $existing;
        }

        $terminalIds = Payment\Gateway::getTerminalsForValidateVpaForMode($this->mode);

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
                                                    'validate_vpa_routing_v2',
                                                    Mode::LIVE);

        $tracable = [
            'code'          => TraceCode::VALIDATE_VPA_REQUEST,
            'variant'       => $variant,
            'vpa'           => mask_vpa($input['vpa']),
            'success'       => false,
        ];

        if (($this->mode === Mode::LIVE) and ($variant === Payment\Gateway::UPI_SBI))
        {
            $terminalIds = ['AK6NMmzbL6FPe4', '9Q8w9weX9D1T27', '6KTOhwf4XBOMns'];
        }

        if (($this->mode === Mode::LIVE) and ($variant === Payment\Gateway::UPI_ICICI))
        {
            $terminalIds = ['6KTOhwf4XBOMns', '9Q8w9weX9D1T27', 'AK6NMmzbL6FPe4'];
        }

        $terminals = $this->repo->terminal->findManyEnabledByIds($terminalIds);

        $terminals = $terminals->sortBy(function ($terminal) use ($terminalIds) {
                    return array_search($terminal->getId(), $terminalIds);
        });

        $count = count($terminals);

        if ($count < 1)
        {
            throw new Exception\RuntimeException(
                ErrorCode::SERVER_ERROR);
        }

        // Input, GatewayInput and Response are currently same, we are using different variable
        // names as make sure there usage are not mixed, and later they all can be different.
        $gatewayData = $input;

        $response = $input;

        $success = false;

        $gatewayResponse = null;

        foreach ($terminals as $index => $terminal)
        {
            $startTime = Carbon::now();

            try
            {
                $gateway = $terminal->getGateway();

                $tracable['gateway'] = $gateway;

                // Invalid vpa on MindGate and SBI thrown back with GatewayError
                $gatewayResponse = $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);

                $tracable['time'] = Carbon::now()->diffInRealSeconds($startTime);

                $tracable['success'] = true;

                $tracable['gatewayResponse'] = $gatewayResponse;

                $this->trace->info(TraceCode::VALIDATE_VPA_REQUEST, $tracable);

                $success = true;

                break;
            }
            catch (Exception\GatewayErrorException $exception)
            {
                // As of now, MindGate sends INVALID VPA code when gateway returns code VN.
                // SBI does not have this check, but we currently do not need that.
                // Now, If the code is INVALID VPA, we can skip calling next VPA.
                if ($exception->getCode() === ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA)
                {
                    break;
                }
                $tracable['time'] = Carbon::now()->diffInRealSeconds($startTime);

                $this->trace->traceException($exception, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION, $tracable);

                // Throwing gateway exception now as we couldn't validate vpa on any of the applicable terminals
                if ($index === ($count - 1))
                {
                    throw new Exception\GatewayErrorException(ErrorCode::GATEWAY_ERROR_FATAL_ERROR);
                }
            }
        }

        $response['success'] = $success;

        $response['customer_name'] = $gatewayResponse;

        (new PaymentsUpi\Vpa\Service)->handleValidateVpaResponse($response);

        return $response;
    }

    public function validateVpaCheckForExisting(array $input)
    {
        $vpa = (new PaymentsUpi\Vpa\Service)->handleValidateVpaRequest($input);

        if (empty($vpa) === true)
        {
            return false;
        }

        $tracable = [
            'code'          => TraceCode::VALIDATE_VPA_REQUEST,
            'variant'       => 'cache',
            'vpa'           => mask_vpa($input['vpa']),
            'success'       => true,
        ];

        $this->trace->info(TraceCode::VALIDATE_VPA_REQUEST, $tracable);

        return [
            'vpa'               => $vpa->getAddress(),
            'success'           => true,
            'customer_name'     => $vpa->getName(),
        ];
    }
}
