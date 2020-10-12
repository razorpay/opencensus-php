<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const VALIDATION_ID     = 'validation_id';
    const ARTEFACT_TYPE     = 'artefact_type';
    const PLATFORM          = 'platform';
    const OWNER_TYPE        = 'owner_type';
    const OWNER_ID          = 'owner_id';
    const VALIDATION_STATUS = 'validation_status';
    const VALIDATION_UNIT   = 'validation_unit';
    const ERROR_CODE        = 'error_code';
    const ERROR_DESCRIPTION = 'error_description';

    protected $primaryKey = self::VALIDATION_ID;

    protected $entity = 'bvs_validation';

    protected $fillable = [
        self::VALIDATION_ID,
        self::ARTEFACT_TYPE,
        self::PLATFORM,
        self::OWNER_TYPE,
        self::OWNER_ID,
        self::VALIDATION_STATUS,
        self::VALIDATION_UNIT,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];

    protected $public = [
        self::VALIDATION_ID,
        self::ARTEFACT_TYPE,
        self::PLATFORM,
        self::OWNER_TYPE,
        self::OWNER_ID,
        self::VALIDATION_STATUS,
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getValidationId(): string
    {
        return $this->getAttribute(self::VALIDATION_ID);
    }

    public function getArtefactType(): string
    {
        return $this->getAttribute(self::ARTEFACT_TYPE);
    }

    public function getPlatform(): string
    {
        return $this->getAttribute(self::PLATFORM);
    }

    public function getOwnerId(): string
    {
        return $this->getAttribute(self::OWNER_ID);
    }

    public function getOwnerType(): string
    {
        return $this->getAttribute(self::OWNER_TYPE);
    }

    public function getValidationStatus(): string
    {
        return $this->getAttribute(self::VALIDATION_STATUS);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }
}
