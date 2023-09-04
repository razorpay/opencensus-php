<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

class Factory
{
    public static function getEntityToProtoConvertor($entity): ?EntityToProtoConvertorInterface{
        if ($entity instanceof \RZP\Models\Merchant\Entity) {
            return new Merchant($entity);
        }
        return null;
    }
}
