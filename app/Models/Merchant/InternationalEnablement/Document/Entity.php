<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Models\Base;
use RZP\Models\GenericDocument;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID  = 'merchant_id';
    const DOCUMENT_ID  = 'document_id';
    const TYPE         = 'type';
    const CUSTOM_TYPE  = 'custom_type';
    const DISPLAY_NAME = 'display_name';

    const INTERNATIONAL_ENABLEMENT_DETAIL_ID  = 'international_enablement_detail_id';       

    const TYPE_FIELD_MAX_LENGTH   = 50;
    const DISPLAY_NAME_MAX_LENGTH = 100;

    protected $entity = 'international_enablement_document';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DOCUMENT_ID,
        self::TYPE,
        self::CUSTOM_TYPE,
        self::DISPLAY_NAME,
    ];

    protected $visible = [
        self::ID,
        self::DOCUMENT_ID,
        self::TYPE,
        self::CUSTOM_TYPE,
        self::DISPLAY_NAME,
        self::MERCHANT_ID,
        self::INTERNATIONAL_ENABLEMENT_DETAIL_ID,
        self::CREATED_AT
    ];

    protected $public = [
        self::DOCUMENT_ID,
        self::TYPE,
        self::CUSTOM_TYPE,
        self::DISPLAY_NAME,
    ];

    protected $publicSetters = [
        self::DOCUMENT_ID,
    ];

    protected $defaults = [
        self::CUSTOM_TYPE  => null,
    ];

    public function setMerchantId(string $merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setInternationalEnablementDetailId(string $ieDetailId)
    {
        $this->setAttribute(self::INTERNATIONAL_ENABLEMENT_DETAIL_ID, $ieDetailId);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function enablement()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\InternationalEnablement\Detail\Entity',
            Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID);
    }

    public function getDocumentId()
    {
        return $this->getAttribute(self::DOCUMENT_ID);
    }

    public function getPublicDocumentId()
    {
        return GenericDocument\Constants::DOCUMENT_ID_SIGN . $this->getDocumentId();
    }

    public function setPublicDocumentIdAttribute(array & $attributes)
    {
        $attributes[self::DOCUMENT_ID] = GenericDocument\Constants::DOCUMENT_ID_SIGN . $this->getDocumentId();
    }

    protected function setDocumentIdAttribute($documentId)
    {
        $this->attributes[self::DOCUMENT_ID] = Entity::stripDefaultSign($documentId);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getCustomType()
    {
        return $this->getAttribute(self::CUSTOM_TYPE);
    }

    public function getDisplayName()
    {
        return $this->getAttribute(self::DISPLAY_NAME);
    }

    public function isCustomType(): bool
    {
        return Constants::OTHERS === $this->getType();
    }
}
