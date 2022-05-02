<?php


namespace RZP\Models\Base\Audit;

use RZP\Models\Base;

/**
 * Class Entity
 */
class Entity extends Base\PublicEntity
{
    const ID_LENGTH = 14;

    const ID         = 'id';
    const META       = 'meta';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $entity = 'audit_info';


    protected $fillable           = [
        self::ID,
        self::META,
    ];

    protected $public             = [
        self::ID,
        self::META,
        self::CREATED_AT,
    ];

    protected $casts              = [
        self::META => 'json',
    ];

    protected $primaryKey         = self::ID;

    protected $generateIdOnCreate = true;

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getMeta()
    {
        return $this->getAttribute(self::META);
    }

    public function setMeta($meta)
    {
        return $this->setAttribute(self::META, $meta);
    }
}
