<?php

namespace RZP\Models\FileStore;

use Crypt;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const TYPE                  = 'TYPE';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const COMMENTS              = 'comments';
    const FORMAT                = 'format';
    const SIZE                  = 'size';
    const NAME                  = 'name';
    const SERVICE               = 'service';
    const LOCATION              = 'location';
    const BUCKET                = 'bucket';
    const PERMISSION            = 'permission';
    const ENCRYPTION_METHOD     = 'encryption_method';
    const PASSWORD              = 'password';
    const METADATA              = 'metadata';
    const DELETED_AT            = 'deleted_at';

    protected $entity           = 'file';

    protected static $sign      = 'file';

    protected $table  = \RZP\Constants\Table::FILE;

    protected $generateIdOnCreate = true;

    protected $public = [
        self::ID,
        self::NAME,
        self::COMMENTS,
        self::TYPE,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::FORMAT,
        self::SIZE,
        self::ENCRYPTION_METHOD,
        self::LOCATION,
        self::SERVICE,
        self::PASSWORD,
        self::BUCKET,
        self::NAME,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::MERCHANT_ID,
        self::PERMISSION,
        self::METADATA,
        self::COMMENTS,
        self::TYPE
    ];

    protected $visible = [
        self::ID,
        self::FORMAT,
        self::SIZE,
        self::ENCRYPTION_METHOD,
        self::LOCATION,
        self::SERVICE,
        self::BUCKET,
        self::NAME,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::MERCHANT_ID,
        self::PERMISSION,
        self::METADATA,
        self::COMMENTS,
        self::TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $hidden = [
        self::PASSWORD
    ];

    protected $defaults = [];

    protected function getPasswordAttribute()
    {
        $pwd = $this->attributes[self::PASSWORD];

        if ($pwd !== null)
        {
            $pwd = Crypt::decrypt($pwd);
        }

        return $pwd;
    }

    protected function setPasswordAttribute($password)
    {
        if ($password !== null)
        {
            $password = Crypt::encrypt($password);
        }

        $this->attributes[self::PASSWORD] = $password;
    }

    public function entityAssociate($entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->source()->associate($entity);
    }

    public function setFormat($format)
    {
        $this->setAttribute(self::FORMAT, $format);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getFormat()
    {
        return $this->getAttribute(self::FORMAT);
    }
}
