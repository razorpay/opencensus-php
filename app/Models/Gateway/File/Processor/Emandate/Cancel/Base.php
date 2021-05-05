<?php

namespace RZP\Models\Gateway\File\Processor\Emandate\Cancel;

use RZP\Trace\TraceCode;
use RZP\Gateway\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\Emandate;

abstract class Base extends EMandate\Base
{
    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();

        $end = $this->gatewayFile->getEnd();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        $tokens = $this->repo->token->fetchDeletedTokensForMethods(
            static::METHODS, static::GATEWAYS, static::ACQUIRER, $begin, $end);

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        $tokenIds = $tokens->pluck('id')->toArray();

        $this->trace->info(TraceCode::EMANDATE_CANCEL_REQUEST, [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $tokenIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    public function generateData(PublicCollection $entities): PublicCollection
    {
        return $entities;
    }
}
