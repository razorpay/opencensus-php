<?php


namespace RZP\Models\Merchant\Consent;

use RZP\Models\Base;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Entity
 *
 * @package RZP\Models\Merchant\Consent
 */
class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const USER_ID     = 'user_id';
    const MERCHANT_ID = 'merchant_id';
    const DETAILS_ID  = 'details_id';
    const METADATA    = 'metadata';
    const CREATED_AT  = 'created_at';
    const CONSENT_FOR = 'consent_for';
    const STATUS      = 'status';
    const REQUEST_ID  = 'request_id';
    const AUDIT_ID    = 'audit_id';
    const UPDATED_AT  = 'updated_at';
    const RETRY_COUNT = 'retry_count';
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID   = 'entity_id';

    protected $entity             = 'merchant_consents';

    protected $generateIdOnCreate = true;

    protected $fillable           = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::DETAILS_ID,
        self::METADATA,
        self::CONSENT_FOR,
        self::AUDIT_ID,
        self::STATUS,
        self::REQUEST_ID,
        self::RETRY_COUNT,
        self::ENTITY_ID,
        self::ENTITY_TYPE

    ];

    protected $public             = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::DETAILS_ID,
        self::METADATA,
        self::CONSENT_FOR,
        self::STATUS,
        self::REQUEST_ID,
        self::RETRY_COUNT,
        self::ENTITY_ID,
        self::ENTITY_TYPE
    ];

    protected $casts              = [
        self::METADATA => 'array',
    ];

    protected $defaults           = [
        self::METADATA   => [],
        self::DETAILS_ID => null,
        self::STATUS     => null,
        self::REQUEST_ID => null,
        self::RETRY_COUNT => 0,
        self::ENTITY_ID  => null,
        self::ENTITY_TYPE => null
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getConsentFor()
    {
        return $this->getAttribute(self::CONSENT_FOR);
    }
    public function getDetailsId()
    {
        return $this->getAttribute(self::DETAILS_ID);
    }

    public function getRetryCount()
    {
        return $this->getAttribute(self::RETRY_COUNT);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function setUserId($userId)
    {
        return $this->setAttribute(self::USER_ID, $userId);
    }

    public function setMerchantId($merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setDetailsId($detailsId)
    {
        return $this->setAttribute(self::DETAILS_ID, $detailsId);
    }

    public function setMetadata($metadata)
    {
        return $this->setAttribute(self::METADATA, $metadata);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    public function setConsentFor($consentFor)
    {
        return $this->setAttribute(self::CONSENT_FOR, $consentFor);
    }

    public function setStatus($status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function setRequestId($requestId)
    {
        return $this->setAttribute(self::REQUEST_ID, $requestId);
    }

    public function setAuditId($auditId)
    {
        return $this->setAttribute(self::AUDIT_ID, $auditId);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setAttribute(self::UPDATED_AT, $updatedAt);
    }

    public function setRetryCount($retryCount)
    {
        return $this->setAttribute(self::RETRY_COUNT, $retryCount);
    }

    public function setEntityId($entityId)
    {
        return $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setEntityType($entityType)
    {
        return $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }
}
