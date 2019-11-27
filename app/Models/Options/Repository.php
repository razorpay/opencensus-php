<?php

namespace RZP\Models\Options;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'options';

    /**
     * Gets the Options created by the Merchant for a namespace.
     *
     * @param  string  $merchantId
     * @param  string  $namespace
     * @return Entity
     */
    public function getOptionsForMerchantAndNamespace(string $merchantId, string $namespace)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::NAMESPACE, '=', $namespace)
            ->where(Entity::SCOPE, '=', Constants::SCOPE_GLOBAL)
            ->first();
    }

    /**
     * Gets the Options created by the Merchant using API for a service and reference ID
     *
     * @param  string  $merchantId
     * @param  string  $service
     * @param  string  $referenceId
     * @return Entity
     */
    public function getOptionsForMerchantServiceAndReferenceId(
        string $merchantId,
        string $service,
        string $referenceId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::SERVICE_TYPE, '=', $service)
            ->where(Entity::REFERENCE_ID, '=', $referenceId)
            ->where(Entity::SCOPE, '=', Constants::SCOPE_ENTITY)
            ->first();
    }
}
