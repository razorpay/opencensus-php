<?php

namespace Models\Customer\App;

use Models\Base;
use Models\Customer\App;

class Service extends Base\Service
{
    public function deleteAppTokensForGlobalCustomer($appToken, $input)
    {
        App\Entity::verifyIdAndStripSign($appToken);

        $appCore = new App\Core;

        $app = $appCore->getAppByAppToken($appToken, $this->merchant);

        $data = $appCore->deleteAppTokensForGlobalCustomer($app->customer, $input);

        return $data;
    }
}
