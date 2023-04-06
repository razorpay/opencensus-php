<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Constants\Mode;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\PaymentsUpi;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Error\PublicErrorDescription;

trait Vpa
{
    // realtime validate vpa for the ixigo and bajaj merchants
    private $gatewayValidateMerchants = ['8RerE9oY0d7rbC','GCwhxngAcMtWC8', 'GDJYY4pJqT0cQ5', 'Epq7C3REXxW4po', 'KKvun1NXU95w0O','H9s3fChY9rxatj','KedEOyCoarYmHF','9uMVLUIRC372we','Fx5J0TjTKSQBOH'];

    /**
     * @param array $input
     *
     * @throws Exception\BadRequestException
     */
    public function checkoutValidateContactUpiNumber(array $input): void
    {
        $properties = [
            'id' => UniqueIdEntity::generateUniqueId(),
            'experiment_id' => $this->app['config']->get('app.checkout_upi_number_contact_blacklist_splitz_experiment_id'),
            'request_data' => json_encode(
                [
                    'merchant_id' => $this->merchant->getId(),
                    'contact' => $input['vpa'],
                ]
            ),
        ];

        try
        {
            $response = $this->app['splitzService']->evaluateRequest($properties);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::SPLITZ_ERROR
            );

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                'vpa',
                null,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_UPI_NUMBER
            );
        }

        $variant = $response['response']['variant']['name'] ?? '';

        if ($variant !== 'variant_on')
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                "vpa",
                null,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_UPI_NUMBER
            );
        }
    }

    public function validateVpa(array $input)
    {
        $action = Payment\Action::VALIDATE_VPA;

        // Since we are modifying input before validation,
        // check if vpa field is present in input.
        if (isset($input[Payment\Entity::VPA]) === true)
        {
            $input[Payment\Entity::VPA] = trim($input[Payment\Entity::VPA]);
        }

         // Redirect Standard Checkout UPI Number request to UPS
        if ($this->shouldValidateVpaThroughUpiPaymentService($input) === true)
        {
            if(isset($input[Payment\Analytics\Entity::LIBRARY]) &&
                $input[Payment\Analytics\Entity::LIBRARY] === Payment\Analytics\Metadata::CHECKOUTJS)
            {
                $this->checkoutValidateContactUpiNumber($input);
            }
            try
            {
                $this->trace->info(TraceCode::UPI_PAYMENT_SERVICE_VALIDATE_VPA,
                    [
                        'route' => $this->app['api.route']->getCurrentRouteName()
                    ]);

                return $this->app['upi.payments']->action(Payment\Action::VALIDATE_VPA,
                    $input,
                    Payment\Gateway::UPI_ICICI);

            }
            catch (Exception\GatewayErrorException $exception)
            {
                $exception->getError()->appendToField(Payment\Entity::VPA);

                $exception->getError()->setPaymentMethod(Payment\Method::UPI);

                $this->trace->traceException($exception, Trace::INFO,
                    TraceCode::RECOVERABLE_EXCEPTION,
                    [
                        'input' => $input
                    ]);

                throw $exception;
            }
        }

        // This will throw bad request validation error
        (new Payment\Validator)->validateInput($action, $input);

        $existing = $this->validateVpaCheckForExisting($input);

        if (empty($existing) === false)
        {
            $this->trace->info(
                TraceCode::VPA_ALREADY_VALIDATED,
                $existing
            );

            return $this->processValidateVpaResponse($existing);
        }

        $traceable = [];

        // Retrieve terminals for validate VPA.
        $terminals = $this->getTerminalsForValidateVpa($input[Payment\Entity::VPA], $traceable);

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

                $traceable['gateway'] = $gateway;

                // Invalid vpa on MindGate and SBI thrown back with GatewayError
                $gatewayResponse = $this->app['gateway']->call($gateway, $action, $gatewayData, $this->mode, $terminal);

                $traceable['time'] = Carbon::now()->diffInRealSeconds($startTime);

                $traceable['success'] = true;

                $traceable['gatewayResponse'] = $gatewayResponse;

                $this->trace->info(TraceCode::VALIDATE_VPA_REQUEST, $traceable);

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
                $traceable['time'] = Carbon::now()->diffInRealSeconds($startTime);

                $this->trace->traceException($exception, Trace::INFO, TraceCode::RECOVERABLE_EXCEPTION, $traceable);

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

        return $this->processValidateVpaResponse($response);;
    }

    public function processValidateVpaResponse($response)
    {
        if ($this->app['basicauth']->isPublicAuth())
        {
            return [
                'vpa'               => $response['vpa'],
                'success'           => $response['success'],
                // mask customer name
                'customer_name'     => mask_by_percentage($response['customer_name'], 0.9),
            ];
        }

        return $response;
    }

    public function validateVpaCheckForExisting(array $input)
    {
        $merchantId = $this->merchant->getId();

        if (in_array($merchantId, $this->gatewayValidateMerchants) === true)
        {
            return false;
        }

        $vpa = (new PaymentsUpi\Vpa\Service)->handleValidateVpaRequest($input);

        if (empty($vpa) === true)
        {
            return false;
        }

        $traceable = [
            'code'          => TraceCode::VALIDATE_VPA_REQUEST,
            'variant'       => 'cache',
            'vpa'           => mask_vpa($input['vpa']),
            'success'       => true,
        ];

        $this->trace->info(TraceCode::VALIDATE_VPA_REQUEST, $traceable);

        return [
            'vpa'               => $vpa->getAddress(),
            'success'           => true,
            'customer_name'     => $vpa->getName(),
        ];
    }

    /**
     * Return terminals to perform VPA validation.
     * @param string $vpa
     * @param $traceable
     * @return mixed
     */
    protected function getTerminalsForValidateVpa(string $vpa, &$traceable)
    {
        // Get terminals stored in env
        $terminalIds = Payment\Gateway::getTerminalsForValidateVpaForMode($this->mode);

        $variant = $this->app->razorx->getTreatment($this->app['request']->getTaskId(),
            'validate_vpa_routing_v2',
            Mode::LIVE);

        $traceable = [
            'code'          => TraceCode::VALIDATE_VPA_REQUEST,
            'variant'       => $variant,
            'vpa'           => mask_vpa($vpa),
            'success'       => false,
        ];

        if (($this->mode === Mode::LIVE) and ($variant === Payment\Gateway::UPI_SBI))
        {
            $terminalIds = ['AK6NMmzbL6FPe4', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', '6KTOhwf4XBOMns'];
        }

        if (($this->mode === Mode::LIVE) and ($variant === Payment\Gateway::UPI_ICICI))
        {
            $terminalIds = ['6KTOhwf4XBOMns', 'BZuiTusQVjb1a4', 'CrTfneH0erizag', 'CrWje4EiFnXUE8', 'AK6NMmzbL6FPe4'];
        }

        $terminals = $this->filterTerminalsForValidateVpa($terminalIds);

        return $terminals;
    }

    /**
     *  1. Retrieves enabled terminals from database.
     *  2. Filters enabled terminals to return only one mindgate terminal along with other gateway terminals.
     *  3. Sorts the enabled terminal in the required order.
     * @param array $terminalIds
     * @return mixed
     */
    protected function filterTerminalsForValidateVpa(array $terminalIds)
    {
        // get enabled terminals from the terminal Ids.
        $terminals = (new Terminal\Repository)->findManyEnabledByIds($terminalIds);

        $this->filterMindgateTerminalsForValidateVpa($terminals);

        // Sort the terminals as per required order.
        $terminals = $terminals->sortBy(function ($terminal) use ($terminalIds) {
            return array_search($terminal->getId(), $terminalIds);
        });

        $selectedTerminals = $terminals->values();

        return $selectedTerminals;
    }

    /**
     * Mindgate terminals are merchant terminals and not shared, we decided to distribute the traffic among
     * 3 merchant terminal so that single merchant using mindgate terminal should not get affected.
     * 1. Filters the terminal to include only one mindgate terminal along with other gateway terminal.
     * @param $terminals
     */
    protected function filterMindgateTerminalsForValidateVpa(&$terminals)
    {
        // get all the enabled mindgate terminals
        $mindgateEnabledTerminals = $terminals->filter(function ($terminal) {
            return ($terminal->getGateway() === Payment\Gateway::UPI_MINDGATE);
        });

        // if there are is more than 1 mindgate terminals to choose from.
        if (count($mindgateEnabledTerminals) > 1)
        {
            // Randomly choose an enabled mindgate terminal.
            $selectedTerminal = $mindgateEnabledTerminals->random();

            // Filter enabled terminals to only include one mindgate terminal
            // (which is randomly chosen in the above step) along with other gateway terminals.
            $terminals = $terminals->filter(function ($terminal) use ($mindgateEnabledTerminals, $selectedTerminal){

                // Check if the terminal is mindgate terminal and
                // don't choose if it is not the randomly selected terminal
                if (($terminal->getGateway() === Payment\Gateway::UPI_MINDGATE) and
                    ($terminal !== $selectedTerminal))
                {
                    return false;
                }

                return true;
            });
        }
    }

    /**
     *  Checks whether the validate vpa should be redirected to UPS based on input and route
     * @param array $input
     * @return bool
     */

    protected function shouldValidateVpaThroughUpiPaymentService(array $input): bool
    {
        $route =  $this->app['api.route']->getCurrentRouteName();

        return ((is_numeric($input[Payment\Entity::VPA])) and ($route === 'payment_validate_account'));
    }
}
