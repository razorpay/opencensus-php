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
        self::REQUEST_ID

    ];

    protected $public             = [
        self::MERCHANT_ID,
        self::USER_ID,
        self::DETAILS_ID,
        self::METADATA,
        self::CONSENT_FOR,
        self::STATUS,
        self::REQUEST_ID
    ];

    protected $casts              = [
        self::METADATA => 'array',
    ];

    protected $defaults           = [
        self::METADATA   => [],
        self::DETAILS_ID => null,
        self::STATUS     => null,
        self::REQUEST_ID => null
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }
    public function getConsentFor()
    {
        return $this->getAttribute(self::CONSENT_FOR);
    }
}
