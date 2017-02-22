<?php

namespace RZP\Models\BankTransferAttempt;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Core extends Base\Core
{
    // public function createFromSettlement(Settlement\Entity $setl): Entity
    // {
    //     $bankTransferAttempt = new Entity;

    //     $values = [
    //         Entity::ENTITY_TYPE         => EntityType::SETTLEMENT,
    //         Entity::ENTITY_ID           => $setl->getId(),
    //         Entity::CHANNEL             => $setl->getChannel(),
    //         Entity::VERSION             => Version::V2,
    //     ];

    //     $bankTransferAttempt->fillAndGenerateId($values);

    //     $bankTransferAttempt->bankAccount()->associate($setl->bankAccount);

    //     $bankTransferAttempt->source()->associate($setl);

    //     $this->repo->saveOrFail($bankTransferAttempt);

    //     return $bankTransferAttempt;
    // }
}