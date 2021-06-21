<?php


namespace RZP\Models\Merchant\Escalations\Actions;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const ESCALATION_ID         = 'escalation_id';
    const ACTION_HANDLER        = 'action_handler';
    const STATUS                = 'status';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';


    protected $entity = 'onboarding_escalation_actions';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ESCALATION_ID,
        self::ACTION_HANDLER,
        self::STATUS
    ];

    protected $public = [
        self::ID,
        self::ESCALATION_ID,
        self::ACTION_HANDLER,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    public function getHandler()
    {
        return $this->getAttribute(self::ACTION_HANDLER);
    }

    public function getEscalationId()
    {
        return $this->getAttribute(self::ESCALATION_ID);
    }
}
