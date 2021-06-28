<?php


namespace RZP\Models\Reward\MerchantReward;

use RZP\Models\Base;


class Entity extends Base\PublicEntity
{
    const MERCHANT_ID         = 'merchant_id';
    const STATUS              = 'status';
    const REWARD_ID           = 'reward_id';
    const ACTIVATED_AT        = 'activated_at';
    const ACCEPTED_AT         = 'accepted_at';
    const DEACTIVATED_AT      = 'deactivated_at';

    //status constants
    const AVAILABLE           = 'available';
    const QUEUE               = 'queue';
    const LIVE                = 'live';
    const EXPIRED             = 'expired';
    const DELETED             = 'deleted';

    protected $public = [
        self::MERCHANT_ID,
        self::REWARD_ID,
        self::STATUS,
        self::ACTIVATED_AT,
        self::ACCEPTED_AT,
        self::DEACTIVATED_AT
    ];

    protected $entity         = 'merchant_reward';

    //maximum live reward allowed
    const MAX_LIVE_REWARD_ALLOWED = 3;

    protected $dates = [
        self::ACTIVATED_AT,
        self::ACCEPTED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DEACTIVATED_AT
    ];

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setActivatedAt($activatedAt)
    {
        $this->setAttribute(self::ACTIVATED_AT, $activatedAt);
    }

    public function setDeactivatedAt($deactivatedAt)
    {
        $this->setAttribute(self::DEACTIVATED_AT, $deactivatedAt);
    }

    public function setAcceptedAt($acceptedAt)
    {
        $this->setAttribute(self::ACCEPTED_AT, $acceptedAt);
    }

    public function setRewardId($rewardId)
    {
        $this->setAttribute(self::REWARD_ID, $rewardId);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getActivatedAt()
    {
        return $this->getAttribute(self::ACTIVATED_AT);
    }

    public function getDeactivatedAt()
    {
        return $this->getAttribute(self::DEACTIVATED_AT);
    }

    public function getAcceptedAt()
    {
        return $this->getAttribute(self::ACCEPTED_AT);
    }

    public function getRewardId()
    {
        return $this->getAttribute(self::REWARD_ID);
    }
}
