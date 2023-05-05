<?php


namespace RZP\Models\Merchant\VerificationDetail;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail;

/**
 * Class Entity
 *
 * @property Merchant\Entity $merchant
 * @property Detail\Entity $merchantDetail
 *
 * @package RZP\Models\Merchant\VerificationDetail
 */
class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const ARTEFACT_TYPE         = 'artefact_type';
    const ARTEFACT_IDENTIFIER   = 'artefact_identifier';
    const STATUS                = 'status';
    const AUDIT_ID              = 'audit_id';
    const METADATA              = 'metadata';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $entity           = 'merchant_verification_detail';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::ARTEFACT_TYPE,
        self::ARTEFACT_IDENTIFIER,
        self::STATUS,
        self::METADATA,
        self::AUDIT_ID,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ARTEFACT_TYPE,
        self::ARTEFACT_IDENTIFIER,
        self::STATUS,
        self::METADATA,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $defaults = [
        self::METADATA => []
    ];

    protected $casts = [
        self::METADATA       => 'array',
    ];

    public function merchantDetail()
    {
        return $this->belongsTo('RZP\Models\Merchant\Detail\Entity', self::MERCHANT_ID, self::MERCHANT_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getMetadata()
    {
        return $this->getAttribute(self::METADATA);
    }

    public function getArtefactType()
    {
        return $this->getAttribute(self::ARTEFACT_TYPE);
    }

    public function getArtefactIdentifier()
    {
        return $this->getAttribute(self::ARTEFACT_IDENTIFIER);
    }

    public function setMetadata(array $metadata = [])
    {
        return $this->setAttribute(self::METADATA, $metadata);
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }
}
