<?php

namespace RZP\Models\RawAddress;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Services\BulkUploadClient;

class Repository  extends Base\Repository
{
    protected $entity = 'raw_address';

    public function fetchPendingContacts()
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $statusCol    = $this->dbColumn(Entity::STATUS);

        return $this->newQuery()
                    ->distinct()
                    ->select($contactCol)
                    ->where($statusCol, BulkUploadClient::STATUS_PENDING)
                    ->limit(500)
                    ->get();
    }

    public function fetchRawAddressesForContact(string $contact, $status = null)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $statusCol    = $this->dbColumn(Entity::STATUS);

        $result = $this->newQuery()
                       ->selectRaw(Table::RAW_ADDRESS . '.*')
                       ->where($contactCol,$contact);

        if($status != null )
        {
            $result = $result->where($statusCol, $status);
        }

        return $result->get();
    }

    public function fetchMerchantIdForContact(string $contact)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $merchantCol = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($merchantCol)
                    ->where($contactCol, $contact)
                    ->first();
    }

    public function updateStatus(array $contacts , string $status)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $statusCol    = $this->dbColumn(Entity::STATUS);

        return $this->newQueryWithoutTimestamps()
            ->whereIn($contactCol, $contacts)
            ->update([$statusCol => $status]);

    }

}
