<?php

namespace RZP\Services\Mock;

use Carbon\Carbon;

class MandateHQ
{
    public function shouldSkipSummaryPage(): bool
    {
        return true;
    }

    public function isBinSupported($bin): bool
    {
        if (app()->isEnvironmentQA() === true)
            return false;
        return true;
    }

    public function validatePayment($mandateId, $input)
    {
        return [
            'validation_id' => 'KYteGki9M1GQIV',
            'mandate_id'    => 'ICn1KwWfZCcpVf',
            'afa_required'  => true,
            'time'          => '1666010496',
            'xid'           => 'MDAwMDAwMDAwMDFVdS1qMF9WZFM=',
            'cavv2'         => 'AAABCRgAACBQV2dVAgAAAAAAAAA=',
            'eci'           => '05',
            'version'       => 'protocol_version',
            'success'       => 'true'
        ];
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
            'afa_status'   => 'pending',
            'afa_required' => false,
        ];
    }

    public function cancelMandate($mandateId)
    {
        return [
            'id'           => 'C146gmBVNe',
            'status'       => 'cancelled',
        ];
    }

    public function reportPayment($mandateId, $input)
    {
        return [];
    }
}
