<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::UPI_TRANSFER_REQUEST;

    public function updateByGatewayAndNpciRefId(string $gateway, string $npciReferenceId, array $data)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->where(Entity::NPCI_REFERENCE_ID, $npciReferenceId)
                    ->update($data);
    }
}
