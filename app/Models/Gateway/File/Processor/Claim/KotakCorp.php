<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base\PublicCollection;

class KotakCorp extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_KOTAK;
    const BANKCODE = Payment\Processor\Netbanking::KKBK_C;

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment->fetchReconciledPaymentsForGatewayWithBankCode(
            $begin,
            $end,
            static::GATEWAY,
            static::BANKCODE,
            $statuses
        );

        return $claims;
    }

    public function createFile($data)
    {
        return;
    }
}
