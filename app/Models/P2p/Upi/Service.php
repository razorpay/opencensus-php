<?php

namespace RZP\Models\P2p\Upi;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Error\P2p\ErrorCode;
use RZP\Models\P2p\Transaction;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function gatewayCallback(array $input)
    {
        $gatewayData = $this->processor->initiateGatewayCallback($input);

        $input[Base\Entity::GATEWAY_DATA] = $gatewayData;

        $callback  = $this->processor->gatewayCallback($input);

        $response = $callback[Base\Entity::RESPONSE];
        unset($callback[Base\Entity::RESPONSE]);

        $this->processCallback($callback);

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
