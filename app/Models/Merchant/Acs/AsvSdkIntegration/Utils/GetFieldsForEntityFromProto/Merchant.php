<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

class  Merchant implements GetFieldsForEntityFromProtoInterface
{
    private MerchantV1\SaveResponse $saveResponse;

    function __construct(MerchantV1\SaveResponse $saveResponse)
    {
        $this->saveResponse = $saveResponse;
    }

    public function getCreatedAt()
    {
        return $this->saveResponse->getMerchant()->getCreatedAt();
    }

    public function getUpdatedAt()
    {
        return $this->saveResponse->getMerchant()->getUpdatedAt();
    }
}
