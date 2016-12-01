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
            AppToken\Entity::verifyIdAndStripSign($appTokenId);

            $appCore = new AppToken\Core;

            $appToken = $appCore->getAppByAppTokenId($appTokenId, $this->merchant);

            if ($appToken !== null)
            {
                $data = $appCore->deleteAppTokens($appToken, $input);

                return $data;
            }
        }

        return [];
    }
}
