<?php

namespace RZP\Tests\Functional\Fixtures\Entity;

use Carbon\Carbon;
use RZP\Constants\Timezone;

class GatewayDowntime extends Base
{
    public function createCard(array $attributes = [])
    {
        // Sunday, 28 January 2018 00:00:00 GMT+05:30
        $begin = Carbon::createFromTimestamp(1517077800, Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::createFromTimestamp(1517077800, Timezone::IST)->addMinutes(60)->timestamp;

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

    public function createUpi(array $attributes = [])
    {
        $cardAttributes = [
            'method'      => 'upi',
            'card_type'   => 'NA',
            'reason_code' => 'OTHER',
            'source'      => 'other',
            'partial'     => false,
            'scheduled'   => true,
        ];

        $attributes = array_merge($cardAttributes, $attributes);

        $downtime = $this->fixtures->create('gateway_downtime', $attributes);

        return $downtime;
    }

    public function createNetbanking(array $attributes = [])
    {
        // Sunday, 28 January 2018 00:00:00 GMT+05:30
        $begin = Carbon::createFromTimestamp(1517077800, Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::createFromTimestamp(1517077800, Timezone::IST)->addMinutes(60)->timestamp;

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
        // Sunday, 28 January 2018 00:00:00 GMT+05:30
        $begin = Carbon::createFromTimestamp(1517077800, Timezone::IST)->subMinutes(60)->timestamp;

        $end = Carbon::createFromTimestamp(1517077800, Timezone::IST)->addMinutes(60)->timestamp;

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
