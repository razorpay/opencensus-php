<?php

namespace RZP\Models\RawAddress;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Services\BulkUploadClient;
use Carbon\Carbon;

class Repository  extends Base\Repository
{
    protected $entity = 'raw_address';

    public function fetchPendingContacts()
    {
        $contactCol   = $this->dbColumn(Entity::CONTACT);
        $statusCol    = $this->dbColumn(Entity::STATUS);
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT) ;

        $connection = $this->getMasterReplicaConnection();
        return  $this->newQueryWithConnection($connection)
                    ->distinct()
                    ->select($contactCol)
                    ->where($statusCol, BulkUploadClient::STATUS_PENDING)
                    ->where($createdAtCol, '>', Carbon::now()->subHours(3)->timestamp)
                    ->limit(500)
                    ->get();
    }

    public function fetchRawAddressesForContact(string $contact, $status = null)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $statusCol  = $this->dbColumn(Entity::STATUS);

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
        $contactCol  = $this->dbColumn(Entity::CONTACT);
        $merchantCol = $this->dbColumn(Entity::MERCHANT_ID);

        return $this->newQuery()
                    ->select($merchantCol)
                    ->where($contactCol, $contact)
                    ->first();
    }

    public function updateStatus(array $contacts ,string $oldStatus, string $newStatus)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);
        $statusCol  = $this->dbColumn(Entity::STATUS);
        $createdAtCol = $this->dbColumn(Entity::CREATED_AT) ;

        return $this->newQueryWithoutTimestamps()
            ->whereIn($contactCol, $contacts)
            ->where($statusCol,$oldStatus)
            ->update([$statusCol => $newStatus]);

    }

    public function bulkInsert($data)
    {
        return $this->newQuery()->insert($data);
    }

}
