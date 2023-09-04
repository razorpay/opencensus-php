<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

use Rzp\Accounts\Merchant\V1 as MerchantV1;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Constants as MerchantConstant;

class Merchant implements EntityToProtoConvertorInterface
{
    protected MerchantEntity $entity;

    function __construct(MerchantEntity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @throws \Exception
     */
    public function toSaveProtoRequest(): MerchantV1\SaveRequest
    {
        $saveRequest = new MerchantV1\SaveRequest();

        // Sample implementation
        $merchant = new MerchantV1\Merchant;
        $merchant->setNameUnwrapped("Acme Corp");
        $merchant->setId($this->entity->getId());
        $saveRequest->setMerchant($merchant);

        return $saveRequest;
    }
}
