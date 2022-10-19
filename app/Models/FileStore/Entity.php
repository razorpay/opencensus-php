<?php

namespace RZP\Models\FileStore;

use Carbon\Carbon;
use Crypt;
use RZP\Constants;
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
    const REGION                = 'region';
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
        self::BUCKET,
        self::REGION,
    ];

    protected $fillable = [
        self::TYPE,
        self::COMMENTS,
        self::EXTENSION,
        self::MIME,
        self::SIZE,
        self::NAME,
        self::STORE,
        self::LOCATION,
        self::BUCKET,
        self::REGION,
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
        self::REGION,
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
        self::REGION            => null,
        self::PERMISSION        => null,
        self::ENCRYPTION_METHOD => null,
        self::PASSWORD          => null,
        self::EXTENSION         => null,
        self::METADATA          => [],
    ];

    protected $ignoredRelations = [
        'entity',
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

    public function setPassword($password)
    {
        $this->setAttribute(self::PASSWORD, $password);
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

    public function setRegion($region)
    {
        $this->setAttribute(self::REGION, $region);
    }

    public function setBucket($bucket)
    {
        $this->setAttribute(self::BUCKET, $bucket);
    }

    public function setMime($mime)
    {
        $this->setAttribute(self::MIME, $mime);
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

    public function setMetadata($metadata)
    {
        $this->setAttribute(self::METADATA, $metadata);
    }

    // ----------------------- Setters Ends----------------------------------------

    // ----------------------- Relations -----------------------------------------

    public function entity()
    {
        return $this->morphTo();
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

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getMetadata()
    {
        return $this->getAttribute(self::METADATA);
    }

    public function getBucket()
    {
        return $this->getAttribute(self::BUCKET);
    }

    public function getRegion()
    {
        return $this->getAttribute(self::REGION);
    }

    public function getLocation()
    {
        return $this->getAttribute(self::LOCATION);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getSize()
    {
        return $this->getAttribute(self::SIZE);
    }

    public function getComments()
    {
        return $this->getAttribute(self::COMMENTS);
    }

    /**
     * Returns local full file path
     *
     * @return string
     */
    public function getFullFilePath(): string
    {
        $dir = storage_path(Store::STORAGE_DIRECTORY);

        return $dir . $this->getName() . '.' . $this->getExtension();
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

    protected function getMetadataAttribute()
    {
        $metadata = $this->attributes[self::METADATA];

        if ($metadata === null)
        {
            $metadata = [];
        }
        else
        {
            $metadata = json_decode($metadata, true);
        }

        return $metadata;
    }

    protected function setMetadataAttribute(array $metadata = [])
    {
        $this->attributes[self::METADATA] = json_encode($metadata);
    }

    public function setDeletedAt()
    {
        $this->attributes[self::DELETED_AT] = Carbon::now()->timestamp;
    }

    public function setComments(string $comments)
    {
        $this->setAttribute(self::COMMENTS, $comments);
    }

}
