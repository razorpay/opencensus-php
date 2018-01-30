<?php

namespace RZP\Models\Dispute\File;

use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Constants\Entity as ConstantEntity;

class Entity extends Base\PublicEntity
{
    const DISPUTE_ID          = 'dispute_id';
    const FILE_ID             = 'file_id';
    const NAME                = 'name';
    const CATEGORY            = 'category';
    const CREATED_AT          = 'created_at';
    const UPDATED_AT          = 'updated_at';

    // Keys for array name of uploaded documents
    const FILES = 'upload_files';

    const FILE = 'file';

    // Upper limit for file-size in bytes
    const MAX_FILE_SIZE = 10485760;

    // Upper limit for number of files uploaded ia single request
    const MAX_NUM_FILES = 10;

    const STORAGE_PATH = 'files/dispute';

    const S3_FOLDER_PATH = 'dispute_files/';

    const S3_BUCKET_NAME = 'dispute_files_bucket';


    protected $entity = ConstantEntity::DISPUTE_FILE;

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::DISPUTE_ID,
        self::FILE_ID,
        self::NAME,
        self::CATEGORY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::DISPUTE_ID,
        self::FILE_ID,
        self::NAME,
        self::CATEGORY,
        self::CREATED_AT,
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

    public function getFileId()
    {
        return $this->getAttribute(self::FILE_ID);
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute\Entity::class);
    }
}


