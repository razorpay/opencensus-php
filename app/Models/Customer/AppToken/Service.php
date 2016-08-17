<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Models\Base;
use RZP\Models\Customer\AppToken;

class Service extends Base\Service
{
    public function deleteAppTokensForGlobalCustomer($input)
    {
        $appToken = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appToken !== null)
        {
            AppToken\Entity::verifyIdAndStripSign($appToken);

            $appCore = new AppToken\Core;

            $app = $appCore->getAppByAppToken($appToken, $this->merchant);

            $data = $appCore->deleteAppTokens($app, $input);

            return $data;
        }
    }
}
