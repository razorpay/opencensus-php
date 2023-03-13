<?php
namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;
use RZP\Models\Merchant\BvsValidation;
use Illuminate\Database\Eloquent\SoftDeletes;
use MVanDuijker\TransactionalModelEvents as TransactionalModelEvents;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use TransactionalModelEvents\TransactionalAwareEvents;

    const FILE_STORE_ID      = 'file_store_id';
    const DOCUMENT_TYPE      = 'document_type';
    const ENTITY_TYPE        = 'entity_type';
    const SOURCE             = 'source';
    const FILE               = 'file';
    const SIGNED_URL         = 'signed_url';
    const OCR_VERIFY         = 'ocr_verify';
    const VALIDATION_ID      = 'validation_id';
    const ENTITY_ID          = 'entity_id';
    const UPLOAD_BY_ADMIN_ID = 'upload_by_admin_id';
    const AUDIT_ID           = 'audit_id';
    //When the document is accounted for
    const DOCUMENT_DATE      = 'document_date';
    const METADATA           = 'metadata';

    protected static $sign = 'doc';

    protected $entity = "merchant_document";

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::FILE_STORE_ID,
        self::MERCHANT_ID,
        self::DOCUMENT_TYPE,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::SOURCE,
        self::UPLOAD_BY_ADMIN_ID,
        self::DOCUMENT_DATE,
        self::AUDIT_ID,
        self::METADATA,
        self::DELETED_AT,
    ];

    protected $public = [
        self::FILE_STORE_ID,
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::DOCUMENT_TYPE,
        self::OCR_VERIFY,
        self::SOURCE,
        self::UPLOAD_BY_ADMIN_ID,
        self::DOCUMENT_DATE,
        self::METADATA
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
        self::DOCUMENT_DATE,
    ];

    protected $casts              = [
        self::METADATA => 'array',
    ];

    protected $defaults           = [
        self::METADATA   => []
    ];

    public function getFileStoreSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function setFileStoreSource(string $source)
    {
        return $this->setAttribute(self::SOURCE, $source);
    }

    public function getFileStoreId()
    {
        return $this->getAttribute(self::FILE_STORE_ID);
    }

    public function getPublicFileStoreId()
    {
        return 'file_'.$this->getAttribute(self::FILE_STORE_ID);
    }

    public function getDocumentType()
    {
        return $this->getAttribute(self::DOCUMENT_TYPE);
    }

    public function getDocumentDate()
    {
        return $this->getAttribute(self::DOCUMENT_DATE);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getOcrVerify()
    {
        return $this->getAttribute(self::OCR_VERIFY);
    }

    public function getUploadByAdminId()
    {
        return $this->getAttribute(self::UPLOAD_BY_ADMIN_ID);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function setOcrVerify(string $ocrVerify)
    {
        $this->setAttribute(self::OCR_VERIFY, $ocrVerify);
    }

    public function setEntityType(string $entityType = 'merchant')
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setUploadByAdminId(string $adminId)
    {
        return $this->setAttribute(self::UPLOAD_BY_ADMIN_ID, $adminId);
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

    public function entity()
    {
        return $this->morphTo(self::ENTITY);
    }

    public function fileStore()
    {
        return $this->belongsTo(FileStore\Entity::class);
    }

    public function setValidationId(string $validationId)
    {
        return $this->setAttribute(self::VALIDATION_ID, $validationId);
    }

    public function getValidationId(): ?string
    {
        return $this->getAttribute(self::VALIDATION_ID);
    }

    public function bvsValidation()
    {
        return $this->belongsTo(BvsValidation\Entity::class, self::VALIDATION_ID);
    }

    public function getMetadata()
    {
        return $this->getAttribute(self::METADATA);
    }
}
