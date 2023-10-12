<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class Address implements GetFieldsForEntityFromProtoInterface
{
    private $address;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->address = ($saveResponse->getAddresses())[0];
    }

    public function getCreatedAt() : int
    {
        return $this->address->getCreatedAt();
    }

    public function getUpdatedAt() : int
    {
        return $this->address->getUpdatedAt();
    }

    public function getAuditId(): ?string
    {
        return null;
    }
}
