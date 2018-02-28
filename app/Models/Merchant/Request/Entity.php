<?php

namespace RZP\Models\Merchant\Request;

use RZP\Models\Base;
use RZP\Models\State;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID       = 'merchant_id';
    const NAME              = 'name';
    const TYPE              = 'type';
    const STATUS            = 'status';
    const PUBLIC_MESSAGE    = 'public_message';
    const REJECTION_REASONS = 'rejection_reasons';
    const COMMENT           = 'comment';
    const MERCHANT          = 'merchant';
    const SUBMISSIONS       = 'submissions';
    const QUESTIONS         = 'questions';

    protected $entity = 'merchant_request';

    protected static $sign = 'm_req';

    protected $primaryKey = self::ID;

    protected $fillable = [
        self::MERCHANT_ID,
        self::NAME,
        self::TYPE,
        self::COMMENT,
        self::PUBLIC_MESSAGE,
        self::STATUS,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::NAME,
        self::MERCHANT,
        self::COMMENT,
        self::TYPE,
        self::STATUS,
        self::PUBLIC_MESSAGE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::MERCHANT_ID,
        self::COMMENT,
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

    public function setPublicMerchantIdAttribute(array &$attributes)
    {
        $merchantId = $this->getAttribute(self::MERCHANT_ID);

        if ($merchantId !== null)
        {
            $attributes[self::MERCHANT_ID] = Merchant\Entity::getSignedId($merchantId);
        }
    }

    public function setPublicCommentAttribute(array &$attributes)
    {
        $app = \App::getFacadeRoot();

        if ($app['basicauth']->isAdminAuth() !== true)
        {
            unset($attributes[Entity::COMMENT]);
        }
    }
}
