<?php


namespace RZP\Models\VirtualVpaPrefixHistory;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::VIRTUAL_VPA_PREFIX_HISTORY;

    public function deactivatePrefix(string $merchantId, string $virtualVpaPrefixId, int $deactivatedAt) : int
    {
        $data = [
            Entity::IS_ACTIVE           => false,
            Entity::DEACTIVATED_AT      => $deactivatedAt,
        ];

        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::VIRTUAL_VPA_PREFIX_ID, $virtualVpaPrefixId)
                    ->where(Entity::IS_ACTIVE, true)
                    ->update($data);
    }
}
