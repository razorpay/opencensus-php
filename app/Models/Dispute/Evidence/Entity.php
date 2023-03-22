<?php


namespace RZP\Models\Dispute\Evidence;


use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Constants as RZPConstants;
use RZP\Models\Dispute\Evidence\Document;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const DISPUTE_ID       = 'dispute_id';
    const SUMMARY          = 'summary';
    const AMOUNT           = 'amount';
    const CURRENCY         = 'currency';
    const REJECTION_REASON = 'rejection_reason';
    const SOURCE           = 'source';

    const CURRENCY_LENGTH = 3;
    const SOURCE_LENGTH   = 50;

    const SUBMITTED_AT = 'submitted_at';


    //relations
    const DOCUMENTS = 'documents';

    protected $fillable = [
        self::DISPUTE_ID,
        self::SUMMARY,
        self::AMOUNT,
        self::CURRENCY,
        self::REJECTION_REASON,
        self::SOURCE,
    ];

    protected $visible = [
        self::ID,
        self::AMOUNT,
        self::SUMMARY,
        self::REJECTION_REASON,
        self::CURRENCY,
        self::SOURCE,
        self::SUBMITTED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DISPUTE_ID,
        self::DELETED_AT,
    ];

    protected $public = [
        self::AMOUNT,
        self::SUMMARY,
        self::SUBMITTED_AT,
    ];

    protected $publicSetters = [
        self::SUBMITTED_AT,
    ];

    protected $dates = [
        self::SUBMITTED_AT,
    ];

    protected $entity = RZPConstants\Entity::DISPUTE_EVIDENCE;

    protected $generateIdOnCreate = true;

    protected $dispute;

    public function documents()
    {
        return (new Document\Repository)->getDocumentsForDispute($this->getDisputeId());
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute\Entity::class);
    }

    public function toArray()
    {
        $data = parent::toArray();
        unset($data[self::DELETED_AT]);
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
        }

        return $data;
    }

    public function getDisputeId()
    {
        return $this->getAttribute(self::DISPUTE_ID);
    }

    public function setPublicSubmittedAtAttribute(array & $array)
    {
        $array[self::SUBMITTED_AT] = $this->getUpdatedAt();
    }

    public function toArrayPublic()
    {
        $result = parent::toArrayPublic();

        $result = $this->addAllDocumentTypesToResultOfToArrayPublic($result);

        foreach ($this->documents() as $document)
        {
            $documentType = $document->getType();

            $result = $this->processDocumentForAdditionIntoToArrayPublic($result, $document);
        }

        $result = $this->sortDocumentIdsInResultOfToArrayPublic($result);

        $result = $this->nullifyEmptyDocumentTypesInResultOfToArrayPublic($result);

        return $result;
    }

    protected function processDocumentForAdditionIntoToArrayPublic($result, Document\Entity $document)
    {
        if ($document->isOthersType() === true)
        {
            $result[Document\Types::OTHERS] = $this->processDocumentForAdditionIntoToArrayPublicOthersType(
                $result[Document\Types::OTHERS],
                $document);
        }
        else
        {
            array_push($result[$document->getType()], $document->getPublicDocumentId());
        }
        return $result;
    }

    protected function addAllDocumentTypesToResultOfToArrayPublic($result)
    {
        foreach (Document\Types::getTypes() as $type)
        {
            $result[$type] = [];
        }
        return $result;
    }

    protected function nullifyEmptyDocumentTypesInResultOfToArrayPublic($result)
    {
        foreach (Document\Types::getTypes() as $type)
        {
            if (count($result[$type]) > 0)
            {
                continue;
            }

            $result[$type] = null;
        }

        return $result;
    }

    protected function sortDocumentIdsInResultOfToArrayPublic($result)
    {
        foreach (Document\Types::getTypes() as $type)
        {
            if ($type === Document\Types::OTHERS)
            {
                usort($result[$type], function ($a, $b)
                {
                    return strcmp($a[Document\Entity::TYPE], $b[Document\Entity::TYPE]);
                });
            }
            else
            {
                sort($result[$type]);
            }

        }

        return $result;
    }

    protected function processDocumentForAdditionIntoToArrayPublicOthersType($othersTypeResult,
                                                                             Document\Entity $document)
    {
        $customType = $document->getCustomType();

        $customTypeExists = false;

        foreach ($othersTypeResult as &$row)
        {
            if ((isset($row[Document\Entity::TYPE]) === false) or
                ($row[Document\Entity::TYPE] !== $customType)
            )
            {
                continue;
            }

            $customTypeExists = true;

            array_push($row[Constants::DOCUMENT_IDS], $document->getPublicDocumentId());

        }

        if ($customTypeExists === false)
        {
            array_push($othersTypeResult, [
                Document\Entity::TYPE   => $document->getCustomType(),
                Constants::DOCUMENT_IDS => [$document->getPublicDocumentId()],
            ]);
        }

        return $othersTypeResult;
    }
}
