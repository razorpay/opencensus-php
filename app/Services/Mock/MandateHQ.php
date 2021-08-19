<?php

namespace RZP\Services\Mock;

use Carbon\Carbon;

class MandateHQ
{
    public function isBinSupported($bin): bool
    {
        return false;
    }

    public function registerMandate($input)
    {
        return [
            'redirect_url' => "https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage",
            'id'           => "C146gmBVNe",
            "status"       => "created",
        ];
    }

    public function createPreDebitNotification($mandateId, $input)
    {
        return [
            'id'           => 'C146gmBVNe',
            'status'       => 'delivered',
            'delivered_at' => Carbon::now()->timestamp,
        ];
    }

    public function reportPayment($mandateId, $input)
    {
        return [];
    }
}
