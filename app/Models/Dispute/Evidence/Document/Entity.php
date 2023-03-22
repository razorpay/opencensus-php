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
    const DOCUMENT_TYPE = 'document_type';

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
        self::DELETED_AT,
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

    public function toArray(): array
    {
        $data = parent::toArray();

        if ($data[self::DELETED_AT] === null)
        {
            unset($data[self::DELETED_AT]);
        }
        else
        {
            $data[self::DELETED_AT] = [
                "Int64" => strtotime($data[self::DELETED_AT]),
                "Valid" => true,
            ];
            $data[self::DOCUMENT_TYPE] = $data[self::TYPE];
        }

        return $data;
    }

    public function toArrayAdmin(): array
    {
        $data = parent::toArrayAdmin();
        unset($data[self::DELETED_AT]);
        return $data;
    }

    public function toDualWriteArray() : array
    {
        $array = $this->toArray();

        $array[self::DOCUMENT_TYPE] = $array[self::TYPE];
        unset($array[self::TYPE]);

        return $array;
    }
}
