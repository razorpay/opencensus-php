<?php

namespace RZP\Models\PaymentLink\CustomDomain\Mock;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\PaymentLink\CustomDomain\IAppClient;

final class AppClient implements IAppClient
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function createApp(array $data): array
    {
        return [
            'id'            => UniqueIdEntity::generateUniqueId(),
            'app_name'      => $data['app_name'],
            'callback_url'  => $data['callback_url'],
        ];
    }

    public function setApi($client)
    {
        // TODO: Implement setApi() method.
    }

    public function getApi()
    {
        // TODO: Implement getApi() method.
    }
}
