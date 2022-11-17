<?php


namespace RZP\Models\Merchant\Consent;

use Illuminate\Support\Facades\DB;
use RZP\Base\ConnectionType;
use RZP\Constants\Table;
use RZP\Models\Base;
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

    public function getUniqueMerchantIdsWithConsentsNotSuccess(array $validLegalDocs, $intervalTime)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->where(Entity::RETRY_COUNT, '<', Constants::STORE_CONSENTS_MAX_ATTEMPT)
                    ->whereIn(Entity::CONSENT_FOR, $validLegalDocs)
                    ->where(Entity::CREATED_AT, '>', $intervalTime)
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

    public function getFailedConsentDetailsForMerchants($merchantId)
    {
        $consentDetailsIdColumn = $this->dbColumn(Entity::DETAILS_ID);
        $detailIdColumn         = $this->repo->merchant_consent_details->dbColumn(ConsentDetails\Entity::ID);

        $url        = $this->repo->merchant_consent_details->dbColumn(ConsentDetails\Entity::URL);
        $consentFor = $this->dbColumn(Entity::CONSENT_FOR);
        $retryCount = $this->dbColumn(Entity::RETRY_COUNT);

        $userAttrs = [
            $url,
            $consentDetailsIdColumn,
            $consentFor,
            $retryCount
        ];

        return $this->newQuery()
                    ->select($userAttrs)
                    ->join(Table::MERCHANT_CONSENT_DETAILS, $detailIdColumn, '=', $consentDetailsIdColumn)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->where(Entity::RETRY_COUNT, '<', Constants::STORE_CONSENTS_MAX_ATTEMPT)
                    ->whereNotNull(Entity::DETAILS_ID)
                    ->get();
    }

    public function getConsentDetailsForRequestId($requestId, $consentFor)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::REQUEST_ID, '=', $requestId)
                    ->where(Entity::CONSENT_FOR, '=', $consentFor)
                    ->first();
    }

    public function fetchMerchantConsentDetails($merchantId, $type)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONSENT_FOR, '=', $type)
                    ->where(Entity::STATUS, '<>', Constants::SUCCESS)
                    ->first();
    }

}
