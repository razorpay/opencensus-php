<?php

namespace RZP\Models\P2p\Turbo;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Complaint;

/**
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function turboGatewayCallback(array $input)
    {
        // Initiate Gateway callback has two responsibilities to process the callback
        // 1. provides the context data [Mandatory]
        $callbackArray = $this->processor->initiateTurboCallback($input);

        $response = $callbackArray[Base\Entity::RESPONSE];

        $this->processCallback($callbackArray);

        return $response;
    }

    protected function processCallback($callback)
    {
        $context = $callback[Base\Entity::CONTEXT];
        unset($callback[Base\Entity::CONTEXT]);

        switch ($context[Base\Entity::ENTITY])
        {
            case Complaint\Entity::COMPLAINT:

                $processor = new Complaint\Processor;

                $processor->processAction($context[Base\Entity::ACTION], $callback);

                break;
        }
    }
}
