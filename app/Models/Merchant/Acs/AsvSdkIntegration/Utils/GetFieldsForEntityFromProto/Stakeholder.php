<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class Stakeholder implements GetFieldsForEntityFromProtoInterface
{
    private MerchantV1\SaveResponse $saveResponse;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->saveResponse = $saveResponse;
    }

    public function getCreatedAt(): int
    {
        return $this->saveResponse->getStakeholders()[0]->getCreatedAt();
    }

    public function getUpdatedAt(): int
    {
        return $this->saveResponse->getStakeholders()[0]->getUpdatedAt();
    }

    public function getAuditId() : ?string {
        return $this->saveResponse->getStakeholders()[0]->getAuditIdUnwrapped();
    }
}
