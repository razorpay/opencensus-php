<?php


namespace RZP\Models\GupShup;

use RZP\Models\Base;
use RZP\Models\AppStore\Core as AppStoreCore;

class Service extends Base\Service
{
    const TEXT = 'text';

    public function handleIncomingMessagesCallback(array $input)
    {
        $messageType = $input['type'];

        switch ($messageType)
        {
            case self::TEXT:
                $message = $input[self::TEXT];

                $appStoreCore = new AppStoreCore();

                $userMobileNumber = $input['mobile'];

                return $appStoreCore->processGupShupMessages($userMobileNumber, $message);

            default:
                return ;
        }

    }
}
