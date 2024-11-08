<?php

namespace RZP\Models\P2p\Session;

use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $createSessionRules;

    // makeCreateSessionRules will create the rules for create session api
    public function makeCreateSessionRules(): Base\Libraries\Rules
    {
       return $this->makeRules([
            Entity::CUSTOMER_REFERENCE  => 'required',
        ]);
    }
}
