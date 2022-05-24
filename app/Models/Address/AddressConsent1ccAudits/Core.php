<?php

namespace RZP\Models\Address\AddressConsent1ccAudits;

use RZP\Models\Base;
use RZP\Models\Customer;

class Core extends Base\Core
{
    public function createAndSaveAudits($input)
    {
        $auditEntity = (new Entity)->build($input);

        $auditEntity->generateId();

        $this->repo->address_consent_1cc_audits->saveOrFail($auditEntity);

        return $auditEntity;
    }
}
