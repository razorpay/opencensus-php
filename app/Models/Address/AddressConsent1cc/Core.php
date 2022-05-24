<?php

namespace RZP\Models\Address\AddressConsent1cc;

use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{
    public function createAndSaveConsent(Customer\Entity $customer, $input)
    {
        $input[Entity::CUSTOMER_ID] = $customer->getId();

        $consentEntity = (new Entity)->build($input);

        $consentEntity->generateId();

        $this->repo->address_consent_1cc->saveOrFail($consentEntity);

        return $consentEntity;
    }
}
