<?php

namespace RZP\Models\P2p\Turbo;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;

class Validator extends Base\Validator
{
    protected static $initiateTurboCallbackRules;

    public function makeInitiateTurboCallbackRules()
    {
        $rules = $this->makeRules([
                      Entity::PAYLOAD  => 'sometimes',
                      Entity::CONTENT  => 'sometimes',
                      Entity::HEADERS  => 'sometimes',
                      Entity::GATEWAY  => 'sometimes',
                  ]);

        return $rules;
    }
}
