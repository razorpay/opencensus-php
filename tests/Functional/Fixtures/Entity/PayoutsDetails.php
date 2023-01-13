<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;

class PayoutsDetails extends Base
{
    public function create(array $attributes = [])
    {
        $payoutId = $attributes['payout_id'] ?? 'pout_123';

        $additionalInfo = $attributes['additional_info'] ?? null;

        \DB::connection('live')->table('payouts_details')
            ->insert([
                'payout_id' => $payoutId,
                'additional_info' => json_encode($additionalInfo),
                'queue_if_low_balance_flag' => 1,
                'created_at'  => Carbon::now()->getTimestamp(),
                'updated_at'  => Carbon::now()->getTimestamp(),
            ]);
    }
}
