<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class Email implements GetFieldsForEntityFromProtoInterface
{
    private MerchantV1\SaveResponse $saveResponse;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->saveResponse = $saveResponse;
    }

    public function getCreatedAt() : int
    {
        return ($this->saveResponse->getMerchantEmails())[0]->getCreatedAt();
    }

    public function getUpdatedAt() : int
    {
        return ($this->saveResponse->getMerchantEmails())[0]->getUpdatedAt();
    }

    public function getAuditId(): ?string
    {
        return null;
    }
}
