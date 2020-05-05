<?php

namespace RZP\Models\Merchant\Reminders;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_reminders';

    /**
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getByMerchantId(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->first();
    }

    /**
     * @param string $merchantId
     * @param string $namespace
     * @return mixed
     */
    public function getByMerchantIdAndNamespace(string $merchantId, string $namespace)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REMINDER_NAMESPACE, '=', $namespace)
            ->first();
    }
}
