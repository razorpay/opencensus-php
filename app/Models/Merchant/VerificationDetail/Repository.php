<?php


namespace RZP\Models\Merchant\VerificationDetail;

use RZP\Models\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_verification_detail';

    public function getDetailsForTypeAndIdentifier(string $mid, string $artefactType, string $artefactIdentifier)
    {
        return $this->newQuery()
            ->where(Entity::ARTEFACT_TYPE, '=', $artefactType)
            ->where(Entity::ARTEFACT_IDENTIFIER, '=', $artefactIdentifier)
            ->where(Entity::MERCHANT_ID, '=', $mid)
            ->first();
    }

    public function getDetailsForTypeAndIdentifierFromReplica(string $mid, string $artefactType, string $artefactIdentifier): ?Entity
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
            ->where(Entity::ARTEFACT_TYPE, '=', $artefactType)
            ->where(Entity::ARTEFACT_IDENTIFIER, '=', $artefactIdentifier)
            ->where(Entity::MERCHANT_ID, '=', $mid)
            ->first();
    }

    public function getDetailsForMerchant(string $mid)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, '=', $mid)
                    ->get();
    }
}
