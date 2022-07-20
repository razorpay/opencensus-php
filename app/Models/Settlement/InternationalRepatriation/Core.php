<?php

namespace RZP\Models\Settlement\InternationalRepatriation;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createInternationalRepatriation(array $input): Entity
    {
        $internationalRepatriation = new Entity;

        $internationalRepatriation->generateId();

        $internationalRepatriation->build($input);

        $this->repo->settlement_international_repatriation->saveOrFail($internationalRepatriation);

        $this->trace->info(TraceCode::REPATRIATION_DETAIL_SAVE, [
            'merchant_id'             => $input[Entity::MERCHANT_ID],
            'integration_entity'      => $input[Entity::INTEGRATION_ENTITY],
            'id'                      => $internationalRepatriation->getId()
        ]);

        return $internationalRepatriation;
    }
}
