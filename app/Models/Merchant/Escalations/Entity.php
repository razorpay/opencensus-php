<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const ESCALATED_TO          = 'escalated_to';
    const TYPE                  = 'type';
    const MILESTONE             = 'milestone';
    const AMOUNT                = 'amount';
    const THRESHOLD             = 'threshold';
    const DESCRIPTION           = 'description';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';

    protected $entity = 'merchant_onboarding_escalations';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::ESCALATED_TO,
        self::TYPE,
        self::MILESTONE,
        self::AMOUNT,
        self::THRESHOLD,
        self::DESCRIPTION,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ESCALATED_TO,
        self::TYPE,
        self::MILESTONE,
        self::AMOUNT,
        self::THRESHOLD,
        self::DESCRIPTION,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    public function getThresholdInRupee()
    {
        return $this->getAttribute(self::THRESHOLD) / 100;
    }
}
