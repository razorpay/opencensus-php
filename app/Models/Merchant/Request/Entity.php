<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const NAME                             = 'name';
    const TYPE                             = 'type';
    const STATES                           = 'states';
    const STATUS                           = 'status';
    const MERCHANT                         = 'merchant';
    const MERCHANT_ID                      = 'merchant_id';
    const PUBLIC_MESSAGE                   = 'public_message';
    const INTERNAL_COMMENT                 = 'internal_comment';
    const ALLOWED_NEXT_ACTIVATION_STATUSES = 'allowed_next_activation_statuses';

    protected $entity = 'merchant_request';

    protected static $sign = 'm_req';

    protected $primaryKey = self::ID;

    protected $fillable = [
        self::NAME,
        self::TYPE,
        self::STATUS,
        self::INTERNAL_COMMENT,
        self::PUBLIC_MESSAGE,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::TYPE,
        self::STATES,
        self::STATUS,
        self::MERCHANT,
        self::CREATED_AT,
        self::MERCHANT_ID,
        self::PUBLIC_MESSAGE,
        self::INTERNAL_COMMENT,
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
    ];

    protected $publicSetters = [
        self::ID,
        self::MERCHANT_ID,
        self::INTERNAL_COMMENT,
        self::ALLOWED_NEXT_ACTIVATION_STATUSES,
    ];

    protected $defaults = [
        self::TYPE   => Type::INTERNAL,
        self::STATUS => Status::UNDER_REVIEW
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function states()
    {
        return $this->morphMany(State\Entity::class, 'entity');
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getPublicMessage()
    {
        return $this->getAttribute(self::PUBLIC_MESSAGE);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function isProductRequest()
    {
        return ($this->getAttribute(self::TYPE) === Type::PRODUCT);
    }

    public function isPartnerRequest(): bool
    {
        return ($this->getAttribute(self::TYPE) === Type::PARTNER);
    }

    public function isPartnerActivationRequest(): bool
    {
        return (($this->isPartnerRequest() === true) and
            ($this->getAttribute(self::NAME) === Constants::ACTIVATION));
    }

    public function isPartnerDeactivationRequest(): bool
    {
        return (($this->isPartnerRequest() === true) and
            ($this->getAttribute(self::NAME) === Constants::DEACTIVATION));
    }

    public function setPublicMerchantIdAttribute(array &$attributes)
    {
        $merchantId = $this->getAttribute(self::MERCHANT_ID);

        if ($merchantId !== null)
        {
            $attributes[self::MERCHANT_ID] = Merchant\Entity::getSignedId($merchantId);
        }
    }

    public function setPublicInternalCommentAttribute(array &$attributes)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() !== true)
        {
            unset($attributes[Entity::INTERNAL_COMMENT]);
        }
    }

    protected function setPublicAllowedNextActivationStatusesAttribute(array & $array)
    {
        $activationStatus = $this->getStatus();

        $allowedNextActivationStatuses = [];

        if (empty($activationStatus) === false)
        {
            $allowedNextActivationStatuses = Status::ALLOWED_NEXT_ACTIVATION_STATUSES_MAPPING[$activationStatus];
        }

        $array[self::ALLOWED_NEXT_ACTIVATION_STATUSES] = $allowedNextActivationStatuses;
    }

}
