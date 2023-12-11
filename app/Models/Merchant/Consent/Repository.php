<?php


namespace RZP\Models\Merchant\Consent;

use Illuminate\Support\Facades\DB;
use RZP\Base\ConnectionType;
use RZP\Constants\Mode;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Merchant\Detail\Constants as DEConstants;
use RZP\Models\Merchant\Consent\Details as ConsentDetails;

class Repository extends Base\Repository
{

    protected $entity = 'merchant_consents';

    public function getConsentDetailsForMerchantIdAndConsentFor(string $merchantId, array $validLegalDocs, string $connectionType = null)
    {
        if ($connectionType === null)
        {
            $connectionType = ConnectionType::REPLICA;
        }

        return $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getConsentForMerchantIdAndConsentTypeFromSlaveLive(string $merchantId, array $validLegalDocs)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection(Mode::LIVE))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    public function getUniqueMerchantIdsWithConsentsNotSuccess($intervalTime, $validLegalDocs)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->where(Entity::RETRY_COUNT, '<', Constants::STORE_CONSENTS_MAX_ATTEMPT)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->where(Entity::CREATED_AT, '>', $intervalTime)
                    ->take(1000)
                    ->distinct()
                    ->get()
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function getAllConsentDetailsForMerchant(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function getFailedConsentDetailsForMerchants($merchantId, $validLegalDocs)
    {
        $consentDetailsIdColumn = $this->dbColumn(Entity::DETAILS_ID);
        $detailIdColumn         = $this->repo->merchant_consent_details->dbColumn(ConsentDetails\Entity::ID);

        $url                = $this->repo->merchant_consent_details->dbColumn(ConsentDetails\Entity::URL);
        $consentFor         = $this->dbColumn(Entity::CONSENT_FOR);
        $retryCount         = $this->dbColumn(Entity::RETRY_COUNT);
        $createdAt          = $this->dbColumn(Entity::CREATED_AT);
        $metadata           = $this->dbColumn(Entity::METADATA);

        $userAttrs = [
            $url,
            $consentDetailsIdColumn,
            $consentFor,
            $retryCount,
            $createdAt,
            $metadata
        ];

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->select($userAttrs)
                    ->join(Table::MERCHANT_CONSENT_DETAILS, $detailIdColumn, '=', $consentDetailsIdColumn)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->where(Entity::RETRY_COUNT, '<', Constants::STORE_CONSENTS_MAX_ATTEMPT)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->whereNotNull(Entity::DETAILS_ID)
                    ->get();
    }

    public function getConsentDetailsForRequestId($requestId)
    {
        return $this->newQuery()
                    ->where(Entity::REQUEST_ID, '=', $requestId)
                    ->get();
    }

    public function fetchMerchantConsentDetails($merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONSENT_FOR, '=', $type)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->first();
    }

    public function fetchMerchantConsentForTypeAndDetailsId($merchantId, $type, $detailsId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONSENT_FOR, '=', $type)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->where(Entity::DETAILS_ID, '=', $detailsId)
                    ->first();
    }

    public function fetchAllConsentForMerchantIdAndConsentType($merchantId, $validLegalDocs)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function fetchMerchantConsentDetailsWithEntityIdAndEntityType(string $merchantId, string $type, string $entityId = null, string $entityType = null)
    {
        $query = $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONSENT_FOR, '=', $type)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS);


        if($entityId != null && $entityType != null)
        {
            $query->where(Entity::ENTITY_ID, '=', $entityId)
                  ->where(Entity::ENTITY_TYPE, '=', $entityType);
        }

        return $query->first();
    }

    public function getConsentDetailsForMerchantIdAndEntityId(string $merchantId, array $validLegalDocs, string $entityId, string $entityType, string $connectionType = null)
    {
        if ($connectionType === null)
        {
            $connectionType = ConnectionType::REPLICA;
        }

        return $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }
}
