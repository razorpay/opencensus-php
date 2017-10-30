<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Constants\Entity as ConstantEntity;

class Entity extends Base\PublicEntity
{
    const ID                  = 'id';
    const DISPUTE_ID          = 'dispute_id';
    const URL                 = 'url';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';

    protected $entity = ConstantEntity::DISPUTE_FILE;

    // Keys for array name of uploaded documents
    const FILES = 'upload_files';

    const FILE = 'file';

    // Upper limit for file-size in bytes
    const MAX_FILE_SIZE = 10485760;

    // Upper limit for number of files uploaded ia single request
    const MAX_NUM_FILES = 10;

    const STORAGE_PATH = 'files/dispute';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::DISPUTE_ID,
        self::URL,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::DISPUTE_ID,
        self::URL,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $guarded = [self::ID];

    public function getDisputeId()
    {
        return $this->getAttribute(self::DISPUTE_ID);
    }

    public function getUrl()
    {
        return $this->getAttribute(self::URL);
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute\Entity::class);
    }
}


