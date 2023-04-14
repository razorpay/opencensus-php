<?php

namespace RZP\Models\Merchant\BvsValidation;

use RZP\Models\Base\PublicEntity;

class Entity extends PublicEntity
{
    const VALIDATION_ID         = 'validation_id';
    const ARTEFACT_TYPE         = 'artefact_type';
    const PLATFORM              = 'platform';
    const OWNER_TYPE            = 'owner_type';
    const OWNER_ID              = 'owner_id';
    const VALIDATION_STATUS     = 'validation_status';
    const VALIDATION_UNIT       = 'validation_unit';
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const RULE_EXECUTION_LIST   = 'rule_execution_list';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const FUZZY_SCORE           = 'fuzzy_score';
    const METADATA              = 'metadata';

    protected $primaryKey       = self::VALIDATION_ID;

    protected $entity           = 'bvs_validation';

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
        self::RULE_EXECUTION_LIST,
        self::FUZZY_SCORE
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
        self::RULE_EXECUTION_LIST,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::FUZZY_SCORE
    ];

    protected $casts = [
        self::RULE_EXECUTION_LIST       => 'array',
    ];

    public function getValidationId(): string
    {
        return $this->getAttribute(self::VALIDATION_ID) ?? '';
    }

    public function getValidationUnit(): ?string
    {
        return $this->getAttribute(self::VALIDATION_UNIT);
    }

    public function setValidationUnit(string $validationUnit)
    {
        $this->setAttribute(self::VALIDATION_UNIT, $validationUnit);
    }

    public function setArtefactType(string $artefactType)
    {
        $this->setAttribute(self::ARTEFACT_TYPE, $artefactType);
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

    public function setValidationStatus(string $status): string
    {
        return $this->setAttribute(self::VALIDATION_STATUS,$status);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getErrorDescription()
    {
        return $this->getAttribute(self::ERROR_DESCRIPTION);
    }

    public function getRuleExecutionList()
    {
        return $this->getAttribute(self::RULE_EXECUTION_LIST);
    }

    public function getFuzzyScore()
    {
        return $this->getAttribute(self::FUZZY_SCORE);
    }

    public function setMetadata(array $metadata)
    {
        $this->setAttribute(self::METADATA, $metadata);
    }

    public function getMetadata(): array
    {
        return $this->getAttribute(self::METADATA);
    }

}
