<?php

namespace RZP\Gateway\Base\Mock;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class RefundReconciliator extends Reconciliator
{
    public function generateReconciliation(array $input)
    {
        $refunds = $this->getAllRefundsToReconcile();

        $inputData = [];

        foreach ($refunds as $refund)
        {
            $data['refund'] = $refund->toArray();

            $this->addGatewayEntityIfNeeded($data, $refund);

            $inputData[] = $data;
        }

        return $this->generate($inputData);
    }

    protected function getAllRefundsToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->repo->refund->fetch(
            [
                'from'    => $createdAtStart,
                'to'      => $createdAtEnd,
                'gateway' => $this->gateway,
            ]);
    }
}