<?php

namespace RZP\Models\P2p\Base\Traits;

use RZP\Base\BuilderEx;
use RZP\Models\Customer;

/**
 * @property Customer\Entity $customer
 *
 * Trait HasCustomer
 * @package RZP\Models\P2p\Base\Traits
 */
trait HasCustomer
{
    public function associateCustomer(Customer\Entity $handle)
    {
        return $this->customer()->associate($handle);
    }

    public function scopeCustomer(BuilderEx $query, Customer\Entity $customer)
    {
        return $query->where(self::BANK, $customer->getId());
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->customer()->getModel()->getSignedIdOrNull($this->getCustomerId());

        $array[self::BANK_ACCOUNT_ID] = $customerId;
    }
}
