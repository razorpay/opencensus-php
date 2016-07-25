<?php

namespace RZP\Models\Customer\App;

use RZP\Models\Base;
use RZP\Models\Customer\App;

class Service extends Base\Service
{
    public function deleteAppTokensForGlobalCustomer($input)
    {
        $appToken = App\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appToken !== null)
        {
            App\Entity::verifyIdAndStripSign($appToken);

            $appCore = new App\Core;

            $app = $appCore->getAppByAppToken($appToken, $this->merchant);

            $data = $appCore->deleteAppTokensForGlobalCustomer($app->customer, $input);

            return $data;
        }
    }
}
