<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const FORMAT                = 'format';
    const SIZE                  = 'size';
    const ENCRYPTION_METHOD     = 'encryption_method';
    const LOCATION              = 'location';
    const SERVICE               = 'service';
    const BUCKET                = 'bucket';
    const NAME                  = 'name';
    const PASSWORD              = 'password';
    const ENTITY_NAME           = 'entity_name';
    const ENTITY_ID             = 'entity_id';
    const MERCHANT_ID           = 'merchant_id';
    const PERMISSION            = 'permission';
    const METADATA              = 'metadata';
    const COMMENTS              = 'comments';
    const DOCUMENT_TYPE         = 'document_type';
    const DELETED_AT            = 'deleted_at';

    protected $entity           = 'file_handler';

    protected static $sign      = 'file';

    protected $table  = \RZP\Constants\Table::FILE_HANDLER;

    protected $generateIdOnCreate = true;

    protected $public = [
        self::ID,
        self::FORMAT,
        self::SIZE,
        self::ENCRYPTION_METHOD,
        self::LOCATION,
        self::SERVICE,
        self::NAME,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MERCHANT_ID,
        self::PERMISSION,
        self::METADATA,
        self::COMMENTS,
        self::DOCUMENT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $fillable = [
        self::FORMAT,
        self::SIZE,
        self::ENCRYPTION_METHOD,
        self::LOCATION,
        self::SERVICE,
        self::NAME,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MERCHANT_ID,
        self::PERMISSION,
        self::METADATA,
        self::COMMENTS,
        self::DOCUMENT_TYPE
    ];

    protected $visible = [
        self::ID,
        self::FORMAT,
        self::SIZE,
        self::ENCRYPTION_METHOD,
        self::LOCATION,
        self::SERVICE,
        self::NAME,
        self::ENTITY_NAME,
        self::ENTITY_ID,
        self::MERCHANT_ID,
        self::PERMISSION,
        self::METADATA,
        self::COMMENTS,
        self::DOCUMENT_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $hidden = [
        self::PASSWORD
    ];

    protected $defaults = [];
}
