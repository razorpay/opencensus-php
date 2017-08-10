<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class GatewayDowntime extends Base
{
    public function createCard(array $attributes = [])
    {
        $begin = Carbon::now(Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::now(Timezone::IST)->addMinutes(60)->timestamp;

        $cardAttributes = [
            'method'      => 'card',
            'card_type'   => 'credit',
            'reason_code' => 'OTHER',
            'source'      => 'other',
            'partial'     => false,
            'scheduled'   => true,
            'begin'       => $begin,
            'end'         => $end,
        ];

        $attributes = array_merge($cardAttributes, $attributes);

        $downtime = $this->fixtures->create('gateway_downtime', $attributes);

        return $downtime;
    }

    public function createNetbanking(array $attributes = [])
    {
        $begin = Carbon::now(Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::now(Timezone::IST)->addMinutes(60)->timestamp;

        $netbankingAttributes = [
            'method'      => 'netbanking',
            'reason_code' => 'OTHER',
            'source'      => 'other',
            'partial'     => false,
            'scheduled'   => true,
            'begin'       => $begin,
            'end'         => $end
        ];

        $attributes = array_merge($netbankingAttributes, $attributes);

        $downtime = $this->fixtures->create('gateway_downtime', $attributes);

        return $downtime;
    }

    public function createWallet(array $attributes = [])
    {
        $begin = Carbon::now(Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::now(Timezone::IST)->addMinutes(60)->timestamp;

        $walletAttributes = [
            'method'      => 'wallet',
            'reason_code' => 'OTHER',
            'source'      => 'other',
            'partial'     => false,
            'scheduled'   => true,
            'begin'       => $begin,
            'end'         => $end,
        ];

        $attributes = array_merge($walletAttributes, $attributes);

        $downtime = $this->fixtures->create('gateway_downtime', $attributes);

        return $downtime;
    }
}
