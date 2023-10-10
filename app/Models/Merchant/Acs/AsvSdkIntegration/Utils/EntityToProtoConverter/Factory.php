<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter;

class Factory
{
    public static function getEntityToProtoConvertor($entity, array $dirtyFieldKeys): ?EntityToProtoConvertorInterface{

        return match (get_class($entity)) {
            \RZP\Models\Merchant\Website\Entity::class => new Website($entity, $dirtyFieldKeys),
            \RZP\Models\Merchant\Email\Entity::class => new Email($entity, $dirtyFieldKeys),
            \RZP\Models\Merchant\BusinessDetail\Entity::class => new BusinessDetail($entity, $dirtyFieldKeys),
            \RZP\Models\Merchant\Document\Entity::class => new Document($entity, $dirtyFieldKeys),
            default => null,
        };

    }
}
