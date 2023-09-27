<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class Document implements GetFieldsForEntityFromProtoInterface
{
    private $merchantDocument;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->merchantDocument = ($saveResponse->getMerchantDocuments())[0];
    }

    public function getCreatedAt() : int
    {
        return $this->merchantDocument->getCreatedAt();
    }

    public function getUpdatedAt() : int
    {
        return $this->merchantDocument->getUpdatedAt();
    }

    public function getAuditId(): ?string
    {
       return $this->merchantDocument->getAuditIdUnwrapped();
    }
}
