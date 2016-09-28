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

    protected $public = [];

    protected $fillable = [];

    protected $visible = [];

    protected $hidden = array(
        self::PASSWORD);

    protected $defaults = [];
}
