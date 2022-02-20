<?php

namespace RZP\Models\P2p\Upi;

use Exception;
use RZP\Trace\TraceCode;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Mandate;
use RZP\Models\P2p\Transaction;
use Razorpay\Trace\Logger as Trace;

/**
 * @property  Core      $core
 * @property  Validator $validator
 * @property  Processor $processor
 */
class Service extends Base\Service
{
    public function gatewayCallback(array $input)
    {
        // Initiate Gateway callback has two responsibilities to process the callback
        // 1. provides the context data [Mandatory]
        // 2. provides entity data by transforming the request [Optional]
        $callbackArray = $this->processor->initiateGatewayCallback($input);

        // Default Response to every callback
        $response = [Base\Entity::SUCCESS => true];

        try
        {
            // Add actual input to callback so that gateway can validate the actual request itself
            $callbackArray[Base\Entity::REQUEST] = $input;

            // Gateway callback has three responsibilities
            // 1. resolves context based on the context data
            // 2. verifies or validates the callback with the resolved context
            // 3. provides entity data by transforming the request if it wasn't done in initiate call [Optional]
            // 4. Update the response
            $callbackArray = $this->processor->gatewayCallback($callbackArray);

            $response = $callbackArray[Base\Entity::RESPONSE];
            unset($callbackArray[Base\Entity::RESPONSE]);

            // unset request key before validation
            unset($callbackArray[Base\Entity::REQUEST]);

            $this->processCallback($callbackArray);
        }
        catch (Exception $e)
        {
            if (ExpectedHardFailures::isExpected($e, $callbackArray[Base\Entity::CONTEXT]) === false)
            {
                throw $e;
            }

            $this->trace->traceException($e, Trace::WARNING, TraceCode::P2P_CALLBACK_TRACE, [
                Base\Entity::CONTEXT      => $callbackArray[Base\Entity::CONTEXT],
                ExpectedHardFailures::KEY => true,
            ]);

            $response[ExpectedHardFailures::KEY] = $e->getMessage();
        }

        return $response;
    }

    protected function processCallback($callback)
    {
        $context = $callback[Base\Entity::CONTEXT];
        unset($callback[Base\Entity::CONTEXT]);

        switch ($context[Base\Entity::ENTITY])
        {
            case Transaction\Entity::TRANSACTION:

                $processor = new Transaction\Processor;

                $processor->processAction($context[Base\Entity::ACTION], $callback);

                break;

            case Transaction\Entity::CONCERNS:

                foreach ($callback[Transaction\Entity::CONCERNS] as $concern)
                {
                    $processor = new Transaction\Processor;

                    $device = $this->processor->resolveDeviceFromConcern($concern);

                    $processor->processAction($context[Base\Entity::ACTION], [
                        Transaction\Entity::CONCERN => $concern,
                    ], $device);
                }

                break;

            case Device\Entity::REGISTER_TOKEN:

                $processor = new Device\Processor;

                $processor->processAction($context[Base\Entity::ACTION],
                                          $callback[Device\Entity::REGISTER_TOKEN]);

                break;

            case Device\Entity::DEVICE:

                $processor = new Device\Processor();

                $this->processor->resolveContextFromDevice($context[Base\Entity::ACTION], $context);

                $processor->processAction($context[Base\Entity::ACTION], $context);

                break;

            case Mandate\Entity::MANDATE:

                $processor = new Mandate\Processor();

                $processor->processAction($context[Base\Entity::ACTION], $callback);

                break;
        }

    }

    public function reminderCallback($input)
    {
        $data = [
            Base\Entity::CONTEXT => $input
        ];

        $callback = $this->processor->initiateReminderCallback($data);

        $response = $this->processor->reminderCallback($data);

        $this->processCallback($callback);

        return $response;
    }
}
