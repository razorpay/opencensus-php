<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base;

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
     * @return mixed
     */
    public function getLatestArtefactValidationForOwnerId(
        string $ownerId, string $artefactType, string $validationUnit, string $ownerType)
    {
        $ownerIdColumn        = $this->repo->bvs_validation->dbColumn(Entity::OWNER_ID);
        $artefactTypeColumn   = $this->repo->bvs_validation->dbColumn(Entity::ARTEFACT_TYPE);
        $validationUnitColumn = $this->repo->bvs_validation->dbColumn(Entity::VALIDATION_UNIT);
        $ownerTypeColumn    = $this->repo->bvs_validation->dbColumn(Entity::OWNER_TYPE);

        return $this->newQuery()
                    ->where($ownerIdColumn, $ownerId)
                    ->where($artefactTypeColumn, $artefactType)
                    ->where($validationUnitColumn, $validationUnit)
                    ->where($ownerTypeColumn, $ownerType)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();
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
}
