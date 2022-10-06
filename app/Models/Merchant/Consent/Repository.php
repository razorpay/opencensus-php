<?php


namespace RZP\Models\Merchant\Consent;

use RZP\Base\ConnectionType;
use RZP\Constants\Table;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Base;
use RZP\Models\Merchant\Consent\Details as ConsentDetails;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'merchant_consents';

    public function getConsentDetailsForMerchantIdandConsentFor(string $merchantId, string $activationFormMilestone, string $connectionType = null)
    {
        if($connectionType === null)
        {
            $connectionType = ConnectionType::REPLICA;
        }

        return $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::CONSENT_FOR, 'LIKE', $activationFormMilestone . '%')
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }

    public function updateStatusForMerchantIdAndConsentFor(string $merchantId, string $activationFormMilestone, string $status, $updatedAt, $requestId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::CONSENT_FOR, 'LIKE', $activationFormMilestone . '%')
            ->where(Entity::STATUS, '<>',  Constants::SUCCESS)
            ->update(array(
                'status' => $status,
                'updated_at' => $updatedAt,
                'request_id' => $requestId
            ));
    }

    public function updateStatusForRequestIdandConsentFor($id, $consentFor, $updatedAt, $status)
    {
        return $this->newQuery()
            ->where(Entity::REQUEST_ID, '=', $id)
            ->where(Entity::CONSENT_FOR, '=', $consentFor)
            ->update(array(
                'status' => $status,
                'updated_at' => $updatedAt
            ));
    }

    public function getUniqueMerchantIdsWithFailedConsents($lastCronJobTime)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
            ->select(Entity::MERCHANT_ID)
            ->where(Entity::STATUS, '=', Constants::FAILED)
            ->where(Entity::UPDATED_AT, '>', $lastCronJobTime)
            ->distinct()
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function getFailedConsentDetailsForMerchants($merchantId, $lastCronJobTime)
    {
        $consentDetailsIdColumn           = $this->dbColumn(Entity::DETAILS_ID);
        $detailIdColumn                   = $this->repo->merchant_consent_details->dbColumn(ConsentDetails\Entity::ID);

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
            ->join(Table::MERCHANT_CONSENT_DETAILS, $detailIdColumn, '=', $consentDetailsIdColumn)
            ->select(
                ConsentDetails\Entity::URL,
                Entity::DETAILS_ID,
                Entity::USER_ID,
                Entity::METADATA,
                Entity::CONSENT_FOR,
                Entity::AUDIT_ID)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Constants::FAILED)
            ->where(Entity::UPDATED_AT, '>', $lastCronJobTime);
    }

}
