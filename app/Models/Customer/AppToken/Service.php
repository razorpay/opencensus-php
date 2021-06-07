<?php

namespace RZP\Models\Customer\AppToken;

use RZP\Models\Base;
use RZP\Models\Customer\AppToken;

class Service extends Base\Service
{
    public function deleteAppTokensForGlobalCustomer($input)
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appTokenId !== null)
        {
            $appCore = new AppToken\Core;

            $appToken = $appCore->getAppByAppTokenId($appTokenId, $this->merchant);

            if ($appToken !== null)
            {
                $data = $appCore->deleteAppTokens($appToken, $input);

                AppToken\SessionHelper::removeAppTokenFromSession($this->mode);

                return $data;
            }
            AppToken\SessionHelper::removeAppTokenFromSession($this->mode);
        }

        return [];
    }
}
