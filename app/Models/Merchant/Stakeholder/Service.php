<?php

namespace RZP\Models\Merchant\Stakeholder;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;

class Service extends Base\Service
{
    public function create(string $accountId, array $input)
    {
        (new Validator)->validateInput('create_stakeholder', $input);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->create($accountId, $input);

        return (new Response)->createResponse($stakeholder);
    }

    public function fetch(string $accountId, string $id)
    {
        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($id);
        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->fetch($accountId, $id);

        return (new Response)->createResponse($stakeholder);
    }

    public function fetchAll(string $accountId)
    {
        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholders = $this->core()->fetchAll($accountId);

        return (new Response)->createListResponse($stakeholders);
    }

    public function update(string $accountId, string $id, array $input)
    {
        (new Validator)->validateInput('edit_stakeholder', $input);

        (new Account\Core)->validatePartnerAccess($this->merchant, $accountId);

        Entity::verifyIdAndStripSign($id);
        Account\Entity::verifyIdAndStripSign($accountId);

        $stakeholder = $this->core()->update($accountId, $id, $input);

        return (new Response)->createResponse($stakeholder);
    }
}
