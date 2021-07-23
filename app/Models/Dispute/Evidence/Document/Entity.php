<?php


namespace RZP\Models\Dispute\Evidence\Document;

use RZP\Constants;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const DISPUTE_ID  = 'dispute_id';
    const TYPE        = 'type';
    const CUSTOM_TYPE = 'custom_type';
    const DOCUMENT_ID = 'document_id'; // refers to the underlying ufh file id/document id
    const SOURCE      = 'source';

    const TYPE_LENGTH        = 100;
    const CUSTOM_TYPE_LENGTH = 100;
    const SOURCE_LENGTH      = 50;

    const DOCUMENT_ID_SIGN = 'doc_';


    protected $fillable = [
        self::DISPUTE_ID,
        self::DOCUMENT_ID,
        self::SOURCE,
        self::TYPE,
        self::CUSTOM_TYPE,
    ];

    protected $visible = [
        self::ID,
        self::DISPUTE_ID,
        self::DOCUMENT_ID,
        self::TYPE,
        self::CUSTOM_TYPE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $entity = Constants\Entity::DISPUTE_EVIDENCE_DOCUMENT;

    protected $generateIdOnCreate = true;

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getPublicDocumentId(): string
    {
        return self::DOCUMENT_ID_SIGN . $this->getDocumentId();
    }

    public function getDocumentId()
    {
        return $this->getAttribute(self::DOCUMENT_ID);
    }

    public function getCustomType()
    {
        return $this->getAttribute(self::CUSTOM_TYPE);
    }

    public function isOthersType(): bool
    {
        return $this->getType() === Types::OTHERS;
    }
}