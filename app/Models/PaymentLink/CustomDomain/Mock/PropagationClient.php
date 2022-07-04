<?php

namespace RZP\Models\PaymentLink\CustomDomain\Mock;

use RZP\Models\PaymentLink\CustomDomain\IPropagationClient;

final class PropagationClient implements IPropagationClient
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function checkPropagation(array $data): array
    {
        return [
            'propagated'    => true,
            'domain_name'   => $data['domain_name']
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
