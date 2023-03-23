<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Base\ConnectionType;
use RZP\Models\Base\PublicEntity;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'bvs_validation';

    /**
     * Returns Most recent artefact validation for owner id
     *
     * @param string $ownerId
     * @param string $artefactType
     *
     * @param string $validationUnit
     *
     * @param string $ownerType
     *
     * @return mixed
     */
    public function getLatestArtefactValidationForOwnerId(
        string $ownerId, string $artefactType, string $validationUnit, string $ownerType)
    {
        $ownerIdColumn        = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $artefactTypeColumn   = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);
        $ownerTypeColumn    = $this->repo->bvs_validation->dbColumn(Entity::OWNER_TYPE);

        $query = $this->newQuery()
                      ->where($ownerIdColumn, $ownerId)
                      ->where($artefactTypeColumn, $artefactType);

        if(empty($validationUnit) === false)
            $query = $query -> where($validationUnitColumn, $validationUnit);
        if(empty($ownerType) ===  false)
            $query = $query -> where($ownerTypeColumn, $ownerType);

        return $query->orderBy(Entity::CREATED_AT, 'desc')->first();

    }

    /**
     * Returns Most recent artefact validation for owner id and owner type
     *
     * @param string $ownerId
     * @param string $ownerType
     * @param string $artefactType
     *
     * @return mixed
     */
    public function getLatestArtefactValidationForOwnerIdAndOwnerType(string $ownerId, string $ownerType, string $artefactType)
    {
        $ownerIdColumn      = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $ownerTypeColumn    = $this->repo->bvs_validation->dbColumn(Entity::OWNER_TYPE);
        $artefactTypeColumn = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);

        return $this->newQuery()
                    ->where($ownerIdColumn, $ownerId)
                    ->where($ownerTypeColumn, $ownerType)
                    ->where($artefactTypeColumn, $artefactType)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
    }

    /**
     * @param string $ownerId
     * @param string $artefactType
     * @param string $validationUnit
     * @return mixed
     */
    public function getLatestValidationForArtefactAndValidationUnit(
        string $ownerId, string $artefactType, string $validationUnit)
    {
        $ownerIdColumn      = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $artefactTypeColumn = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);

        return $this->newQuery()
            ->where($ownerIdColumn, $ownerId)
            ->where($artefactTypeColumn, $artefactType)
            ->where($validationUnitColumn, $validationUnit)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }

    /**
     * @param string $ownerId
     * @param string $artefactType
     * @param string $validationUnit
     * @return mixed
     */
    public function getValidationsForArtefactAndValidationUnit(
        string $ownerId, string $artefactType, string $validationUnit)
    {
        $ownerIdColumn      = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $artefactTypeColumn = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);

        return $this->newQuery()
                    ->where($ownerIdColumn, $ownerId)
                    ->where($artefactTypeColumn, $artefactType)
                    ->where($validationUnitColumn, $validationUnit)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->get();
    }

    public function getValidationsOfStatus(String $status, int $startTimeStamp, int $endTimeStamp)
    {
        $validationIdColumn     = $this->dbColumn(Entity::VALIDATION_ID);
        $ownerIdColumn          = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $platformColumn         = $this->repo->bvs_validation->dbColumn(Entity::PLATFORM);
        $ownerTypeColumn        = $this->repo->bvs_validation->dbColumn(Entity::OWNER_TYPE);
        $validationStatusColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_STATUS);
        $createdAtColumn        = $this->repo->bvs_validation->dbColumn(Entity::CREATED_AT);
        $validationUnitColumn   = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);
        $artefactTypeColumn     = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);

        return $this->newQuery()
                    ->select($platformColumn,
                             $ownerTypeColumn,
                             $validationIdColumn,
                             $ownerIdColumn,
                             $validationStatusColumn,
                             $validationUnitColumn,
                             $artefactTypeColumn)
                    ->whereBetween($createdAtColumn, [$startTimeStamp, $endTimeStamp])
                    ->Where(Entity::VALIDATION_STATUS, "=", $status)
                    ->Where(Entity::PLATFORM, "=", "pg")
                    ->Where(Entity::OWNER_TYPE, "=", "merchant")
                    ->orderBy($createdAtColumn, 'desc')
                    ->get();
    }

    public function getOwnerIds(string $artefactType, string $validationUnit,int $startTimeStamp, int $endTimeStamp,array $status)
    {
        $ownerIdColumn        = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $createdAtColumn      = $this->repo->bvs_validation->dbColumn(Entity::CREATED_AT);
        $artefactTypeColumn   = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);

        return $this->newQuery()
                    ->select($ownerIdColumn)
                    ->where($artefactTypeColumn, $artefactType)
                    ->where($validationUnitColumn, $validationUnit)
                    ->whereBetween($createdAtColumn, [$startTimeStamp, $endTimeStamp])
                    ->WhereIn(Entity::VALIDATION_STATUS, $status)
                    ->Where(Entity::PLATFORM, "=", "pg")
                    ->Where(Entity::OWNER_TYPE, "=", "merchant")
                    ->distinct()
                    ->pluck(Entity::OWNER_ID)
                    ->toArray();
    }

    public function getEntitiesByMerchantIdAndState(string $merchantId, array $status)
    {
        $ownerIdColumn        = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);
        $artefactTypeColumn   = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationIdColumn   = $this->dbColumn(Entity::VALIDATION_ID);

        return $this->newQuery()
            ->select(
                $validationIdColumn,
                $ownerIdColumn,
                $validationUnitColumn,
                $artefactTypeColumn)
            ->Where($ownerIdColumn, $merchantId)
            ->WhereIn(Entity::VALIDATION_STATUS, $status)
            ->Where(Entity::PLATFORM, "=", "pg")
            ->Where(Entity::OWNER_TYPE, "=", "merchant")
            ->get();
    }

    public function getArtefactTypeFromValidationId(string $validationId)
    {
        $artefactTypeColumn   = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);

        return $this->newQuery()
            ->select($artefactTypeColumn)
            ->Where(Entity::VALIDATION_ID, $validationId)
            ->Where(Entity::PLATFORM, "=", "pg")
            ->Where(Entity::OWNER_TYPE, "=", "merchant")
            ->pluck(Entity::ARTEFACT_TYPE)
            ->toArray();
    }

    public function getFromValidationId(string $validationId)
    {
        return $this->newQuery()
                    ->Where(Entity::VALIDATION_ID, $validationId)
                    ->Where(Entity::PLATFORM, "=", "pg")
                    ->Where(Entity::OWNER_TYPE, "=", "merchant")
                    ->first();
    }

    public function getAllValidationsForMerchant(string $merchantId)
    {

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->Where(Entity::OWNER_ID, $merchantId)
                    ->Where(Entity::PLATFORM, "=", "pg")
                    ->Where(Entity::OWNER_TYPE, "=", "merchant")
                    ->get();
    }
}
