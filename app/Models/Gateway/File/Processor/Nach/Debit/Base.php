<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Gateway\Netbanking;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Processor\Nach;

abstract class Base extends Nach\Base
{
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)
            ->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)
            ->getTimestamp();

        $tokens = $this->repo->token->fetchPendingNachDebit($this->gateway, $begin, $end);

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'entity_ids'      => $paymentIds,
                'begin'           => $begin,
                'end'             => $end,
            ]);

        return $tokens;
    }

    public function generateData(PublicCollection $tokens)
    {
            return $tokens;
    }
}
