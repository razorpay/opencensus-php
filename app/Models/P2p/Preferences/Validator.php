<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device\RegisterToken;
use RZP\Models\P2p\Base\Libraries\Context;

class Validator extends Base\Validator
{
    protected static $getPreferencesRules;

    public function makeGetPreferencesRules()
    {
        $rules = $this->makeRules([
                      Entity::CUSTOMER_ID  => 'required',
                      Entity::ORDER_ID  => 'sometimes',
                  ]);

        return $rules;
    }
}
