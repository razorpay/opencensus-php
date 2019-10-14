<?php
namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    const FILE_STORE_ID                     = 'file_store_id';
    const DOCUMENT_TYPE                     = 'document_type';
    const ENTITY_TYPE                       = 'entity_type';
    const FILE                              = 'file';
    const SIGNED_URL                        = 'signed_url';
    const OCR_VERIFY                        = 'ocr_verify';

    protected static $sign = 'doc';

    protected $entity = "merchant_document";

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::FILE_STORE_ID,
        self::MERCHANT_ID,
        self::DOCUMENT_TYPE,
        self::ENTITY_TYPE,
    ];

    protected $public = [
        self::FILE_STORE_ID,
        self::MERCHANT_ID,
        self::DOCUMENT_TYPE,
        self::OCR_VERIFY,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    public function getFileStoreId()
    {
        return $this->getAttribute(self::FILE_STORE_ID);
    }

    public function getDocumentType()
    {
        return $this->getAttribute(self::DOCUMENT_TYPE);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function setEntityType(string $entityType = 'merchant')
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setMerchantId(string $merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID,$merchantId);
    }

    public function setFileStoreId(string $fileStoreID)
    {
        return $this->setAttribute(self::FILE_STORE_ID,$fileStoreID);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function fileStore()
    {
        return $this->belongsTo(FileStore\Entity::class);
    }
}
