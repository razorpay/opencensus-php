<?php

namespace RZP\Models\FileStore;

use Crypt;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const TYPE                  = 'type';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const COMMENTS              = 'comments';
    const EXTENSION             = 'extension';
    const MIME                  = 'mime';
    const SIZE                  = 'size';
    const NAME                  = 'name';
    const STORE                 = 'store';
    const LOCATION              = 'location';
    const BUCKET                = 'bucket';
    const PERMISSION            = 'permission';
    const ENCRYPTION_METHOD     = 'encryption_method';
    const PASSWORD              = 'password';
    const METADATA              = 'metadata';
    const DELETED_AT            = 'deleted_at';

    protected $entity           = 'file_store';

    protected static $sign      = 'file';

    protected $generateIdOnCreate = true;

    protected $public = [
        self::ID,
        self::TYPE,
        self::COMMENTS,
        self::NAME,
        self::LOCATION,
        self::CREATED_AT,
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::TYPE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::COMMENTS,
        self::EXTENSION,
        self::MIME,
        self::SIZE,
        self::NAME,
        self::STORE,
        self::LOCATION,
        self::BUCKET,
        self::PERMISSION,
        self::ENCRYPTION_METHOD,
        self::PASSWORD,
        self::METADATA,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TYPE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::COMMENTS,
        self::EXTENSION,
        self::MIME,
        self::SIZE,
        self::NAME,
        self::STORE,
        self::LOCATION,
        self::BUCKET,
        self::PERMISSION,
        self::ENCRYPTION_METHOD,
        self::METADATA,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $hidden = [
        self::PASSWORD
    ];

    protected $defaults = [
        self::ENTITY_ID         => null,
        self::ENTITY_TYPE       => null,
        self::COMMENTS          => null,
        self::MIME              => null,
        self::BUCKET            => null,
        self::PERMISSION        => null,
        self::ENCRYPTION_METHOD => null,
        self::PASSWORD          => null,
        self::METADATA          => null,
    ];

    // ----------------------- Mutators -------------------------------------------

    protected function setPasswordAttribute($password)
    {
        if ($password !== null)
        {
            $password = Crypt::encrypt($password);
        }

        $this->attributes[self::PASSWORD] = $password;
    }

    // ----------------------- Mutators Ends --------------------------------------


    // ----------------------- Setters --------------------------------------------

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setType($type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setStore(string $store)
    {
        $this->setAttribute(self::STORE, $store);
    }

    public function setExtension($extension)
    {
        $this->setAttribute(self::EXTENSION, $extension);
    }

    public function setMime($mime)
    {
        return $this->setAttribute(self::MIME, $mime);
    }

    public function setMerchantId($merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setSize(int $size)
    {
        $this->setAttribute(self::SIZE, $size);
    }

    public function setLocation($location)
    {
        $this->setAttribute(self::LOCATION, $location);
    }

    // ----------------------- Setters Ends----------------------------------------

    // ----------------------- Relations -----------------------------------------

    public function entityAssociate($entity)
    {
        $this->setEntityType($entity->getEntityName());

        $this->source()->associate($entity);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------- Relations Ends -------------------------------------

    // ----------------------- Getters --------------------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getId()
    {
        return $this->getAttribute(self::ID);
    }

    public function getStore()
    {
        return $this->getAttribute(self::STORE);
    }

    public function getExtension()
    {
        return $this->getAttribute(self::EXTENSION);
    }

    public function getMime()
    {
        return $this->getAttribute(self::MIME);
    }

    // ----------------------- Getters Ends----------------------------------------

    protected function getPasswordAttribute()
    {
        $password = $this->attributes[self::PASSWORD];

        if ($password !== null)
        {
            $password = Crypt::decrypt($password);
        }

        return $password;
    }
}
