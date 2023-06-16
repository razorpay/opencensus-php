<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;

class MerchantDocument
{
    protected MerchantV1\MerchantDocument $proto;

    function __construct(MerchantV1\MerchantDocument $proto)
    {
        $this->proto = $proto;
    }

    public function ToEntity(): MerchantDocumentEntity
    {
        $merchantDocumentRawAttributes = [
            MerchantDocumentEntity::ID => $this->proto->getId(),
            MerchantDocumentEntity::MERCHANT_ID => $this->proto->getMerchantId(),
            MerchantDocumentEntity::FILE_STORE_ID => $this->proto->getFileStoreIdUnwrapped(),
            MerchantDocumentEntity::SOURCE => $this->proto->getSource(),
            MerchantDocumentEntity::DOCUMENT_TYPE => $this->proto->getDocumentType(),
            MerchantDocumentEntity::ENTITY_TYPE => $this->proto->getEntityType(),
            MerchantDocumentEntity::ENTITY_ID => $this->proto->getEntityIdUnwrapped(),

            MerchantDocumentEntity::UPLOAD_BY_ADMIN_ID => $this->proto->getUploadByAdminIdUnwrapped(),
            MerchantDocumentEntity::AUDIT_ID => $this->proto->getAuditIdUnwrapped(),
            MerchantDocumentEntity::METADATA => $this->proto->getMetadataUnwrapped(),
            MerchantDocumentEntity::OCR_VERIFY => $this->proto->getOcrVerifyUnwrapped(),
            MerchantDocumentEntity::VALIDATION_ID => $this->proto->getValidationIdUnwrapped(),
            MerchantDocumentEntity::DOCUMENT_DATE => $this->proto->getDocumentDateUnwrapped(),
            MerchantDocumentEntity::CREATED_AT => $this->proto->getCreatedAt(),
            MerchantDocumentEntity::UPDATED_AT => $this->proto->getUpdatedAt(),
            MerchantDocumentEntity::DELETED_AT => $this->proto->getDeletedAtUnwrapped(),
        ];
        $merchantDocumentEntity = new MerchantDocumentEntity();
        $merchantDocumentEntity->setRawAttributes($merchantDocumentRawAttributes);
        $merchantDocumentEntity->exists = true;
        return $merchantDocumentEntity;
    }
}
