<?php

namespace RZP\Models\Merchant\Merchant1ccComments;

use RZP\Models\Base;
use RZP\Models\Merchant;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID            = 'id';
    const MERCHANT_ID   = 'merchant_id';
    const FLOW          = 'flow';
    const COMMENT       = 'comment';


    protected $entity = 'merchant_1cc_comments';

    protected $generateIdOnCreate = true;

    protected static $generators = [
        self::ID,
    ];

    // Added this to prevent errors on absence of updated_at field
    const UPDATED_AT = null;
    
    protected $fillable = [
        self::MERCHANT_ID,
        self::FLOW,
        self::COMMENT,
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::FLOW,
        self::COMMENT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::DELETED_AT,
    ];


    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant\Entity::class, Merchant\Entity::ID, self::MERCHANT_ID);
    }

    public function getFlow()
    {
        return $this->getAttributeValue(self::FLOW);
    }

    public function getComment()
    {
        return $this->getAttributeValue(self::COMMENT);
    }

    public function setFlow(string $value)
    {
        $this->setAttribute(self::FLOW, $value);
    }

    public function setComment(string $value)
    {
        $this->setAttribute(self::COMMENT, $value);
    }
}
