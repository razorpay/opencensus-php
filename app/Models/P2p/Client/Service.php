<?php

namespace RZP\Models\P2p\Client;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Core          $core
 * @property  Validator     $validator
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function getGatewayConfig(array $input): array
    {
        $response = $this->processor->getGatewayConfig($input);

        return $response;
    }
}
