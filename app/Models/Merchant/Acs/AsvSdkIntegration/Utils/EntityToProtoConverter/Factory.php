<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

class Factory
{
    public static function getEntityToProtoConvertor($entity): ?EntityToProtoConvertorInterface{

        return match (get_class($entity)) {
            \RZP\Models\Merchant\Website\Entity::class => new Website($entity),
            \RZP\Models\Merchant\Email\Entity::class => new Email($entity),
            \RZP\Models\Merchant\BusinessDetail\Entity::class => new BusinessDetail($entity),
            default => null,
        };

    }
}
