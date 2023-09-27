<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Document\Entity;
use RZP\Models\Merchant\Document\Source;

class Document implements EntityToProtoConvertorInterface
{
    protected Entity $entity;

    function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        $document = new MerchantV1\MerchantDocument();

        $rawAttributes = $this->entity->getAttributes();

        $document->setId(Helper::notNullCheck($rawAttributes, UniqueIdEntity::ID));
        $document->setMerchantId(Helper::notNullCheck($rawAttributes, PublicEntity::MERCHANT_ID));
        $document->setFileStoreId(Helper::converToStringValue($rawAttributes, Entity::FILE_STORE_ID));
        $document->setSource($rawAttributes[Entity::SOURCE] ?? Source::API);
        $document->setDocumentType(Helper::notNullCheck($rawAttributes, Entity::DOCUMENT_TYPE));
        $document->setEntityType(Helper::notNullCheck($rawAttributes, Entity::ENTITY_TYPE));
        $document->setEntityId(Helper::converToStringValue($rawAttributes, Entity::ENTITY_ID));
        $document->setUploadByAdminId(Helper::converToStringValue($rawAttributes, Entity::UPLOAD_BY_ADMIN_ID));
        $document->setMetadata(Helper::converToStringValue($rawAttributes, Entity::METADATA));
        $document->setOcrVerify(Helper::converToStringValue($rawAttributes, Entity::OCR_VERIFY));
        $document->setValidationId(Helper::converToStringValue($rawAttributes, Entity::VALIDATION_ID));
        $document->setDocumentDate(Helper::convertToUInt32Value($rawAttributes, Entity::DOCUMENT_DATE));
        $document->setAuditId(Helper::converToStringValue($rawAttributes, Entity::AUDIT_ID));

        $saveRequest->setMerchantDocuments([$document]);
        return $saveRequest;
    }
}
