<?php

namespace RZP\Models\Address\AddressConsent1ccAudits;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'address_consent_1cc_audits';

    /**
     * SELECT count(*) FROM address_consent_1cc_audits where contact= ?
     * AND created_at >= ?
     */
    public function fetchAddressConsent1ccAuditsByContact($contact)
    {
        $nowMinus30Days = Carbon::today(Timezone::IST)->subDays(30)->getTimestamp();

        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::CONTACT, $contact)
            ->where(Entity::CREATED_AT, '>', $nowMinus30Days)
            ->count();
    }
}
