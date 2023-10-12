<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class MerchantDetail implements GetFieldsForEntityFromProtoInterface
{
    private MerchantV1\SaveResponse $saveResponse;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->saveResponse = $saveResponse;
    }

    public function getCreatedAt() : int
    {
        return $this->saveResponse->getMerchantDetail()->getCreatedAt();
    }

    public function getUpdatedAt() : int
    {
        return $this->saveResponse->getMerchantDetail()->getUpdatedAt();
    }

    public function getAuditId(): ?string
    {
        return $this->saveResponse->getMerchantDetail()->getAuditIdUnwrapped();
    }
}
