<?php

namespace RZP\Models\Partner\KycAccessState;

use RZP\Models\Base;

use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const STATE = 'state';

    const ENTITY_TYPE = 'entity_type';

    const ENTITY_ID = 'entity_id';

    const PARTNER_ID = 'partner_id';

    const APPROVE_TOKEN = 'approve_token';

    const REJECT_TOKEN  = 'reject_token';

    const TOKEN_EXPIRY = 'token_expiry';

    const REJECTION_COUNT = 'rejection_count';

    const PARTNER_TYPE = 'partner_type';

    protected $entity = 'partner_kyc_access_state';

    protected static $generators = [
        self::APPROVE_TOKEN,
        self::REJECT_TOKEN,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::ENTITY_ID,
        self::ENTITY_TYPE,
    ];

    protected $defaults = [
        self::ENTITY_TYPE     => 'merchant',
        self::STATE           => State::PENDING_APPROVAL,
        self::REJECTION_COUNT => 0,
    ];

    protected $public = [
        self::ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::PARTNER_ID,
        self::TOKEN_EXPIRY,
        self::STATE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REJECTION_COUNT,
    ];

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getPartnerId()
    {
        return $this->getAttribute(self::PARTNER_ID);
    }

    public function getState()
    {
        return $this->getAttribute(self::STATE);
    }

    public function getApprovedToken()
    {
        return $this->getAttribute(self::APPROVE_TOKEN);
    }

    public function getRejectToken()
    {
        return $this->getAttribute(self::REJECT_TOKEN);
    }

    public function isApproved(): bool
    {
        return ($this->getAttribute(self::APPROVE_TOKEN) === null);
    }

    public function isRejected(): bool
    {
        return ($this->getAttribute(self::REJECT_TOKEN) === null);
    }

    public function getExpiryTime()
    {
        return $this->getAttribute(self::TOKEN_EXPIRY);
    }

    public function setExpiryTime(int $expiry)
    {
        $this->setAttribute(self::TOKEN_EXPIRY, $expiry);
    }

    public function setPartnerId($partnerId)
    {
        $this->setAttribute(self::PARTNER_ID, $partnerId);
    }

    public function setState(string $state)
    {
        $this->setAttribute(self::STATE, $state);
    }

    public function setApproveTokenNull()
    {
        $this->setAttribute(self::APPROVE_TOKEN, null);
    }

    public function setRejectTokenNull()
    {
        $this->setAttribute(self::REJECT_TOKEN, null);
    }

    public function getRejectionCount(): int
    {
        return $this->getAttribute(self::REJECTION_COUNT);
    }

    public function incrementRejectionCount()
    {
        $count = $this->getRejectionCount();

        $this->setAttribute(self::REJECTION_COUNT, ++$count);
    }

    /**
     * Generates a one time use token of the given length
     *
     * @param $length
     *
     * @return string
     * @throws \Exception
     */
    protected function generateOneTimeUseToken($length)
    {
        $bytes = random_bytes($length / 2);
        $token = bin2hex($bytes);

        return $token;
    }

    /**
     * Generates approve token
     */
    public function generateApproveToken()
    {
        $this->setAttribute(self::APPROVE_TOKEN, $this->generateOneTimeUseToken(32));
    }

    /**
     * Generates reject token
     */
    public function generateRejectToken()
    {
        $this->setAttribute(self::REJECT_TOKEN, $this->generateOneTimeUseToken(32));
    }

}
