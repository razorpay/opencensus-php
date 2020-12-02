<?php


namespace RZP\Models\Merchant\AutoKyc\Escalations;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const ESCALATION_LEVEL      = 'escalation_level';
    const ESCALATION_METHOD     = 'escalation_method';
    const ESCALATION_TYPE       = 'escalation_type';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const WORKFLOW_ID           = 'workflow_id';

    protected $entity = 'merchant_auto_kyc_escalations';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_ID,
        self::ESCALATION_LEVEL,
        self::ESCALATION_TYPE,
        self::ESCALATION_METHOD,
        self::WORKFLOW_ID
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::ESCALATION_LEVEL,
        self::ESCALATION_TYPE,
        self::ESCALATION_METHOD,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::WORKFLOW_ID
    ];

    public function getLevel()
    {
        return $this->getAttribute(self::ESCALATION_LEVEL);
    }
}
