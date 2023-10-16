<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Exception;
use Rzp\Accounts\Merchant\V1 as MerchantV1;

trait DeleteNotSupported
{
    /**
     * @throws Exception
     */
    public function toDeleteProtoRequest(): MerchantV1\DeleteRequest
    {
        throw new Exception("delete not supported on entity");
    }

}
