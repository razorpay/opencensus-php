<?php

namespace RZP\Models\PaymentLink\CustomDomain\Mock;

use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\PaymentLink\CustomDomain\IDomainClient;

final class DomainClient implements IDomainClient
{
    /**
     * @param array $data
     *
     * @return array
     */
    public function createDomain(array $data): array
    {
        return [
            "id"            => UniqueIdEntity::generateUniqueId(),
            "domain_name"   => $data['domain_name'],
            "merchant_id"   => $data['merchant_id'],
            "status"        => 'created',
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function listDomain(array $data): array
    {
        $data['count'] = 0;
        $data['entity'] = 'domains';
        $data['items'] = [];

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function deleteDomain(array $data): array
    {
        return [
            "id"            => UniqueIdEntity::generateUniqueId(),
            "domain_name"   => $data['domain_name'],
            "merchant_id"   => UniqueIdEntity::generateUniqueId(),
            "status"        => 'deleted',
        ];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function isSubDomain(array $data): array
    {
        return [
            "is_sub_domain" => false,
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
