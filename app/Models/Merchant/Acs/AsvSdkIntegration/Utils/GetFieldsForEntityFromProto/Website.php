<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class Website implements GetFieldsForEntityFromProtoInterface
{
    private MerchantV1\SaveResponse $saveResponse;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->saveResponse = $saveResponse;
    }

    public function getCreatedAt(): int
    {
        return $this->saveResponse->getMerchantWebsite()->getCreatedAt();
    }

    public function getUpdatedAt(): int
    {
        return $this->saveResponse->getMerchantWebsite()->getUpdatedAt();
    }

    public function getAuditId() : ?string {
        return $this->saveResponse->getMerchantWebsite()->getAuditIdUnwrapped() ?? "";
    }
}
