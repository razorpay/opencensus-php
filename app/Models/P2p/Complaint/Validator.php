<?php

namespace RZP\Models\P2p\Complaint;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;

class Validator extends Base\Validator
{
    protected static $initiateTurboCallbackRules;

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
                  Entity::CRN          => 'sometimes',
                  Entity::GATEWAY_DATA => 'sometimes',
                  Entity::META         => 'sometimes',
                  Entity::ENTITY_ID    => 'sometimes',
                  Entity::ENTITY_TYPE  => 'sometimes',
                  Entity::UPDATED_AT   => 'sometimes',
                ]);

        return $rules;
    }
}
