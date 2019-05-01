<?php

namespace RZP\Models\P2p\Upi;

use RZP\Exception;
use RZP\Models\P2p\Base;
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
        $parsed = $this->processor->initiateGatewayCallback($input);

        $input['parsed'] = $parsed;

        $callback  = $this->processor->gatewayCallback($input);

        $context = $callback[Base\Entity::CONTEXT];
        unset($callback[Base\Entity::CONTEXT]);

        switch ($context[Base\Entity::ENTITY])
        {
            case Transaction\Entity::TRANSACTION:

                $processor = new Transaction\Processor;

                switch ($context[Base\Entity::ACTION])
                {
                    case Transaction\Action::INCOMING_COLLECT:

                        return $processor->incomingCollect($callback);
                }
        }
    }
}
