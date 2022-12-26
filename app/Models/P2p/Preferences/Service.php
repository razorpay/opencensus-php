<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Exception;
use RZP\Models\P2p\Base;

/**
 * @property  Processor     $processor
 */
class Service extends Base\Service
{
    public function getPreferences(array $input): array
    {
        $response = $this->processor->getPreferences($input);

        return $response;
    }
}
