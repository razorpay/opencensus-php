<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

interface EntityToProtoConvertorInterface
{
    public function toSaveProtoRequest(): MerchantV1\SaveRequest;

    public function toDeleteProtoRequest(): MerchantV1\DeleteRequest;
}
